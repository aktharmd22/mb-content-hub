<?php

namespace App\Http\Controllers\Lead;

use App\Enums\ArticleStage;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\StageHistory;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const STUCK_DAYS_THRESHOLD = 3;

    public function index(): View
    {
        $userId    = auth()->id();
        $now       = now();
        $startWeek = $now->copy()->startOfWeek();

        $stats = [
            'pending_reviews' => Article::where('current_stage', ArticleStage::INTERNAL_REVIEW->value)->count(),
            'approved_this_week' => StageHistory::where('changed_by', $userId)
                ->where('to_stage', ArticleStage::CLIENT_APPROVAL->value)
                ->where('changed_at', '>=', $startWeek)
                ->count(),
            'sent_for_revision_this_week' => StageHistory::where('changed_by', $userId)
                ->where('from_stage', ArticleStage::INTERNAL_REVIEW->value)
                ->where('to_stage', ArticleStage::REVISIONS->value)
                ->where('changed_at', '>=', $startWeek)
                ->count(),
            'team_active' => Article::whereIn('current_stage', [
                ArticleStage::ASSIGNED->value,
                ArticleStage::IN_PROGRESS->value,
                ArticleStage::REVISIONS->value,
                ArticleStage::INTERNAL_REVIEW->value,
            ])->count(),
        ];

        $reviewQueue = Article::where('current_stage', ArticleStage::INTERNAL_REVIEW->value)
            ->with(['client', 'techWriter'])
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->limit(10)
            ->get();

        $teamWorkload = User::where('role', 'tech_team')
            ->where('is_active', true)
            ->withCount([
                'articlesAsTechWriter as assigned_count' => fn ($q) => $q->where('current_stage', ArticleStage::ASSIGNED->value),
                'articlesAsTechWriter as in_progress_count' => fn ($q) => $q->where('current_stage', ArticleStage::IN_PROGRESS->value),
                'articlesAsTechWriter as revisions_count' => fn ($q) => $q->where('current_stage', ArticleStage::REVISIONS->value),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (User $u) {
                $u->total_active = $u->assigned_count + $u->in_progress_count + $u->revisions_count;
                return $u;
            });

        $stuck = Article::whereIn('current_stage', [
                ArticleStage::INTERNAL_REVIEW->value,
                ArticleStage::ASSIGNED->value,
            ])
            ->whereNotNull('stage_entered_at')
            ->where('stage_entered_at', '<=', $now->copy()->subDays(self::STUCK_DAYS_THRESHOLD))
            ->with(['client', 'techWriter'])
            ->orderBy('stage_entered_at')
            ->get();

        return view('lead.dashboard', compact('stats', 'reviewQueue', 'teamWorkload', 'stuck'));
    }
}
