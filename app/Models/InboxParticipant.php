<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxParticipant extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'last_read_at',
        'pinned',
        'joined_at',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'joined_at'    => 'datetime',
        'pinned'       => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(InboxConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
