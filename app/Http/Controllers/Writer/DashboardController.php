<?php

namespace App\Http\Controllers\Writer;

use App\Enums\ArticleStage;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\StageHistory;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();
        $now    = now();

        $activeStages = [
            ArticleStage::ASSIGNED->value,
            ArticleStage::IN_PROGRESS->value,
            ArticleStage::REVISIONS->value,
        ];

        $stats = [
            'active' => Article::where('tech_writer_id', $userId)
                ->whereIn('current_stage', $activeStages)
                ->count(),
            'due_this_week' => Article::where('tech_writer_id', $userId)
                ->whereIn('current_stage', $activeStages)
                ->whereNotNull('deadline')
                ->whereBetween('deadline', [$now->copy()->startOfDay(), $now->copy()->addDays(7)->endOfDay()])
                ->count(),
            'completed_this_month' => StageHistory::where('changed_by', $userId)
                ->where('to_stage', ArticleStage::INTERNAL_REVIEW->value)
                ->whereMonth('changed_at', $now->month)
                ->whereYear('changed_at', $now->year)
                ->count(),
            'avg_days' => $this->averageCompletionDays($userId),
        ];

        $queue = Article::where('tech_writer_id', $userId)
            ->whereIn('current_stage', $activeStages)
            ->with('client')
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->limit(10)
            ->get();

        $recentlyCompleted = Article::where('tech_writer_id', $userId)
            ->whereNotIn('current_stage', $activeStages)
            ->with('client')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        // New uploads from sales — visible to all tech team members so they know what's incoming
        $newUploads = Article::where('current_stage', ArticleStage::INBOX->value)
            ->with(['client', 'salesRep', 'articleType'])
            ->orderByDesc('submitted_at')
            ->limit(8)
            ->get();

        // Viral package overview — packages assigned to this tech member
        $assigned = fn () => \App\Models\ViralPackageDeliverable::whereHas('package', fn ($q) => $q->where('tech_team_id', $userId)->where('status', 'active'));
        $viral = [
            'stats' => [
                ['label' => 'Active packages', 'value' => \App\Models\ViralPackage::where('status', 'active')->where('tech_team_id', $userId)->count(), 'hint' => 'assigned to you', 'color' => 'indigo'],
                ['label' => 'To work on',      'value' => $assigned()->whereIn('stage', ['pending', 'in_progress'])->count(), 'hint' => 'deliverables pending', 'color' => 'amber'],
                ['label' => 'In review',       'value' => $assigned()->where('stage', 'review')->count(), 'hint' => 'awaiting sales', 'color' => 'blue'],
            ],
            'packages' => \App\Models\ViralPackage::where('status', 'active')->where('tech_team_id', $userId)
                ->with(['client', 'deliverables', 'techTeam'])->orderByDesc('created_at')->limit(5)->get(),
        ];
        $viralRole = 'writer';

        return view('writer.dashboard', compact('stats', 'queue', 'recentlyCompleted', 'newUploads', 'viral', 'viralRole'));
    }

    /**
     * Average days between IN_PROGRESS → INTERNAL_REVIEW for this writer's last 30 days.
     * Computed in PHP from history entries (small data volume, no need for SQL gymnastics).
     */
    private function averageCompletionDays(int $userId): ?float
    {
        $since = now()->subDays(30);

        $histories = StageHistory::where('changed_by', $userId)
            ->where('to_stage', ArticleStage::INTERNAL_REVIEW->value)
            ->where('changed_at', '>=', $since)
            ->with('article.history')
            ->get();

        if ($histories->isEmpty()) {
            return null;
        }

        $diffs = [];
        foreach ($histories as $h) {
            $startEntry = $h->article->history
                ->where('to_stage', ArticleStage::IN_PROGRESS->value)
                ->where('changed_at', '<', $h->changed_at)
                ->sortByDesc('changed_at')
                ->first();

            if ($startEntry) {
                $diffs[] = $startEntry->changed_at->diffInDays($h->changed_at, true);
            }
        }

        if (empty($diffs)) {
            return null;
        }

        return round(array_sum($diffs) / count($diffs), 1);
    }
}
