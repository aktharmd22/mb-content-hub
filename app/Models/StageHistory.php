<?php

namespace App\Models;

use App\Enums\ArticleStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StageHistory extends Model
{
    protected $fillable = [
        'article_id',
        'from_stage',
        'to_stage',
        'changed_by',
        'changed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'from_stage' => ArticleStage::class,
            'to_stage'   => ArticleStage::class,
            'changed_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
