<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Notifications\SupportTicketCreatedNotification;
use App\Notifications\SupportTicketReplyNotification;
use App\Notifications\SupportTicketUpdatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SupportService
{
    public function nextCode(): string
    {
        $last = SupportTicket::withTrashed()->orderByDesc('id')->first();
        $num  = ($last?->id ?? 0) + 1;
        return 'TKT-' . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
    }

    public function create(User $reporter, array $data): SupportTicket
    {
        return DB::transaction(function () use ($reporter, $data) {
            $ticket = SupportTicket::create([
                'code'             => $this->nextCode(),
                'subject'          => $data['subject'],
                'description'      => $data['description'],
                'priority'         => $data['priority'] ?? 'normal',
                'status'           => 'open',
                'reporter_id'      => $reporter->id,
                'assignee_id'      => $data['assignee_id'] ?? null,
                'last_activity_at' => now(),
            ]);

            $this->notifyOnCreate($ticket);

            return $ticket;
        });
    }

    public function reply(SupportTicket $ticket, User $author, string $body): SupportTicketReply
    {
        return DB::transaction(function () use ($ticket, $author, $body) {
            $reply = SupportTicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id'   => $author->id,
                'body'      => $body,
                'is_system' => false,
            ]);

            // Auto-progress: if reporter replies while waiting, move to in_progress; if assignee replies on open, move to in_progress.
            if ($ticket->status === 'open' && $ticket->assignee_id === $author->id) {
                $ticket->status = 'in_progress';
            } elseif ($ticket->status === 'waiting_user' && $ticket->reporter_id === $author->id) {
                $ticket->status = 'in_progress';
            }

            $ticket->last_activity_at = now();
            $ticket->save();

            $this->notifyOnReply($ticket, $reply, $author);

            return $reply;
        });
    }

    public function systemMessage(SupportTicket $ticket, string $body): SupportTicketReply
    {
        return SupportTicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'body'      => $body,
            'is_system' => true,
        ]);
    }

    public function setStatus(SupportTicket $ticket, string $status, User $actor): void
    {
        if ($ticket->status === $status) return;

        $previous = $ticket->statusLabel();
        $ticket->status = $status;
        $ticket->last_activity_at = now();
        if ($status === 'resolved' && ! $ticket->resolved_at) $ticket->resolved_at = now();
        if ($status === 'closed' && ! $ticket->closed_at) $ticket->closed_at = now();
        $ticket->save();

        $this->systemMessage($ticket, "**{$actor->name}** changed status: {$previous} → {$ticket->statusLabel()}");
        $this->notifyUpdate($ticket, $actor, "Status changed to {$ticket->statusLabel()}");
    }

    public function setPriority(SupportTicket $ticket, string $priority, User $actor): void
    {
        if ($ticket->priority === $priority) return;

        $previous = $ticket->priorityLabel();
        $ticket->priority = $priority;
        $ticket->last_activity_at = now();
        $ticket->save();

        $this->systemMessage($ticket, "**{$actor->name}** changed priority: {$previous} → {$ticket->priorityLabel()}");
    }

    public function assignTo(SupportTicket $ticket, ?int $assigneeId, User $actor): void
    {
        if ($ticket->assignee_id === $assigneeId) return;

        $newAssignee = $assigneeId ? User::find($assigneeId) : null;
        $newName     = $newAssignee?->name ?? 'Admin pool';

        $ticket->assignee_id = $assigneeId;
        $ticket->last_activity_at = now();
        if ($ticket->status === 'open' && $assigneeId && $ticket->reporter_id !== $assigneeId) {
            // remain 'open' until assignee replies
        }
        $ticket->save();

        $this->systemMessage($ticket, "**{$actor->name}** assigned ticket to **{$newName}**");

        if ($newAssignee) {
            try {
                $newAssignee->notify(new SupportTicketUpdatedNotification($ticket, 'assigned'));
            } catch (\Throwable $e) { report($e); }
        }
    }

    /**
     * Assignee bounces ticket back to admin pool.
     */
    public function bounceToAdminPool(SupportTicket $ticket, User $actor): void
    {
        if ($ticket->assignee_id === null) return;

        $ticket->assignee_id = null;
        $ticket->status = 'open';
        $ticket->last_activity_at = now();
        $ticket->save();

        $this->systemMessage($ticket, "**{$actor->name}** bounced ticket back to Admin pool");

        try {
            Notification::send($this->admins(), new SupportTicketUpdatedNotification($ticket, 'bounced'));
        } catch (\Throwable $e) { report($e); }
    }

    /**
     * Counts for the filter tabs displayed in the list page.
     */
    public function countsFor(User $user): array
    {
        $base = SupportTicket::query();

        if (! $user->isAdmin()) {
            $base->where(function ($q) use ($user) {
                $q->where('reporter_id', $user->id)
                  ->orWhere('assignee_id', $user->id);
            });
        }

        $clone = fn () => (clone $base);

        return [
            'all'            => $clone()->count(),
            'open'           => $clone()->where('status', 'open')->count(),
            'in_progress'    => $clone()->where('status', 'in_progress')->count(),
            'resolved'       => $clone()->where('status', 'resolved')->count(),
            'closed'         => $clone()->where('status', 'closed')->count(),
            'unassigned'     => $user->isAdmin()
                ? SupportTicket::whereNull('assignee_id')->whereNotIn('status', ['closed', 'resolved'])->count()
                : 0,
            'assigned_to_me' => SupportTicket::where('assignee_id', $user->id)->whereNotIn('status', ['closed'])->count(),
            'raised_by_me'   => SupportTicket::where('reporter_id', $user->id)->whereNotIn('status', ['closed'])->count(),
        ];
    }

    /**
     * Sidebar badge = number of UNREAD support notifications.
     * Clears automatically once the user opens/reads them — only genuinely new activity shows.
     */
    public function activeForBadge(User $user): int
    {
        return $user->unreadNotifications()
            ->where('data->type', 'like', 'support%')
            ->count();
    }

    private function admins()
    {
        return User::where('role', 'admin')->where('is_active', true)->get();
    }

    private function notifyOnCreate(SupportTicket $ticket): void
    {
        try {
            if ($ticket->assignee_id) {
                $assignee = User::find($ticket->assignee_id);
                $assignee?->notify(new SupportTicketCreatedNotification($ticket));
            } else {
                Notification::send($this->admins(), new SupportTicketCreatedNotification($ticket));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function notifyOnReply(SupportTicket $ticket, SupportTicketReply $reply, User $author): void
    {
        try {
            $recipients = collect();

            // Notify the other party of the thread
            if ($ticket->reporter_id !== $author->id) {
                $reporter = $ticket->reporter;
                if ($reporter) $recipients->push($reporter);
            }
            if ($ticket->assignee_id && $ticket->assignee_id !== $author->id) {
                $assignee = $ticket->assignee;
                if ($assignee) $recipients->push($assignee);
            }

            // If unassigned, also loop in admins so they're aware activity is happening
            if (! $ticket->assignee_id) {
                foreach ($this->admins() as $admin) {
                    if ($admin->id !== $author->id) $recipients->push($admin);
                }
            }

            $recipients = $recipients->unique('id');
            foreach ($recipients as $r) {
                $r->notify(new SupportTicketReplyNotification($ticket, $reply));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function notifyUpdate(SupportTicket $ticket, User $actor, string $message): void
    {
        try {
            $recipients = collect();
            if ($ticket->reporter_id !== $actor->id && $ticket->reporter) {
                $recipients->push($ticket->reporter);
            }
            if ($ticket->assignee_id && $ticket->assignee_id !== $actor->id && $ticket->assignee) {
                $recipients->push($ticket->assignee);
            }
            foreach ($recipients->unique('id') as $r) {
                $r->notify(new SupportTicketUpdatedNotification($ticket, 'status'));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
