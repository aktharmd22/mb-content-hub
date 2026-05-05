<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleAsset extends Model
{
    protected $fillable = [
        'article_id',
        'type',
        'name',
        'drive_file_id',
        'original_filename',
        'mime_type',
        'file_size',
        'url',
        'created_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isImage(): bool
    {
        return $this->type === 'file' && str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return $this->type === 'file' && str_starts_with((string) $this->mime_type, 'video/');
    }
}
