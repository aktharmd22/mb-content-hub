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
            'subject'     => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'priority'    => ['required', 'in:low,normal,high,urgent'],
            'target'      => ['required', 'in:admin_pool,specific'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id', 'required_if:target,specific'],
        ]);

        if ($data['target'] === 'admin_pool') {
            $data['assignee_id'] = null;
        }

        $ticket = $this->service->create(auth()->user(), $data);

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

        $ticket->load(['reporter', 'assignee', 'replies.user']);

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
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $this->service->reply($ticket, $user, $data['body']);

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
}
