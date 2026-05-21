<?php

namespace App\Http\Controllers\Writer;

use App\Exceptions\DriveException;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\ViralPackage;
use App\Models\ViralPackageAsset;
use App\Models\ViralPackageDeliverable;
use App\Services\GoogleDriveService;
use App\Services\ViralPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ViralPackageController extends Controller
{
    public function __construct(private readonly ViralPackageService $service) {}

    public function index(Request $request): View
    {
        // Show packages with at least one non-approved deliverable (i.e. something to work on).
        $packages = ViralPackage::query()
            ->with(['client', 'salesRep', 'deliverables'])
            ->where('status', 'active')
            ->whereHas('deliverables', fn ($q) => $q->whereIn('stage', ['pending', 'in_progress']))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->get('q'));
                $q->whereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('writer.viral-packages.index', compact('packages'));
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

        return view('writer.viral-packages.show', ['package' => $viralPackage]);
    }

    public function pickUp(ViralPackage $viralPackage, ViralPackageDeliverable $deliverable): RedirectResponse
    {
        $this->ensureBelongs($deliverable, $viralPackage);

        try {
            $this->service->pickUpDeliverable($deliverable);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Picked up {$deliverable->title}.");
    }

    public function submit(Request $request, ViralPackage $viralPackage, ViralPackageDeliverable $deliverable): RedirectResponse
    {
        $this->ensureBelongs($deliverable, $viralPackage);

        $validated = $request->validate([
            'file'  => ['required', 'file', 'max:204800'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->submitDeliverable($deliverable, $request->file('file'), $validated['notes'] ?? null);
        } catch (DriveException|WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Submit failed: ' . $e->getMessage());
        }

        return back()->with('success', "{$deliverable->title} submitted for review.");
    }

    public function downloadAsset(ViralPackage $viralPackage, ViralPackageAsset $asset, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        if ($asset->viral_package_id !== $viralPackage->id) {
            abort(404);
        }

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

    public function downloadAllAssets(ViralPackage $viralPackage, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        return $this->buildAssetsZip($viralPackage, $drive);
    }

    public function downloadDeliverable(ViralPackage $viralPackage, ViralPackageDeliverable $deliverable, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
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

        return response()->download($tempPath, $deliverable->drive_filename ?: $deliverable->title)->deleteFileAfterSend();
    }

    private function ensureBelongs(ViralPackageDeliverable $deliverable, ViralPackage $package): void
    {
        if ($deliverable->viral_package_id !== $package->id) {
            abort(404);
        }
    }

    private function buildAssetsZip(ViralPackage $package, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        $fileAssets = $package->assets->where('type', 'file')->filter(fn ($a) => ! empty($a->drive_file_id));
        $linkAssets = $package->assets->where('type', 'link');

        if ($fileAssets->isEmpty() && $linkAssets->isEmpty()) {
            return back()->with('error', 'No assets to download.');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'viral_assets_') . '.zip';
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
                // Handle duplicate filenames by appending (1), (2), etc.
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
}
