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
            ->with(['client', 'salesRep', 'techTeam', 'deliverables'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->when($request->filled('sales_rep_id'), fn ($q) => $q->where('sales_rep_id', $request->get('sales_rep_id')))
            ->when($request->filled('tech_team_id'), fn ($q) => $q->where('tech_team_id', $request->get('tech_team_id')))
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
        $techTeam  = User::where('role', 'tech_team')->orderBy('name')->get(['id', 'name']);
        $clients   = Client::orderBy('name')->get(['id', 'name']);

        return view('admin.viral-packages.index', compact('packages', 'stats', 'salesReps', 'techTeam', 'clients'));
    }

    public function destroy(ViralPackage $viralPackage, \App\Services\GoogleDriveService $drive): \Illuminate\Http\RedirectResponse
    {
        if ($viralPackage->drive_folder_id) {
            try {
                $drive->deleteFile($viralPackage->drive_folder_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $clientName = $viralPackage->client?->name ?? 'package';
        // Hard delete so the FK cascade clears deliverables/assets/history; the Drive folder
        // is already permanently removed above, so a recoverable soft-delete would be useless.
        $viralPackage->forceDelete();

        return redirect()
            ->route('admin.viral-packages.index')
            ->with('success', "Package for {$clientName} deleted.");
    }

    public function show(ViralPackage $viralPackage): View
    {
        $viralPackage->load([
            'client',
            'salesRep',
            'techTeam',
            'assets.creator',
            'deliverables.assignee',
            'deliverables.history.changedBy',
        ]);

        $salesReps = User::whereIn('role', ['sales', 'admin'])->where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']);
        $techTeam  = User::where('role', 'tech_team')->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.viral-packages.show', [
            'package'   => $viralPackage,
            'salesReps' => $salesReps,
            'techTeam'  => $techTeam,
        ]);
    }

    public function downloadDeliverable(ViralPackage $viralPackage, \App\Models\ViralPackageDeliverable $deliverable, \App\Services\GoogleDriveService $drive)
    {
        if ($deliverable->viral_package_id !== $viralPackage->id) {
            abort(404);
        }
        if (! $deliverable->drive_file_id) {
            return back()->with('error', 'No file has been uploaded yet.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'viral_');
        try {
            $drive->downloadFile($deliverable->drive_file_id, $tempPath);
        } catch (\App\Exceptions\DriveException $e) {
            @unlink($tempPath);
            return back()->with('error', $e->getMessage());
        }

        $disposition = request()->boolean('inline') ? 'inline' : 'attachment';
        $headers = $deliverable->mime_type ? ['Content-Type' => $deliverable->mime_type] : [];

        return response()->download($tempPath, $deliverable->drive_filename ?: $deliverable->title, $headers, $disposition)->deleteFileAfterSend();
    }

    public function reassign(Request $request, ViralPackage $viralPackage, \App\Services\ViralPackageService $service): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'sales_rep_id' => ['nullable', 'integer', 'exists:users,id'],
            'tech_team_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        try {
            if (! empty($data['sales_rep_id']) && $data['sales_rep_id'] != $viralPackage->sales_rep_id) {
                $service->reassignSalesRep($viralPackage, (int) $data['sales_rep_id']);
            }
            if (! empty($data['tech_team_id']) && $data['tech_team_id'] != $viralPackage->tech_team_id) {
                $service->reassignTechTeam($viralPackage, (int) $data['tech_team_id']);
            }
        } catch (\App\Exceptions\WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Assignments updated.');
    }
}
