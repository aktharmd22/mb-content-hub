<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\User;
use App\Services\SupportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function __construct(private readonly SupportService $service) {}

    public function index(Request $request): View
    {
        $user    = auth()->user();
        $isAdmin = $user->isAdmin();
        $filter  = $request->get('filter', $isAdmin ? 'all' : 'mine');

        $query = SupportTicket::query()->with(['reporter', 'assignee']);

        if (! $isAdmin) {
            $query->where(function ($q) use ($user) {
                $q->where('reporter_id', $user->id)
                  ->orWhere('assignee_id', $user->id);
            });
        }

        match ($filter) {
            'open'           => $query->where('status', 'open'),
            'in_progress'    => $query->where('status', 'in_progress'),
            'waiting_user'   => $query->where('status', 'waiting_user'),
            'resolved'       => $query->where('status', 'resolved'),
            'closed'         => $query->where('status', 'closed'),
            'unassigned'     => $isAdmin ? $query->whereNull('assignee_id')->whereNotIn('status', ['closed', 'resolved']) : null,
            'assigned_to_me' => $query->where('assignee_id', $user->id)->whereNotIn('status', ['closed']),
            'raised_by_me'   => $query->where('reporter_id', $user->id)->whereNotIn('status', ['closed']),
            default          => null,
        };

        if ($q = trim((string) $request->get('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where('code', 'like', "%{$q}%")
                  ->orWhere('subject', 'like', "%{$q}%");
            });
        }

        $priorityOrder = "FIELD(priority, 'urgent', 'high', 'normal', 'low')";
        $tickets = $query
            ->orderByRaw($priorityOrder)
            ->orderByDesc('last_activity_at')
            ->paginate(20)
            ->withQueryString();

        $counts = $this->service->countsFor($user);

        return view('support.index', [
            'tickets' => $tickets,
            'counts'  => $counts,
            'filter'  => $filter,
            'q'       => $q,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function create(): View
    {
        $assignableUsers = User::where('is_active', true)
            ->where('id', '!=', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('support.create', compact('assignableUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject'       => ['required', 'string', 'max:200'],
            'description'   => ['required', 'string', 'max:5000'],
            'priority'      => ['required', 'in:low,normal,high,urgent'],
            'target'        => ['required', 'in:admin_pool,specific'],
            'assignee_id'   => ['nullable', 'integer', 'exists:users,id', 'required_if:target,specific'],
            'attachments'   => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240'], // 10 MB each
        ]);

        if ($data['target'] === 'admin_pool') {
            $data['assignee_id'] = null;
        }

        $ticket = $this->service->create(auth()->user(), $data);

        $this->attachFiles($ticket, $request->file('attachments', []));

        return redirect()->route('support.show', $ticket)
            ->with('success', "Ticket {$ticket->code} created.");
    }

    public function show(SupportTicket $ticket): View
    {
        $user = auth()->user();
        abort_unless($ticket->canBeViewedBy($user), 403);

        // Mark this ticket's unread notifications as read so the sidebar badge clears.
        $user->unreadNotifications()
            ->where('data->type', 'like', 'support%')
            ->where('data->ticket_id', $ticket->id)
            ->get()
            ->each->markAsRead();

        $ticket->load(['reporter', 'assignee', 'attachments', 'replies.user', 'replies.attachments']);

        $assignableUsers = $user->isAdmin()
            ? User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'role'])
            : collect();

        return view('support.show', [
            'ticket'          => $ticket,
            'assignableUsers' => $assignableUsers,
            'isAdmin'         => $user->isAdmin(),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($ticket->canBeViewedBy($user), 403);
        abort_if($ticket->status === 'closed', 422, 'Cannot reply on a closed ticket.');

        $data = $request->validate([
            'body'          => ['nullable', 'string', 'max:5000'],
            'attachments'   => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240'], // 10 MB each
        ]);

        if (empty($data['body']) && ! $request->hasFile('attachments')) {
            return back()->with('error', 'Add a message or attach a file.');
        }

        $reply = $this->service->reply($ticket, $user, $data['body'] ?? '');

        $this->attachFiles($reply, $request->file('attachments', []));

        return back()->with('success', 'Reply added.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($ticket->canBeViewedBy($user), 403);

        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,waiting_user,resolved,closed'],
        ]);

        // Non-admin reporter can only resolve or close their own ticket
        if (! $user->isAdmin() && $user->id === $ticket->reporter_id && $ticket->assignee_id !== $user->id) {
            if (! in_array($data['status'], ['resolved', 'closed', 'open'])) {
                abort(403, 'You can only resolve, close, or reopen your own tickets.');
            }
        }

        $this->service->setStatus($ticket, $data['status'], $user);

        return back()->with('success', 'Status updated.');
    }

    public function updatePriority(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isAdmin(), 403);

        $data = $request->validate([
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ]);

        $this->service->setPriority($ticket, $data['priority'], $user);

        return back()->with('success', 'Priority updated.');
    }

    public function assign(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isAdmin(), 403);

        $data = $request->validate([
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $this->service->assignTo($ticket, $data['assignee_id'] ?? null, $user);

        return back()->with('success', 'Assignment updated.');
    }

    public function bounce(SupportTicket $ticket): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($ticket->assignee_id === $user->id, 403, 'Only the assignee can bounce this ticket.');

        $this->service->bounceToAdminPool($ticket, $user);

        return back()->with('success', 'Ticket bounced back to Admin pool.');
    }

    public function destroy(Request $request, SupportTicket $ticket): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Only admins can delete tickets.');

        $code = $ticket->code;

        // Remove any notifications pointing at this ticket so badges/bells don't show orphans.
        \Illuminate\Notifications\DatabaseNotification::where('data->ticket_id', $ticket->id)->delete();

        // Delete stored attachment files (ticket + replies) and their records.
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        foreach ($ticket->attachments as $a) {
            if ($a->path) $disk->delete($a->path);
        }
        $ticket->attachments()->delete();
        foreach ($ticket->replies as $r) {
            foreach ($r->attachments as $a) {
                if ($a->path) $disk->delete($a->path);
            }
            $r->attachments()->delete();
        }

        $ticket->replies()->delete();
        $ticket->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('support.index')->with('success', "Ticket {$code} deleted.");
    }

    /**
     * Download a support attachment (permission-checked, must belong to this ticket or its replies).
     */
    public function downloadAttachment(SupportTicket $ticket, \App\Models\SupportAttachment $attachment)
    {
        abort_unless($ticket->canBeViewedBy(auth()->user()), 403);

        $belongs = ($attachment->attachable_type === SupportTicket::class && $attachment->attachable_id === $ticket->id)
            || ($attachment->attachable_type === \App\Models\SupportTicketReply::class
                && $ticket->replies()->whereKey($attachment->attachable_id)->exists());

        abort_unless($belongs, 404);
        abort_unless($attachment->path && \Illuminate\Support\Facades\Storage::disk('local')->exists($attachment->path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    /**
     * Store uploaded files privately and link them to the given model (ticket or reply).
     *
     * @param  \App\Models\SupportTicket|\App\Models\SupportTicketReply  $model
     * @param  array<\Illuminate\Http\UploadedFile|null>  $files
     */
    private function attachFiles($model, array $files): void
    {
        foreach ($files as $file) {
            if (! $file) continue;
            $path = $file->store('support-attachments', 'local');
            $model->attachments()->create([
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'size'          => $file->getSize(),
                'mime'          => $file->getMimeType(),
                'uploaded_by'   => auth()->id(),
            ]);
        }
    }
}
