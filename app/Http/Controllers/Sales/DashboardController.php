<?php

namespace App\Http\Controllers\Sales;

use App\Enums\ArticleStage;
use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();
        $now    = now();

        $stats = [
            'active' => Article::where('sales_rep_id', $userId)
                ->whereNot('current_stage', ArticleStage::PUBLISHED->value)
                ->count(),
            'pending_client' => Article::where('sales_rep_id', $userId)
                ->where('current_stage', ArticleStage::CLIENT_APPROVAL->value)
                ->count(),
            'approved_this_month' => Article::where('sales_rep_id', $userId)
                ->where('current_stage', ArticleStage::APPROVED->value)
                ->whereMonth('stage_entered_at', $now->month)
                ->whereYear('stage_entered_at', $now->year)
                ->count(),
            'published_this_month' => Article::where('sales_rep_id', $userId)
                ->whereNotNull('published_at')
                ->whereMonth('published_at', $now->month)
                ->whereYear('published_at', $now->year)
                ->count(),
        ];

        $recent = Article::where('sales_rep_id', $userId)
            ->with('client')
            ->orderByDesc('submitted_at')
            ->limit(5)
            ->get();

        $needsFollowup = Article::where('sales_rep_id', $userId)
            ->where('current_stage', ArticleStage::CLIENT_APPROVAL->value)
            ->with('client')
            ->orderBy('stage_entered_at')
            ->get();

        // Viral package overview — this sales rep's packages
        $myPackages = \App\Models\ViralPackage::where('status', 'active')->where('sales_rep_id', auth()->id())
            ->with(['client', 'deliverables', 'techTeam'])->orderByDesc('created_at')->get();
        $viral = [
            'stats' => [
                ['label' => 'Active packages',  'value' => $myPackages->count(), 'hint' => 'in progress', 'color' => 'indigo'],
                ['label' => 'Ready to deliver', 'value' => $myPackages->filter->canBeMarkedDelivered()->count(), 'hint' => 'all approved', 'color' => 'emerald'],
                ['label' => 'Delivered',        'value' => \App\Models\ViralPackage::where('sales_rep_id', auth()->id())->where('status', 'completed')->where('completed_at', '>=', now()->startOfMonth())->count(), 'hint' => 'this month', 'color' => 'gray'],
            ],
            'packages' => $myPackages->take(5)->values(),
        ];
        $viralRole = 'sales';

        return view('sales.dashboard', compact('stats', 'recent', 'needsFollowup', 'viral', 'viralRole'));
    }
}
