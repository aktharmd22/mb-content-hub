<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ViralPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'sales_rep_id',
        'tech_team_id',
        'status',
        'drive_folder_id',
        'drive_folder_name',
        'drive_assets_folder_id',
        'drive_deliverables_folder_id',
        'drive_article_folder_id',
        'drive_posts_folder_id',
        'drive_reel_folder_id',
        'drive_corrections_folder_id',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function techTeam(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tech_team_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ViralPackageAsset::class)->orderBy('id');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(ViralPackageDeliverable::class)->orderByRaw("FIELD(kind,'article','social_post','reel'), slot_number");
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function approvedCount(): int
    {
        return $this->deliverables->where('stage', 'approved')->count();
    }

    public function totalDeliverables(): int
    {
        return $this->deliverables->count();
    }

    public function progressPercent(): int
    {
        $total = $this->totalDeliverables();
        if ($total === 0) return 0;
        return (int) round(($this->approvedCount() / $total) * 100);
    }

    public function canBeMarkedDelivered(): bool
    {
        return $this->status === 'active'
            && $this->totalDeliverables() > 0
            && $this->approvedCount() === $this->totalDeliverables();
    }
}
