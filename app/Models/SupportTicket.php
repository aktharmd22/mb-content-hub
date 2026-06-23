<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'subject', 'description', 'priority', 'status',
        'reporter_id', 'assignee_id',
        'attachment_path', 'attachment_name', 'attachment_size', 'attachment_mime',
        'last_activity_at', 'resolved_at', 'closed_at',
    ];

    public function hasAttachment(): bool
    {
        return ! empty($this->attachment_path);
    }

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'resolved_at'      => 'datetime',
            'closed_at'        => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class, 'ticket_id')->orderBy('created_at');
    }

    public function lastReply()
    {
        return $this->hasOne(SupportTicketReply::class, 'ticket_id')->latestOfMany();
    }

    public function canBeViewedBy(User $user): bool
    {
        if ($user->isAdmin()) return true;
        return $this->reporter_id === $user->id || $this->assignee_id === $user->id;
    }

    public function canBeRepliedBy(User $user): bool
    {
        return $this->canBeViewedBy($user) && ! in_array($this->status, ['closed']);
    }

    public function priorityLabel(): string
    {
        return ucfirst($this->priority);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open'          => 'Open',
            'in_progress'   => 'In Progress',
            'waiting_user'  => 'Waiting on user',
            'resolved'      => 'Resolved',
            'closed'        => 'Closed',
            default         => ucfirst($this->status),
        };
    }
}
