<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViralPackageAsset extends Model
{
    protected $fillable = [
        'viral_package_id',
        'deliverable_id',
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

    public function package(): BelongsTo
    {
        return $this->belongsTo(ViralPackage::class, 'viral_package_id');
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
