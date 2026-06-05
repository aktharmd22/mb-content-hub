<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStage;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleType;
use App\Models\Client;
use App\Models\StageHistory;
use App\Models\User;
use App\Models\ViralPackage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $totals = [
            'articles_submitted' => Article::count(),
            'articles_published' => Article::where('current_stage', ArticleStage::PUBLISHED->value)->count(),
            'clients_served'     => Article::distinct('client_id')->whereNotNull('client_id')->count('client_id'),
            'viral_packages'     => ViralPackage::count(),
            'viral_delivered'    => ViralPackage::where('status', 'completed')->count(),
            'total_users'        => User::where('is_active', true)->count(),
        ];

        $monthly = $this->articlesPerMonth(12);
        $weekly  = $this->articlesPerWeek(12);
        $yearly  = $this->articlesPerYear();

        $viralMonthly = $this->viralPackagesPerMonth(12);

        $topClients = $this->topClients(15);
        $salesPerf  = $this->salesPerformance();
        $writerPerf = $this->writerPerformance();
        $typeMix    = $this->articleTypeDistribution();

        $transitionStats = $this->stageTransitionStats();

        return view('admin.reports.index', compact(
            'totals',
            'monthly',
            'weekly',
            'yearly',
            'viralMonthly',
            'topClients',
            'salesPerf',
            'writerPerf',
            'typeMix',
            'transitionStats',
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $kind = $request->get('kind', 'monthly');
        $rows = match ($kind) {
            'monthly'      => $this->articlesPerMonth(24),
            'weekly'       => $this->articlesPerWeek(26),
            'yearly'       => $this->articlesPerYear(),
            'clients'      => $this->topClients(100),
            'sales'        => $this->salesPerformance(),
            'writers'      => $this->writerPerformance(),
            'viral'        => $this->viralPackagesPerMonth(24),
            default        => [],
        };

        $filename = "report-{$kind}-" . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows, $kind) {
            $out = fopen('php://output', 'w');
            if (empty($rows)) {
                fputcsv($out, ['No data']);
                fclose($out);
                return;
            }
            $first = is_array($rows[0]) ? $rows[0] : (array) $rows[0];
            fputcsv($out, array_keys($first));
            foreach ($rows as $row) {
                fputcsv($out, array_values(is_array($row) ? $row : (array) $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Time-series queries
    // ──────────────────────────────────────────────────────────────────────────

    private function articlesPerMonth(int $months): array
    {
        $start = now()->copy()->subMonths($months - 1)->startOfMonth();

        $published = Article::query()
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(published_at, '%Y-%m') as bucket, COUNT(*) as cnt")
            ->groupBy('bucket')
            ->pluck('cnt', 'bucket');

        $submitted = Article::query()
            ->where('submitted_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(submitted_at, '%Y-%m') as bucket, COUNT(*) as cnt")
            ->groupBy('bucket')
            ->pluck('cnt', 'bucket');

        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $d = $start->copy()->addMonths($i);
            $key = $d->format('Y-m');
            $out[] = [
                'period'    => $d->format('M Y'),
                'submitted' => (int) ($submitted[$key] ?? 0),
                'published' => (int) ($published[$key] ?? 0),
            ];
        }
        return $out;
    }

    private function articlesPerWeek(int $weeks): array
    {
        $start = now()->copy()->subWeeks($weeks - 1)->startOfWeek();

        $published = Article::query()
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $start)
            ->selectRaw("YEARWEEK(published_at, 3) as bucket, COUNT(*) as cnt")
            ->groupBy('bucket')
            ->pluck('cnt', 'bucket');

        $submitted = Article::query()
            ->where('submitted_at', '>=', $start)
            ->selectRaw("YEARWEEK(submitted_at, 3) as bucket, COUNT(*) as cnt")
            ->groupBy('bucket')
            ->pluck('cnt', 'bucket');

        $out = [];
        for ($i = 0; $i < $weeks; $i++) {
            $d = $start->copy()->addWeeks($i);
            $key = (int) $d->format('oW');
            $out[] = [
                'period'    => 'W' . $d->format('W') . ' ' . $d->format('M j'),
                'submitted' => (int) ($submitted[$key] ?? 0),
                'published' => (int) ($published[$key] ?? 0),
            ];
        }
        return $out;
    }

    private function articlesPerYear(): array
    {
        return Article::query()
            ->whereNotNull('submitted_at')
            ->selectRaw("YEAR(submitted_at) as year, COUNT(*) as submitted, SUM(CASE WHEN current_stage = 'published' THEN 1 ELSE 0 END) as published")
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get()
            ->map(fn ($r) => [
                'year'      => (int) $r->year,
                'submitted' => (int) $r->submitted,
                'published' => (int) $r->published,
            ])
            ->toArray();
    }

    private function viralPackagesPerMonth(int $months): array
    {
        $start = now()->copy()->subMonths($months - 1)->startOfMonth();

        $created = ViralPackage::query()
            ->where('created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as bucket, COUNT(*) as cnt")
            ->groupBy('bucket')
            ->pluck('cnt', 'bucket');

        $completed = ViralPackage::query()
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(completed_at, '%Y-%m') as bucket, COUNT(*) as cnt")
            ->groupBy('bucket')
            ->pluck('cnt', 'bucket');

        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $d = $start->copy()->addMonths($i);
            $key = $d->format('Y-m');
            $out[] = [
                'period'    => $d->format('M Y'),
                'created'   => (int) ($created[$key] ?? 0),
                'completed' => (int) ($completed[$key] ?? 0),
            ];
        }
        return $out;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Breakdown queries
    // ──────────────────────────────────────────────────────────────────────────

    private function topClients(int $limit): array
    {
        return Client::query()
            ->leftJoin('articles', 'articles.client_id', '=', 'clients.id')
            ->selectRaw('clients.id, clients.name, clients.company,
                         COUNT(articles.id) as total_articles,
                         SUM(CASE WHEN articles.current_stage = "published" THEN 1 ELSE 0 END) as published')
            ->groupBy('clients.id', 'clients.name', 'clients.company')
            ->havingRaw('COUNT(articles.id) > 0')
            ->orderByDesc('total_articles')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'name'           => $r->name,
                'company'        => $r->company ?? '',
                'total_articles' => (int) $r->total_articles,
                'published'      => (int) $r->published,
            ])
            ->toArray();
    }

    private function salesPerformance(): array
    {
        return User::query()
            ->where('users.role', 'sales')
            ->leftJoin('articles', 'articles.sales_rep_id', '=', 'users.id')
            ->selectRaw('users.id, users.name,
                         COUNT(articles.id) as submitted,
                         SUM(CASE WHEN articles.current_stage = "published" THEN 1 ELSE 0 END) as published')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('submitted')
            ->get()
            ->map(function ($r) {
                $submitted = (int) $r->submitted;
                $published = (int) $r->published;
                return [
                    'name'         => $r->name,
                    'submitted'    => $submitted,
                    'published'    => $published,
                    'success_rate' => $submitted > 0 ? round(($published / $submitted) * 100) : 0,
                ];
            })
            ->toArray();
    }

    private function writerPerformance(): array
    {
        return User::query()
            ->where('users.role', 'tech_team')
            ->leftJoin('articles', 'articles.tech_writer_id', '=', 'users.id')
            ->selectRaw('users.id, users.name,
                         COUNT(articles.id) as assigned,
                         SUM(CASE WHEN articles.current_stage = "published" THEN 1 ELSE 0 END) as published')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('published')
            ->get()
            ->map(function ($r) {
                $assigned  = (int) $r->assigned;
                $published = (int) $r->published;
                return [
                    'name'      => $r->name,
                    'assigned'  => $assigned,
                    'published' => $published,
                    'completion_rate' => $assigned > 0 ? round(($published / $assigned) * 100) : 0,
                ];
            })
            ->toArray();
    }

    private function articleTypeDistribution(): array
    {
        return ArticleType::query()
            ->leftJoin('articles', 'articles.article_type_id', '=', 'article_types.id')
            ->selectRaw('article_types.id, article_types.name,
                         COUNT(articles.id) as total,
                         SUM(CASE WHEN articles.current_stage = "published" THEN 1 ELSE 0 END) as published')
            ->groupBy('article_types.id', 'article_types.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'name'      => $r->name,
                'total'     => (int) $r->total,
                'published' => (int) $r->published,
            ])
            ->toArray();
    }

    private function stageTransitionStats(): array
    {
        $allTransitions = StageHistory::query()
            ->selectRaw('to_stage, COUNT(*) as cnt')
            ->groupBy('to_stage')
            ->pluck('cnt', 'to_stage');

        return [
            'total_corrections' => (int) ($allTransitions[ArticleStage::REVISIONS->value] ?? 0),
            'total_approvals'   => (int) ($allTransitions[ArticleStage::APPROVED->value] ?? 0),
            'total_published'   => (int) ($allTransitions[ArticleStage::PUBLISHED->value] ?? 0),
            'total_assignments' => (int) ($allTransitions[ArticleStage::ASSIGNED->value] ?? 0),
            'total_transitions' => (int) $allTransitions->sum(),
        ];
    }
}
