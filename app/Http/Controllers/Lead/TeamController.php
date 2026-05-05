<?php

namespace App\Http\Controllers\Lead;

use App\Enums\ArticleStage;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\StageHistory;
use App\Models\User;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $since   = now()->subDays(30);
        $writers = User::where('role', 'tech_team')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $performance = $writers->map(function (User $writer) use ($since) {
            $reviewSubmissions = StageHistory::where('changed_by', $writer->id)
                ->where('to_stage', ArticleStage::INTERNAL_REVIEW->value)
                ->where('changed_at', '>=', $since)
                ->with('article.history')
                ->get();

            // Average days from in_progress -> internal_review for last 30 days.
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
            $avgDays = $diffs ? round(array_sum($diffs) / count($diffs), 1) : null;

            // Revision rate = revisions sent back / completed reviews
            $revisionsBack = StageHistory::where('from_stage', ArticleStage::INTERNAL_REVIEW->value)
                ->where('to_stage', ArticleStage::REVISIONS->value)
                ->where('changed_at', '>=', $since)
                ->whereHas('article', fn ($q) => $q->where('tech_writer_id', $writer->id))
                ->count();

            $reviewCount = $reviewSubmissions->count();
            $revisionRate = $reviewCount > 0 ? round(($revisionsBack / $reviewCount) * 100) : null;

            $activeCount = Article::where('tech_writer_id', $writer->id)
                ->whereIn('current_stage', [
                    ArticleStage::ASSIGNED->value,
                    ArticleStage::IN_PROGRESS->value,
                    ArticleStage::REVISIONS->value,
                ])
                ->count();

            return [
                'writer'        => $writer,
                'completed'     => $reviewCount,
                'avg_days'      => $avgDays,
                'revisions'     => $revisionsBack,
                'revision_rate' => $revisionRate,
                'active'        => $activeCount,
            ];
        });

        // Bottleneck: articles stuck in any stage > 3 days
        $bottlenecks = Article::whereIn('current_stage', [
                ArticleStage::ASSIGNED->value,
                ArticleStage::IN_PROGRESS->value,
                ArticleStage::INTERNAL_REVIEW->value,
                ArticleStage::REVISIONS->value,
                ArticleStage::CLIENT_APPROVAL->value,
            ])
            ->whereNotNull('stage_entered_at')
            ->where('stage_entered_at', '<=', now()->subDays(3))
            ->with(['client', 'techWriter'])
            ->orderBy('stage_entered_at')
            ->get();

        return view('lead.team.index', compact('performance', 'bottlenecks'));
    }
}
