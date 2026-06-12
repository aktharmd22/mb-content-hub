<?php

namespace App\Http\Controllers;

use App\Exceptions\DriveException;
use App\Models\InboxConversation;
use App\Models\User;
use App\Services\GoogleDriveService;
use App\Services\InboxService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InboxController extends Controller
{
    public function __construct(private readonly InboxService $service) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        $conversations = $this->loadConversationsFor($user, $request->boolean('all') && $user->isAdmin());

        $activeId = $request->integer('open') ?: $conversations->first()?->id;
        $active   = $activeId ? $conversations->firstWhere('id', $activeId) : null;

        if ($active) {
            $active->load(['messages.user', 'participants.user']);
            $this->service->markRead($active, $user);
        }

        // Pool of users to start conversations with (anyone except self)
        $teammates = User::where('is_active', true)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('inbox.index', [
            'conversations' => $conversations,
            'active'        => $active,
            'teammates'     => $teammates,
            'showingAll'    => $request->boolean('all') && $user->isAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'participants'   => ['required', 'array', 'min:1'],
            'participants.*' => ['integer', 'exists:users,id'],
            'title'          => ['nullable', 'string', 'max:120'],
            'context_type'   => ['nullable', 'in:article,viral_package'],
            'context_id'     => ['nullable', 'integer'],
            'first_message'  => ['nullable', 'string', 'max:5000'],
        ]);

        // For 1-on-1 with no context, reuse an existing direct thread if it exists
        if (
            count($data['participants']) === 1 &&
            empty($data['context_type']) &&
            empty($data['context_id'])
        ) {
            $existing = $this->service->findDirectConversation(auth()->id(), (int) $data['participants'][0]);
            if ($existing) {
                if (! empty($data['first_message'])) {
                    $this->service->sendMessage($existing, $data['first_message']);
                }
                return redirect()->route('inbox.index', ['open' => $existing->id]);
            }
        }

        $convo = $this->service->createConversation(
            participantUserIds: $data['participants'],
            title:              $data['title'] ?? null,
            contextType:        $data['context_type'] ?? null,
            contextId:          isset($data['context_id']) ? (int) $data['context_id'] : null,
        );

        if (! empty($data['first_message'])) {
            $this->service->sendMessage($convo, $data['first_message']);
        }

        return redirect()->route('inbox.index', ['open' => $convo->id])
            ->with('success', 'Conversation started.');
    }

    public function sendMessage(Request $request, InboxConversation $conversation)
    {
        $this->ensureParticipant($conversation);

        $data = $request->validate([
            'body'       => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:51200'],   // 50 MB cap on inbox attachments
        ]);

        if (empty($data['body']) && ! $request->hasFile('attachment')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'error' => 'Message body or attachment is required.'], 422);
            }
            return back()->with('error', 'Message body or attachment is required.');
        }

        try {
            $msg = $this->service->sendMessage(
                conversation: $conversation,
                body:         $data['body'] ?? '',
                attachment:   $request->file('attachment'),
            );
        } catch (\Throwable $e) {
            report($e);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'error' => 'Could not send message: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Could not send message: ' . $e->getMessage());
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'id' => $msg?->id]);
        }
        return redirect()->route('inbox.index', ['open' => $conversation->id]);
    }

    /**
     * Delete a message. Sender can delete their own; admins can delete any.
     */
    public function deleteMessage(Request $request, InboxConversation $conversation, \App\Models\InboxMessage $message)
    {
        $user = auth()->user();

        if ($message->conversation_id !== $conversation->id) {
            abort(404);
        }

        if (! $user->isAdmin() && $message->user_id !== $user->id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'error' => 'Not allowed.'], 403);
            }
            abort(403);
        }

        $message->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }
        return back();
    }

    /**
     * Live polling endpoint — returns HTML for any new messages with id > after.
     */
    public function stream(Request $request, InboxConversation $conversation)
    {
        $this->ensureParticipant($conversation);

        $after = $request->integer('after', 0);

        $messages = $conversation->messages()
            ->where('id', '>', $after)
            ->with('user')
            ->orderBy('id')
            ->get();

        if ($messages->isNotEmpty()) {
            $this->service->markRead($conversation, auth()->user());
        }

        return view('inbox._stream', [
            'messages'     => $messages,
            'conversation' => $conversation,
            'user'         => auth()->user(),
        ]);
    }

    public function togglePin(InboxConversation $conversation): RedirectResponse
    {
        $this->ensureParticipant($conversation);
        $pinned = $this->service->togglePin($conversation, auth()->user());
        return back()->with('success', $pinned ? 'Pinned.' : 'Unpinned.');
    }

    public function downloadAttachment(InboxConversation $conversation, \App\Models\InboxMessage $message, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        $this->ensureParticipant($conversation);

        if ($message->conversation_id !== $conversation->id || ! $message->attachment_drive_file_id) {
            abort(404);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'inbox_');
        try {
            $drive->downloadFile($message->attachment_drive_file_id, $tempPath);
        } catch (DriveException $e) {
            @unlink($tempPath);
            return back()->with('error', $e->getMessage());
        }

        return response()->download($tempPath, $message->attachment_filename ?: 'attachment')->deleteFileAfterSend();
    }

    /**
     * AJAX endpoint for polling — returns unread count for nav badge.
     */
    public function unreadCount()
    {
        return response()->json([
            'count' => $this->service->totalUnreadFor(auth()->user()),
        ]);
    }

    private function loadConversationsFor(User $user, bool $adminAllMode = false)
    {
        $query = InboxConversation::query()
            ->with(['participants.user', 'lastMessage.user', 'messages']);

        if (! $adminAllMode) {
            $query->whereHas('participants', fn ($q) => $q->where('user_id', $user->id));
        }

        return $query
            ->orderByDesc('last_message_at')
            ->get()
            ->each(function ($c) use ($user) {
                $c->setRelation('participants', $c->participants->loadMissing('user'));
            })
            // Sort: pinned first, then by last_message_at desc
            ->sortByDesc(function ($c) use ($user) {
                $participant = $c->participants->firstWhere('user_id', $user->id);
                $pinnedBoost = ($participant && $participant->pinned) ? 1e15 : 0;
                return $pinnedBoost + ($c->last_message_at?->timestamp ?? 0);
            })
            ->values();
    }

    private function ensureParticipant(InboxConversation $conversation): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return; // admin can act on any conversation
        if (! $conversation->isParticipant($user)) {
            throw new AuthorizationException('You are not a participant in this conversation.');
        }
    }
}
