<?php

namespace App\Models;

use App\Enums\ArticleStage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'article_code',
        'title',
        'client_id',
        'article_type_id',
        'sales_rep_id',
        'tech_writer_id',
        'tech_lead_id',
        'current_stage',
        'priority',
        'deadline',
        'word_count_target',
        'source_drive_file_id',
        'current_drive_file_id',
        'assets_folder_drive_id',
        'assets_folder_name',
        'submitted_at',
        'published_at',
        'published_url',
        'notes',
        'stage_entered_at',
    ];

    protected function casts(): array
    {
        return [
            'current_stage'     => ArticleStage::class,
            'deadline'          => 'date',
            'submitted_at'      => 'datetime',
            'published_at'      => 'datetime',
            'stage_entered_at'  => 'datetime',
            'word_count_target' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function articleType(): BelongsTo
    {
        return $this->belongsTo(ArticleType::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function techWriter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tech_writer_id');
    }

    public function techLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tech_lead_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(StageHistory::class)->orderBy('changed_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at');
    }

    public function driveFiles(): HasMany
    {
        return $this->hasMany(DriveFile::class)->orderByDesc('uploaded_at');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ArticleAsset::class)->orderBy('id');
    }

    public function getDaysInStageAttribute(): int
    {
        if (! $this->stage_entered_at) {
            return 0;
        }
        return (int) $this->stage_entered_at->diffInDays(now());
    }

    public function getDaysUntilDeadlineAttribute(): ?int
    {
        if (! $this->deadline) {
            return null;
        }
        return (int) Carbon::today()->diffInDays($this->deadline, false);
    }

    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && ! $this->current_stage->isTerminal();
    }
}
