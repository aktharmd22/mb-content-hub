<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Models\ViralPackage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ViralPackageController extends Controller
{
    public function index(Request $request): View
    {
        $packages = ViralPackage::query()
            ->with(['client', 'salesRep', 'deliverables'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->when($request->filled('sales_rep_id'), fn ($q) => $q->where('sales_rep_id', $request->get('sales_rep_id')))
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->get('client_id')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->get('q'));
                $q->whereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'active'             => ViralPackage::where('status', 'active')->count(),
            'completed_month'    => ViralPackage::where('status', 'completed')
                                        ->where('completed_at', '>=', now()->startOfMonth())
                                        ->count(),
            'total_completed'    => ViralPackage::where('status', 'completed')->count(),
        ];

        $salesReps = User::where('role', 'sales')->orderBy('name')->get(['id', 'name']);
        $clients   = Client::orderBy('name')->get(['id', 'name']);

        return view('admin.viral-packages.index', compact('packages', 'stats', 'salesReps', 'clients'));
    }

    public function show(ViralPackage $viralPackage): View
    {
        $viralPackage->load([
            'client',
            'salesRep',
            'assets.creator',
            'deliverables.assignee',
            'deliverables.history.changedBy',
        ]);

        return view('admin.viral-packages.show', ['package' => $viralPackage]);
    }
}
