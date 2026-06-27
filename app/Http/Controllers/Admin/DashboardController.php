<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStage;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\StageHistory;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const STUCK_DAYS = 3;

    public function index(): View
    {
        $now = now();

        $stats = [
            'total_active' => Article::whereNot('current_stage', ArticleStage::PUBLISHED->value)->count(),
            'due_this_week' => Article::whereNot('current_stage', ArticleStage::PUBLISHED->value)
                ->whereNotNull('deadline')
                ->whereBetween('deadline', [$now->copy()->startOfDay(), $now->copy()->addDays(7)->endOfDay()])
                ->count(),
            'stuck' => Article::whereNot('current_stage', ArticleStage::PUBLISHED->value)
                ->whereNotNull('stage_entered_at')
                ->where('stage_entered_at', '<=', $now->copy()->subDays(self::STUCK_DAYS))
                ->count(),
            'published_this_month' => Article::whereNotNull('published_at')
                ->whereMonth('published_at', $now->month)
                ->whereYear('published_at', $now->year)
                ->count(),
        ];

        $stageCounts = Article::query()
            ->selectRaw('current_stage, COUNT(*) as cnt')
            ->groupBy('current_stage')
            ->pluck('cnt', 'current_stage');

        $maxCount = max(1, (int) ($stageCounts->max() ?? 1));

        $pipeline = collect(ArticleStage::cases())->map(fn (ArticleStage $stage) => [
            'stage' => $stage,
            'count' => (int) ($stageCounts[$stage->value] ?? 0),
            'pct'   => max(2, round(((int) ($stageCounts[$stage->value] ?? 0)) / $maxCount * 100)),
        ]);

        // Filter out orphaned history rows whose article was deleted (soft- or hard-delete).
        // Without this, the dashboard 500s on `$h->article->article_code` for missing relations.
        $recentActivity = StageHistory::with(['article:id,article_code,title', 'changedBy:id,name,role'])
            ->whereHas('article')
            ->orderByDesc('changed_at')
            ->limit(15)
            ->get();

        $stuckArticles = Article::whereNot('current_stage', ArticleStage::PUBLISHED->value)
            ->whereNotNull('stage_entered_at')
            ->where('stage_entered_at', '<=', $now->copy()->subDays(self::STUCK_DAYS))
            ->with(['client', 'techWriter'])
            ->orderBy('stage_entered_at')
            ->limit(10)
            ->get();

        // Viral package overview — business-wide
        $viral = [
            'stats' => [
                ['label' => 'Active',         'value' => \App\Models\ViralPackage::where('status', 'active')->count()],
                ['label' => 'In review',      'value' => \App\Models\ViralPackageDeliverable::whereHas('package', fn ($q) => $q->where('status', 'active'))->where('stage', 'review')->count()],
                ['label' => 'Completed (mo)', 'value' => \App\Models\ViralPackage::where('status', 'completed')->where('completed_at', '>=', $now->copy()->startOfMonth())->count()],
            ],
            'packages' => \App\Models\ViralPackage::where('status', 'active')
                ->with(['client', 'deliverables', 'techTeam'])->orderByDesc('created_at')->limit(5)->get(),
        ];
        $viralRole = 'admin';

        return view('admin.dashboard', compact('stats', 'pipeline', 'recentActivity', 'stuckArticles', 'viral', 'viralRole'));
    }
}
