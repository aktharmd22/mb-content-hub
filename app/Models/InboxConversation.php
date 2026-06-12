<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InboxConversation extends Model
{
    protected $fillable = [
        'title',
        'context_type',
        'context_id',
        'created_by',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(InboxParticipant::class, 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(InboxMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function lastMessage()
    {
        return $this->hasOne(InboxMessage::class, 'conversation_id')->latest('created_at');
    }

    /**
     * Resolve the context model (Article or ViralPackage), if any.
     */
    public function context()
    {
        if (! $this->context_type || ! $this->context_id) return null;
        return match ($this->context_type) {
            'article'        => Article::find($this->context_id),
            'viral_package'  => ViralPackage::find($this->context_id),
            default          => null,
        };
    }

    public function isParticipant(?User $user): bool
    {
        if (! $user) return false;
        return $this->participants->contains('user_id', $user->id);
    }

    public function unreadCountFor(User $user): int
    {
        $participant = $this->participants->firstWhere('user_id', $user->id);
        if (! $participant) return 0;
        $lastRead = $participant->last_read_at;
        return $this->messages
            ->where('user_id', '!=', $user->id)
            ->when($lastRead, fn ($c) => $c->where('created_at', '>', $lastRead))
            ->count();
    }

    public function displayTitle(?User $viewer = null): string
    {
        if ($this->title) return $this->title;

        // Default: other participants' names
        $others = $this->participants
            ->when($viewer, fn ($c) => $c->where('user_id', '!=', $viewer->id))
            ->pluck('user.name')
            ->filter()
            ->take(3)
            ->implode(', ');

        return $others ?: 'Conversation';
    }
}
