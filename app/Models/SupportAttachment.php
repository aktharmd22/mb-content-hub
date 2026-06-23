<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupportAttachment extends Model
{
    protected $fillable = [
        'attachable_id', 'attachable_type',
        'path', 'original_name', 'size', 'mime', 'uploaded_by',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;
        if ($bytes <= 0) return '';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0; $b = (float) $bytes;
        while ($b >= 1024 && $i < 3) { $b /= 1024; $i++; }
        return round($b, $i ? 1 : 0) . ' ' . $units[$i];
    }
}
