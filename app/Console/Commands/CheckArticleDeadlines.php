<?php

namespace App\Console\Commands;

use App\Enums\ArticleStage;
use App\Models\Article;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\DeadlineApproachingNotification;
use App\Notifications\StuckArticleNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckArticleDeadlines extends Command
{
    protected $signature = 'articles:check-deadlines';

    protected $description = 'Notify writers/sales of approaching deadlines and admins about stuck articles';

    public function handle(): int
    {
        $this->info('Checking deadlines and stuck articles...');

        $deadlineNotified = $this->notifyApproachingDeadlines();
        $stuckNotified    = $this->notifyStuckArticles();

        $this->newLine();
        $this->line("  → {$deadlineNotified} deadline notification(s) sent");
        $this->line("  → {$stuckNotified} stuck-article notification(s) sent");
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }

    private function notifyApproachingDeadlines(): int
    {
        $count = 0;
        $cutoff = now()->copy()->addDays(2)->endOfDay();

        $articles = Article::whereIn('current_stage', [
                ArticleStage::ASSIGNED->value,
                ArticleStage::IN_PROGRESS->value,
                ArticleStage::REVISIONS->value,
                ArticleStage::INTERNAL_REVIEW->value,
                ArticleStage::CLIENT_APPROVAL->value,
            ])
            ->whereNotNull('deadline')
            ->where('deadline', '<=', $cutoff)
            ->with(['techWriter', 'techLead', 'salesRep'])
            ->get();

        foreach ($articles as $article) {
            $assignee = $this->primaryAssignee($article);
            if (! $assignee || ! $assignee->is_active) {
                continue;
            }

            $assignee->notify(new DeadlineApproachingNotification($article));
            $count++;
        }

        return $count;
    }

    private function notifyStuckArticles(): int
    {
        $threshold = (int) Setting::get('stuck_threshold_days', 3);

        $articles = Article::whereNotIn('current_stage', [ArticleStage::PUBLISHED->value])
            ->whereNotNull('stage_entered_at')
            ->where('stage_entered_at', '<=', now()->subDays($threshold))
            ->with(['techWriter', 'salesRep', 'client'])
            ->get();

        if ($articles->isEmpty()) {
            return 0;
        }

        $admins = User::where('role', 'admin')->where('is_active', true)->get();
        if ($admins->isEmpty()) {
            return 0;
        }

        foreach ($articles as $article) {
            $days = (int) $article->stage_entered_at->diffInDays(now());
            Notification::send($admins, new StuckArticleNotification($article, $days));
        }

        return $articles->count() * $admins->count();
    }

    private function primaryAssignee(Article $article): ?User
    {
        return match ($article->current_stage) {
            ArticleStage::CLIENT_APPROVAL => $article->salesRep,
            ArticleStage::INTERNAL_REVIEW => $article->techLead ?? $article->techWriter,
            default                        => $article->techWriter ?? $article->salesRep,
        };
    }
}
