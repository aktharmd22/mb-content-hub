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

    public function revertDeliverable(ViralPackage $viralPackage, \App\Models\ViralPackageDeliverable $deliverable, \App\Services\ViralPackageService $service): \Illuminate\Http\RedirectResponse
    {
        if ($deliverable->viral_package_id !== $viralPackage->id) {
            abort(404);
        }

        try {
            $service->revertApproval($deliverable);
        } catch (\App\Exceptions\WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$deliverable->title} re-opened — it's back in sales review.");
    }

    public function replaceDeliverable(Request $request, ViralPackage $viralPackage, \App\Models\ViralPackageDeliverable $deliverable, \App\Services\ViralPackageService $service): \Illuminate\Http\RedirectResponse
    {
        if ($deliverable->viral_package_id !== $viralPackage->id) {
            abort(404);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:262144'], // 256 MB (matches .user.ini)
        ], [
            'file.max'      => 'The file is too large. Maximum upload size is 256 MB.',
            'file.required' => 'Please choose a file to upload.',
        ]);

        try {
            $service->replaceFileAsAdmin($deliverable, $request->file('file'));
        } catch (\App\Exceptions\DriveException | \App\Exceptions\WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$deliverable->title} content updated.");
    }

    public function approveDeliverable(ViralPackage $viralPackage, \App\Models\ViralPackageDeliverable $deliverable, \App\Services\ViralPackageService $service): \Illuminate\Http\RedirectResponse
    {
        if ($deliverable->viral_package_id !== $viralPackage->id) {
            abort(404);
        }

        try {
            $service->approveDeliverable($deliverable);
        } catch (\App\Exceptions\WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$deliverable->title} approved.");
    }

    public function requestCorrection(Request $request, ViralPackage $viralPackage, \App\Models\ViralPackageDeliverable $deliverable, \App\Services\ViralPackageService $service): \Illuminate\Http\RedirectResponse
    {
        if ($deliverable->viral_package_id !== $viralPackage->id) {
            abort(404);
        }

        $validated = $request->validate([
            'reason'                    => ['required', 'string', 'max:1000'],
            'correction_assets'         => ['nullable', 'array'],
            'correction_assets.*.type'  => ['nullable', 'in:file,link'],
            'correction_assets.*.url'   => ['nullable', 'url', 'max:500'],
            'correction_assets.*.file'  => ['nullable', 'file', 'max:204800'],
        ]);

        $assets = $this->buildAssetsPayload($request, 'correction_assets');

        try {
            $service->requestCorrection($deliverable, $validated['reason'], $assets);
        } catch (\App\Exceptions\WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Correction requested for {$deliverable->title}.");
    }

    private function buildAssetsPayload(Request $request, string $key = 'assets'): array
    {
        $rows  = $request->input($key, []);
        $files = $request->file($key, []);
        $out   = [];

        foreach ($rows as $i => $row) {
            $type = $row['type'] ?? null;
            if (! in_array($type, ['file', 'link'], true)) {
                continue;
            }
            $name = $row['name'] ?? null;
            if ($type === 'link' && ! empty($row['url'])) {
                $out[] = ['type' => 'link', 'name' => $name, 'url' => $row['url']];
            } elseif ($type === 'file' && isset($files[$i]['file'])) {
                $out[] = ['type' => 'file', 'name' => $name, 'file' => $files[$i]['file']];
            }
        }

        return $out;
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
