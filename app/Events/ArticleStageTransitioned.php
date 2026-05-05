<?php

namespace App\Events;

use App\Enums\ArticleStage;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArticleStageTransitioned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Article $article,
        public readonly ?ArticleStage $fromStage,
        public readonly ArticleStage $toStage,
        public readonly ?User $actor,
        public readonly ?string $notes = null,
    ) {}
}
