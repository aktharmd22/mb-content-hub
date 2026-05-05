<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStage;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Client;
use App\Models\StageHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        // 1. Articles per month (last 6 months including current)
        $articlesPerMonth = $this->articlesPerMonth();

        // 2. Average time per stage (over last 90 days)
        $avgTimePerStage = $this->avgTimePerStage();

        // 3. Writer performance comparison (last 30 days)
        $writerPerformance = $this->writerPerformance();

        // 4. Client volume breakdown (top 10 by articles all-time)
        $clientVolume = Client::withCount('articles')
            ->having('articles_count', '>', 0)
            ->orderByDesc('articles_count')
            ->limit(10)
            ->get();

        // 5. Bottlenecks (articles stuck > 3 days, by stage)
        $bottlenecks = Article::whereNot('current_stage', ArticleStage::PUBLISHED->value)
            ->whereNotNull('stage_entered_at')
            ->where('stage_entered_at', '<=', now()->subDays(3))
            ->selectRaw('current_stage, COUNT(*) as cnt')
            ->groupBy('current_stage')
            ->pluck('cnt', 'current_stage');

        // Summary KPIs
        $thisMonth = end($articlesPerMonth);
        $lastMonth = $articlesPerMonth[count($articlesPerMonth) - 2] ?? ['count' => 0];
        $kpis = [
            'this_month'      => $thisMonth['count'] ?? 0,
            'last_month'      => $lastMonth['count'] ?? 0,
            'change_pct'      => ($lastMonth['count'] ?? 0) > 0
                ? round((($thisMonth['count'] - $lastMonth['count']) / $lastMonth['count']) * 100)
                : null,
            'total_published' => Article::whereNotNull('published_at')->count(),
            'avg_cycle_days'  => $this->avgCycleDays(),
            'stuck_total'     => (int) $bottlenecks->sum(),
        ];

        return view('admin.analytics.index', compact(
            'articlesPerMonth',
            'avgTimePerStage',
            'writerPerformance',
            'clientVolume',
            'bottlenecks',
            'kpis',
        ));
    }

    /** Average days from submission to publish, last 90 days. */
    private function avgCycleDays(): ?float
    {
        $articles = Article::whereNotNull('published_at')
            ->where('published_at', '>=', now()->subDays(90))
            ->whereNotNull('submitted_at')
            ->get(['submitted_at', 'published_at']);

        if ($articles->isEmpty()) return null;

        $total = $articles->sum(fn ($a) => $a->submitted_at->diffInDays($a->published_at, true));
        return round($total / $articles->count(), 1);
    }

    /** @return array<int,array{label:string,count:int,pct:int}> */
    private function articlesPerMonth(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->copy()->subMonths($i)->startOfMonth();
            $end   = now()->copy()->subMonths($i)->endOfMonth();
            $count = Article::whereBetween('submitted_at', [$start, $end])->count();
            $months[] = ['label' => $start->format('M'), 'count' => $count];
        }

        $max = max(1, max(array_column($months, 'count')));
        foreach ($months as &$m) {
            $m['pct'] = max(2, (int) round($m['count'] / $max * 100));
        }
        return $months;
    }

    /** @return array<string,array{stage:ArticleStage,avg_days:?float}> */
    private function avgTimePerStage(): array
    {
        $since = now()->subDays(90);
        $result = [];

        foreach (ArticleStage::cases() as $stage) {
            // For each transition INTO this stage, find the matching transition OUT
            // and compute duration. Note: PUBLISHED has no out-transition, skip.
            if ($stage === ArticleStage::PUBLISHED) {
                $result[$stage->value] = ['stage' => $stage, 'avg_days' => null];
                continue;
            }

            $entries = StageHistory::where('to_stage', $stage->value)
                ->where('changed_at', '>=', $since)
                ->orderBy('article_id')
                ->orderBy('changed_at')
                ->get();

            $diffs = [];
            foreach ($entries as $entry) {
                $next = StageHistory::where('article_id', $entry->article_id)
                    ->where('changed_at', '>', $entry->changed_at)
                    ->orderBy('changed_at')
                    ->first();

                if ($next) {
                    $diffs[] = $entry->changed_at->diffInDays($next->changed_at, true);
                }
            }

            $result[$stage->value] = [
                'stage'    => $stage,
                'avg_days' => $diffs ? round(array_sum($diffs) / count($diffs), 1) : null,
            ];
        }

        return $result;
    }

    /** @return \Illuminate\Support\Collection */
    private function writerPerformance(): \Illuminate\Support\Collection
    {
        $since = now()->subDays(30);

        return User::where('role', 'tech_team')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (User $writer) use ($since) {
                $reviewSubmissions = StageHistory::where('changed_by', $writer->id)
                    ->where('to_stage', ArticleStage::INTERNAL_REVIEW->value)
                    ->where('changed_at', '>=', $since)
                    ->with('article.history')
                    ->get();

                $diffs = [];
                foreach ($reviewSubmissions as $h) {
                    $start = $h->article->history
                        ->where('to_stage', ArticleStage::IN_PROGRESS->value)
                        ->where('changed_at', '<', $h->changed_at)
                        ->sortByDesc('changed_at')
                        ->first();
                    if ($start) {
                        $diffs[] = $start->changed_at->diffInDays($h->changed_at, true);
                    }
                }

                $revisionsBack = StageHistory::where('from_stage', ArticleStage::INTERNAL_REVIEW->value)
                    ->where('to_stage', ArticleStage::REVISIONS->value)
                    ->where('changed_at', '>=', $since)
                    ->whereHas('article', fn ($q) => $q->where('tech_writer_id', $writer->id))
                    ->count();

                $reviewCount = $reviewSubmissions->count();

                return (object) [
                    'name'          => $writer->name,
                    'completed'     => $reviewCount,
                    'avg_days'      => $diffs ? round(array_sum($diffs) / count($diffs), 1) : null,
                    'revision_rate' => $reviewCount > 0 ? round(($revisionsBack / $reviewCount) * 100) : null,
                ];
            });
    }
}
