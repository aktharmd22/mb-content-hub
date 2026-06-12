<?php

namespace App\Services;

use App\Models\InboxConversation;
use App\Models\InboxMessage;
use App\Models\InboxParticipant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InboxService
{
    public function __construct(private readonly GoogleDriveService $drive) {}

    /**
     * Create a new conversation with the given participant user IDs.
     * If $contextType and $contextId are set, ties it to that article/package.
     */
    public function createConversation(
        array $participantUserIds,
        ?string $title = null,
        ?string $contextType = null,
        ?int $contextId = null,
        ?User $creator = null
    ): InboxConversation {
        $creator ??= Auth::user();

        // Always include the creator
        $allParticipants = collect($participantUserIds)
            ->push($creator->id)
            ->unique()
            ->values()
            ->all();

        return DB::transaction(function () use ($title, $contextType, $contextId, $creator, $allParticipants) {
            $convo = InboxConversation::create([
                'title'           => $title,
                'context_type'    => $contextType,
                'context_id'      => $contextId,
                'created_by'      => $creator->id,
                'last_message_at' => now(),
            ]);

            foreach ($allParticipants as $userId) {
                InboxParticipant::create([
                    'conversation_id' => $convo->id,
                    'user_id'         => $userId,
                    'joined_at'       => now(),
                    'last_read_at'    => $userId === $creator->id ? now() : null,
                ]);
            }

            return $convo->fresh(['participants.user']);
        });
    }

    /**
     * Find an existing 1-on-1 conversation between two users (no context), or null.
     */
    public function findDirectConversation(int $userIdA, int $userIdB): ?InboxConversation
    {
        if ($userIdA === $userIdB) return null;

        return InboxConversation::query()
            ->whereNull('context_type')
            ->whereNull('context_id')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userIdA))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userIdB))
            ->withCount('participants')
            ->having('participants_count', '=', 2)
            ->first();
    }

    /**
     * Send a message in a conversation. Supports optional file attachment (uploads to Drive).
     */
    public function sendMessage(
        InboxConversation $conversation,
        string $body,
        ?UploadedFile $attachment = null,
        ?User $sender = null
    ): InboxMessage {
        $sender ??= Auth::user();

        $attachmentData = [];
        if ($attachment) {
            $folderId = $this->ensureInboxAttachmentsFolder();
            if ($folderId) {
                $filename = $attachment->getClientOriginalName();
                try {
                    $driveFileId = $this->drive->uploadFile($attachment->getRealPath(), $folderId, $filename);
                    $attachmentData = [
                        'attachment_drive_file_id' => $driveFileId,
                        'attachment_filename'      => $filename,
                        'attachment_mime_type'     => $attachment->getMimeType(),
                        'attachment_size'          => $attachment->getSize(),
                    ];
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return DB::transaction(function () use ($conversation, $body, $sender, $attachmentData) {
            $message = InboxMessage::create(array_merge([
                'conversation_id' => $conversation->id,
                'user_id'         => $sender->id,
                'body'            => $body,
                'mentions'        => $this->extractMentions($body),
            ], $attachmentData));

            $conversation->update(['last_message_at' => now()]);

            // Sender has implicitly read what they sent
            $conversation->participants()
                ->where('user_id', $sender->id)
                ->update(['last_read_at' => now()]);

            return $message;
        });
    }

    public function markRead(InboxConversation $conversation, User $user): void
    {
        InboxParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }

    public function togglePin(InboxConversation $conversation, User $user): bool
    {
        $participant = InboxParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->first();
        if (! $participant) return false;

        $participant->update(['pinned' => ! $participant->pinned]);
        return $participant->pinned;
    }

    /**
     * Total unread messages across all conversations for a user (for sidebar/nav badge).
     */
    public function totalUnreadFor(User $user): int
    {
        return InboxParticipant::query()
            ->where('user_id', $user->id)
            ->whereHas('conversation.messages', function ($q) use ($user) {
                $q->where('user_id', '!=', $user->id);
            })
            ->get()
            ->sum(function ($participant) use ($user) {
                $lastRead = $participant->last_read_at;
                return InboxMessage::where('conversation_id', $participant->conversation_id)
                    ->where('user_id', '!=', $user->id)
                    ->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))
                    ->count();
            });
    }

    private function ensureInboxAttachmentsFolder(): ?string
    {
        $folderId = Setting::get('drive_folder_inbox_attachments');
        if ($folderId) return $folderId;

        // Best-effort create under the configured root or assets folder
        $parent = Setting::get('drive_folder_root') ?: Setting::get('drive_folder_assets');
        if (! $parent) return null;

        try {
            $newId = $this->drive->createFolder('Inbox Attachments', $parent);
            Setting::put('drive_folder_inbox_attachments', $newId);
            return $newId;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function extractMentions(string $body): array
    {
        preg_match_all('/@(\w+)/', $body, $matches);
        if (empty($matches[1])) return [];
        return User::whereIn('username', $matches[1])->pluck('id')->toArray();
    }
}
