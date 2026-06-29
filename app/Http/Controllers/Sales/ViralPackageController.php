<?php

namespace App\Http\Controllers\Sales;

use App\Exceptions\DriveException;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ViralPackage;
use App\Models\ViralPackageAsset;
use App\Models\ViralPackageDeliverable;
use App\Services\GoogleDriveService;
use App\Services\ViralPackageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ViralPackageController extends Controller
{
    public function __construct(private readonly ViralPackageService $service) {}

    public function index(Request $request): View
    {
        $packages = ViralPackage::query()
            ->with(['client', 'salesRep', 'deliverables'])
            ->when(! auth()->user()->isAdmin(), fn ($q) => $q->where('sales_rep_id', auth()->id()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->get('q'));
                $q->whereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('sales.viral-packages.index', compact('packages'));
    }

    public function create(): View
    {
        // Exclude clients that already have an active viral package — one per client, business-wide.
        $takenClientIds = ViralPackage::where('status', 'active')->pluck('client_id');
        $clients = Client::orderBy('name')
            ->whereNotIn('id', $takenClientIds)
            ->get();

        $takenCount = $takenClientIds->count();

        $techTeam = \App\Models\User::where('role', 'tech_team')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('sales.viral-packages.create', compact('clients', 'techTeam', 'takenCount'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id'           => ['required', 'integer', 'exists:clients,id'],
            'tech_team_id'        => ['required', 'integer', 'exists:users,id'],
            'include_landing_page' => ['nullable', 'boolean'],
            'assets'              => ['nullable', 'array'],
            'assets.*.type'       => ['nullable', 'in:file,link'],
            'assets.*.name'       => ['nullable', 'string', 'max:255'],
            'assets.*.url'        => ['nullable', 'url', 'max:500'],
            'assets.*.file'       => ['nullable', 'file', 'max:204800'],
        ]);

        $assets = $this->buildAssetsPayload($request);

        try {
            $package = $this->service->createPackage(
                (int) $validated['client_id'],
                (int) $validated['tech_team_id'],
                $assets,
                null,
                $request->boolean('include_landing_page')
            );
        } catch (DriveException|WorkflowException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('error', 'Could not create package: ' . $e->getMessage());
        }

        return redirect()
            ->route('sales.viral-packages.show', $package)
            ->with('success', "Viral package created for {$package->client->name} (assigned to {$package->techTeam?->name}).");
    }

    public function reassign(Request $request, ViralPackage $viralPackage): RedirectResponse
    {
        $this->ensureOwn($viralPackage);

        $validated = $request->validate([
            'tech_team_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $this->service->reassignTechTeam($viralPackage, (int) $validated['tech_team_id']);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Tech team member reassigned.');
    }

    public function show(ViralPackage $viralPackage): View
    {
        $this->ensureOwn($viralPackage);

        $viralPackage->load([
            'client',
            'salesRep',
            'techTeam',
            'assets.creator',
            'deliverables.assignee',
            'deliverables.history.changedBy',
            'deliverables.correctionAssets',
        ]);

        $techTeam = \App\Models\User::where('role', 'tech_team')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('sales.viral-packages.show', ['package' => $viralPackage, 'techTeam' => $techTeam]);
    }

    public function addAssets(Request $request, ViralPackage $viralPackage): RedirectResponse
    {
        $this->ensureOwn($viralPackage);

        if ($viralPackage->isCompleted()) {
            return back()->with('error', 'Cannot add assets to a completed package.');
        }

        $request->validate([
            'assets'        => ['required', 'array', 'min:1'],
            'assets.*.type' => ['required', 'in:file,link'],
            'assets.*.url'  => ['nullable', 'url', 'max:500'],
            'assets.*.file' => ['nullable', 'file', 'max:204800'],
        ]);

        $assets = $this->buildAssetsPayload($request);

        try {
            $this->service->attachAdditionalAssets($viralPackage, $assets);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Could not upload assets: ' . $e->getMessage());
        }

        return back()->with('success', count($assets) . ' asset(s) added.');
    }

    public function addPost(ViralPackage $viralPackage, Request $request): RedirectResponse
    {
        $this->ensureOwn($viralPackage);
        $kind = $request->input('kind') === 'reel' ? 'reel' : 'social_post';

        try {
            $deliverable = $this->service->addDeliverable($viralPackage, $kind);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$deliverable->title} added.");
    }

    public function removePost(ViralPackage $viralPackage, ViralPackageDeliverable $deliverable): RedirectResponse
    {
        $this->ensureOwn($viralPackage);
        $this->ensureBelongs($deliverable, $viralPackage);

        try {
            $this->service->removeDeliverable($deliverable);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$deliverable->title} removed.");
    }

    public function approveDeliverable(ViralPackage $viralPackage, ViralPackageDeliverable $deliverable): RedirectResponse
    {
        $this->ensureOwn($viralPackage);
        $this->ensureBelongs($deliverable, $viralPackage);

        try {
            $this->service->approveDeliverable($deliverable);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$deliverable->title} approved.");
    }

    public function requestCorrection(Request $request, ViralPackage $viralPackage, ViralPackageDeliverable $deliverable): RedirectResponse
    {
        $this->ensureOwn($viralPackage);
        $this->ensureBelongs($deliverable, $viralPackage);

        $validated = $request->validate([
            'reason'              => ['required', 'string', 'max:1000'],
            'correction_assets'   => ['nullable', 'array'],
            'correction_assets.*.type' => ['nullable', 'in:file,link'],
            'correction_assets.*.url'  => ['nullable', 'url', 'max:500'],
            'correction_assets.*.file' => ['nullable', 'file', 'max:204800'],
        ]);

        $assets = $this->buildAssetsPayload($request, 'correction_assets');

        try {
            $this->service->requestCorrection($deliverable, $validated['reason'], $assets);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Correction requested for {$deliverable->title}.");
    }

    public function retryDriveSetup(ViralPackage $viralPackage): RedirectResponse
    {
        $this->ensureOwn($viralPackage);

        if ($viralPackage->drive_folder_id) {
            return back()->with('info', 'Drive folder already exists.');
        }

        $parentId = \App\Models\Setting::get('drive_folder_viral_packages');
        if (! $parentId) {
            return back()->with('error', 'Drive folder ID is not configured. Ask admin to set it in Settings → General.');
        }

        try {
            $this->service->retryDriveFolders($viralPackage);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Could not create Drive folder: ' . $e->getMessage());
        }

        return back()->with('success', 'Drive folder created for this package.');
    }

    public function markDelivered(ViralPackage $viralPackage): RedirectResponse
    {
        $this->ensureOwn($viralPackage);

        try {
            $this->service->markDelivered($viralPackage);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Package marked as delivered. 🎉');
    }

    public function downloadAsset(ViralPackage $viralPackage, ViralPackageAsset $asset, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        $this->ensureOwn($viralPackage);

        if ($asset->viral_package_id !== $viralPackage->id) {
            abort(404);
        }

        return $this->downloadOrRedirect($asset, $drive);
    }

    public function downloadAllAssets(ViralPackage $viralPackage, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        $this->ensureOwn($viralPackage);
        return $this->buildAssetsZip($viralPackage, $drive);
    }

    private function buildAssetsZip(ViralPackage $package, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        $fileAssets = $package->assets->where('type', 'file')->filter(fn ($a) => ! empty($a->drive_file_id));
        $linkAssets = $package->assets->where('type', 'link');

        if ($fileAssets->isEmpty() && $linkAssets->isEmpty()) {
            return back()->with('error', 'No assets to download.');
        }

        $zipBase = tempnam(sys_get_temp_dir(), 'viral_assets_');
        @unlink($zipBase); // tempnam created an empty file at this path; drop it so only the .zip remains
        $zipPath = $zipBase . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($zipPath);
            return back()->with('error', 'Could not create zip file.');
        }

        $tempFiles = [];
        $usedNames = [];

        foreach ($fileAssets as $asset) {
            $tempPath = tempnam(sys_get_temp_dir(), 'asset_');
            try {
                $drive->downloadFile($asset->drive_file_id, $tempPath);
                $name = $asset->original_filename ?: ($asset->name ?: 'asset_' . $asset->id);
                $baseName = pathinfo($name, PATHINFO_FILENAME);
                $ext      = pathinfo($name, PATHINFO_EXTENSION);
                $finalName = $name;
                $counter   = 1;
                while (in_array($finalName, $usedNames, true)) {
                    $finalName = $baseName . ' (' . $counter . ')' . ($ext ? '.' . $ext : '');
                    $counter++;
                }
                $usedNames[] = $finalName;
                $zip->addFile($tempPath, $finalName);
                $tempFiles[] = $tempPath;
            } catch (\Throwable $e) {
                report($e);
                @unlink($tempPath);
            }
        }

        if ($linkAssets->isNotEmpty()) {
            $linksContent = "Reference links\n===============\n\n";
            foreach ($linkAssets as $link) {
                $linksContent .= ($link->name ?: 'Link') . ': ' . $link->url . "\n";
            }
            $zip->addFromString('Links.txt', $linksContent);
        }

        $zip->close();

        foreach ($tempFiles as $tf) {
            @unlink($tf);
        }

        $clientName   = $package->client?->name ?? 'package';
        $downloadName = preg_replace('/[^A-Za-z0-9 _-]/', '_', $clientName) . ' assets.zip';

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend();
    }

    public function downloadDeliverable(ViralPackage $viralPackage, ViralPackageDeliverable $deliverable, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        $this->ensureOwn($viralPackage);
        $this->ensureBelongs($deliverable, $viralPackage);

        if (! $deliverable->drive_file_id) {
            return back()->with('error', 'No file has been uploaded yet.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'viral_');
        try {
            $drive->downloadFile($deliverable->drive_file_id, $tempPath);
        } catch (DriveException $e) {
            @unlink($tempPath);
            return back()->with('error', $e->getMessage());
        }

        // ?inline=1 serves the file inline (for <img> previews) instead of forcing download.
        $disposition = request()->boolean('inline') ? 'inline' : 'attachment';
        $headers = $deliverable->mime_type ? ['Content-Type' => $deliverable->mime_type] : [];

        return response()->download($tempPath, $deliverable->drive_filename ?: $deliverable->title, $headers, $disposition)->deleteFileAfterSend();
    }

    public function destroy(ViralPackage $viralPackage, GoogleDriveService $drive): RedirectResponse
    {
        $this->ensureOwn($viralPackage);

        // Best-effort cleanup of Drive folder
        if ($viralPackage->drive_folder_id) {
            try {
                $drive->deleteFile($viralPackage->drive_folder_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $clientName = $viralPackage->client?->name ?? 'package';
        // Hard delete: the Drive folder is permanently removed above, so a soft-deleted shell
        // would be unrecoverable anyway. forceDelete triggers the FK cascade to clear
        // deliverables, assets, and history instead of leaving orphaned rows.
        $viralPackage->forceDelete();

        return redirect()
            ->route('sales.viral-packages.index')
            ->with('success', "Package for {$clientName} deleted.");
    }

    private function ensureOwn(ViralPackage $package): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if ($package->sales_rep_id !== $user->id) {
            throw new AuthorizationException('You can only act on your own packages.');
        }
    }

    private function ensureBelongs(ViralPackageDeliverable $deliverable, ViralPackage $package): void
    {
        if ($deliverable->viral_package_id !== $package->id) {
            abort(404);
        }
    }

    private function downloadOrRedirect(ViralPackageAsset $asset, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        if ($asset->type === 'link') {
            return $asset->url ? redirect()->away($asset->url) : back()->with('error', 'This asset has no URL.');
        }
        if (! $asset->drive_file_id) {
            return back()->with('error', 'This asset has no file attached.');
        }
        $tempPath = tempnam(sys_get_temp_dir(), 'viral_asset_');
        try {
            $drive->downloadFile($asset->drive_file_id, $tempPath);
        } catch (DriveException $e) {
            @unlink($tempPath);
            return back()->with('error', $e->getMessage());
        }
        $filename = $asset->original_filename ?: ($asset->name ?: 'asset');
        return response()->download($tempPath, $filename)->deleteFileAfterSend();
    }

    private function buildAssetsPayload(Request $request, string $key = 'assets'): array
    {
        $rows  = $request->input($key, []);
        $files = $request->file($key, []);
        $out   = [];

        foreach ($rows as $i => $row) {
            $type = $row['type'] ?? null;
            if (! in_array($type, ['file', 'link'], true)) continue;
            $name = $row['name'] ?? null;
            if ($type === 'link' && ! empty($row['url'])) {
                $out[] = ['type' => 'link', 'name' => $name, 'url' => $row['url']];
            } elseif ($type === 'file' && isset($files[$i]['file'])) {
                $out[] = ['type' => 'file', 'name' => $name, 'file' => $files[$i]['file']];
            }
        }
        return $out;
    }
}
