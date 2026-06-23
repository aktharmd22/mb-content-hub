<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViralPackageDeliverable extends Model
{
    public const STAGES = ['pending', 'in_progress', 'review', 'approved'];

    protected $fillable = [
        'viral_package_id',
        'kind',
        'slot_number',
        'title',
        'stage',
        'assigned_to',
        'drive_file_id',
        'drive_filename',
        'mime_type',
        'file_size',
        'notes',
        'submitted_at',
        'approved_at',
    ];

    protected $casts = [
        'slot_number'  => 'integer',
        'file_size'    => 'integer',
        'submitted_at' => 'datetime',
        'approved_at'  => 'datetime',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(ViralPackage::class, 'viral_package_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ViralPackageHistory::class, 'deliverable_id')->orderBy('changed_at');
    }

    /** Reference files/links sales attached when requesting a correction. */
    public function correctionAssets(): HasMany
    {
        return $this->hasMany(ViralPackageAsset::class, 'deliverable_id')->latest();
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            'article'     => 'Article',
            'social_post' => 'Social post',
            'reel'        => 'Reel',
            default       => ucfirst($this->kind),
        };
    }

    public function stageLabel(): string
    {
        return match ($this->stage) {
            'pending'     => 'Pending',
            'in_progress' => 'In progress',
            'review'      => 'Ready for review',
            'approved'    => 'Approved',
            default       => ucfirst(str_replace('_', ' ', $this->stage)),
        };
    }

    public function stageColor(): string
    {
        return match ($this->stage) {
            'pending'     => 'gray',
            'in_progress' => 'indigo',
            'review'      => 'amber',
            'approved'    => 'emerald',
            default       => 'gray',
        };
    }
}
