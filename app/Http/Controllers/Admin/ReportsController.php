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
        // Optional month filter (format: YYYY-MM)
        $selectedMonth = null;
        if ($request->filled('month') && preg_match('/^\d{4}-\d{2}$/', $request->get('month'))) {
            try {
                $selectedMonth = Carbon::createFromFormat('Y-m', $request->get('month'))->startOfMonth();
            } catch (\Throwable) {
                $selectedMonth = null;
            }
        }

        // List of months that actually have activity (for the filter dropdown)
        $availableMonths = $this->availableMonths();

        // Totals — scoped to month if filter is active, otherwise lifetime
        $totals = $this->totals($selectedMonth);

        if ($selectedMonth) {
            // Month-focused view: show daily breakdown for that month
            $dailyBreakdown = $this->articlesPerDay($selectedMonth);
            $monthly = null;
            $weekly  = null;
        } else {
            $dailyBreakdown = null;
            $monthly = $this->articlesPerMonth(12);
            $weekly  = $this->articlesPerWeek(12);
        }

        $yearly       = $this->articlesPerYear();
        $viralMonthly = $this->viralPackagesPerMonth(12);

        $topClients = $this->topClients(15, $selectedMonth);
        $salesPerf  = $this->salesPerformance($selectedMonth);
        $writerPerf = $this->writerPerformance($selectedMonth);
        $typeMix    = $this->articleTypeDistribution($selectedMonth);

        $transitionStats = $this->stageTransitionStats($selectedMonth);

        return view('admin.reports.index', compact(
            'totals',
            'monthly',
            'weekly',
            'yearly',
            'dailyBreakdown',
            'viralMonthly',
            'topClients',
            'salesPerf',
            'writerPerf',
            'typeMix',
            'transitionStats',
            'selectedMonth',
            'availableMonths',
        ));
    }

    private function availableMonths(): array
    {
        return Article::query()
            ->whereNotNull('submitted_at')
            ->selectRaw("DATE_FORMAT(submitted_at, '%Y-%m') as ym, DATE_FORMAT(submitted_at, '%M %Y') as label, COUNT(*) as cnt")
            ->groupBy('ym', 'label')
            ->orderByDesc('ym')
            ->get()
            ->map(fn ($r) => ['value' => $r->ym, 'label' => $r->label, 'count' => (int) $r->cnt])
            ->toArray();
    }

    private function totals(?Carbon $month): array
    {
        $articleQuery = Article::query();
        $viralQuery   = ViralPackage::query();
        $publishedQ   = Article::query()->where('current_stage', ArticleStage::PUBLISHED->value);

        if ($month) {
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();
            $articleQuery->whereBetween('submitted_at', [$start, $end]);
            $publishedQ->whereBetween('published_at', [$start, $end]);
            $viralQuery->whereBetween('created_at', [$start, $end]);
        }

        return [
            'articles_submitted' => $articleQuery->count(),
            'articles_published' => $publishedQ->count(),
            'clients_served'     => (clone $articleQuery)->distinct('client_id')->whereNotNull('client_id')->count('client_id'),
            'viral_packages'     => $viralQuery->count(),
            'viral_delivered'    => (clone $viralQuery)->where('status', 'completed')->count(),
            'total_users'        => User::where('is_active', true)->count(),
        ];
    }

    private function articlesPerDay(Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();
        $daysInMonth = $month->daysInMonth;

        $submitted = Article::query()
            ->whereBetween('submitted_at', [$start, $end])
            ->selectRaw("DAY(submitted_at) as day, COUNT(*) as cnt")
            ->groupBy('day')
            ->pluck('cnt', 'day');

        $published = Article::query()
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$start, $end])
            ->selectRaw("DAY(published_at) as day, COUNT(*) as cnt")
            ->groupBy('day')
            ->pluck('cnt', 'day');

        $out = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $out[] = [
                'period'    => $d,
                'submitted' => (int) ($submitted[$d] ?? 0),
                'published' => (int) ($published[$d] ?? 0),
            ];
        }
        return $out;
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

    private function topClients(int $limit, ?Carbon $month = null): array
    {
        $query = Client::query()
            ->leftJoin('articles', 'articles.client_id', '=', 'clients.id');

        if ($month) {
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();
            $query->whereBetween('articles.submitted_at', [$start, $end]);
        }

        return $query
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

    private function salesPerformance(?Carbon $month = null): array
    {
        $query = User::query()
            ->where('users.role', 'sales')
            ->leftJoin('articles', 'articles.sales_rep_id', '=', 'users.id');

        if ($month) {
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();
            $query->whereBetween('articles.submitted_at', [$start, $end]);
        }

        return $query
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

    private function writerPerformance(?Carbon $month = null): array
    {
        $query = User::query()
            ->where('users.role', 'tech_team')
            ->leftJoin('articles', 'articles.tech_writer_id', '=', 'users.id');

        if ($month) {
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();
            $query->whereBetween('articles.submitted_at', [$start, $end]);
        }

        return $query
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

    private function articleTypeDistribution(?Carbon $month = null): array
    {
        $query = ArticleType::query()
            ->leftJoin('articles', 'articles.article_type_id', '=', 'article_types.id');

        if ($month) {
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();
            $query->whereBetween('articles.submitted_at', [$start, $end]);
        }

        return $query
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

    private function stageTransitionStats(?Carbon $month = null): array
    {
        $query = StageHistory::query();

        if ($month) {
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();
            $query->whereBetween('changed_at', [$start, $end]);
        }

        $allTransitions = $query
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
