<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'body',
        'attachment_drive_file_id',
        'attachment_filename',
        'attachment_mime_type',
        'attachment_size',
        'mentions',
    ];

    protected $casts = [
        'mentions'          => 'array',
        'attachment_size'   => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(InboxConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasAttachment(): bool
    {
        return ! empty($this->attachment_drive_file_id) || ! empty($this->attachment_filename);
    }

    public function attachmentKind(): string
    {
        $m = (string) $this->attachment_mime_type;
        if (str_starts_with($m, 'image/')) return 'image';
        if (str_starts_with($m, 'video/')) return 'video';
        if (str_starts_with($m, 'audio/')) return 'audio';
        if ($m === 'application/pdf')      return 'pdf';
        return 'file';
    }
}
