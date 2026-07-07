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
        // Show every package assigned to this tech member (admins see all), including
        // delivered ones, so the team can see the delivered status. Active packages
        // (with work to do) are listed first, then delivered ones.
        $packages = ViralPackage::query()
            ->with(['client', 'salesRep', 'techTeam', 'deliverables'])
            ->when(! auth()->user()->isAdmin(), fn ($q) => $q->where(function ($w) {
                $w->whereHas('deliverables', fn ($d) => $d->where('assigned_to', auth()->id()))
                  ->orWhere('tech_team_id', auth()->id()); // legacy packages with no per-type owners yet
            }))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->get('q'));
                $q->whereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            })
            ->orderByRaw("FIELD(status,'active','completed')")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('writer.viral-packages.index', compact('packages'));
    }

    public function show(ViralPackage $viralPackage): View
    {
        $this->ensureAssigned($viralPackage);

        $viralPackage->load([
            'client',
            'salesRep',
            'techTeam',
            'assets.creator',
            'deliverables.assignee',
            'deliverables.history.changedBy',
            'deliverables.correctionAssets',
        ]);

        return view('writer.viral-packages.show', ['package' => $viralPackage]);
    }

    public function pickUp(ViralPackage $viralPackage, ViralPackageDeliverable $deliverable): RedirectResponse
    {
        $this->ensureAssigned($viralPackage);
        $this->ensureBelongs($deliverable, $viralPackage);

        try {
            $this->service->pickUpDeliverable($deliverable);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Picked up {$deliverable->title}.");
    }

    public function submit(Request $request, ViralPackage $viralPackage, ViralPackageDeliverable $deliverable): \Symfony\Component\HttpFoundation\Response
    {
        $this->ensureAssigned($viralPackage);
        $this->ensureBelongs($deliverable, $viralPackage);

        $validated = $request->validate([
            'file'  => ['required', 'file', 'max:262144'], // 256 MB (matches .user.ini)
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'file.max'      => 'The file is too large. Maximum upload size is 256 MB.',
            'file.required' => 'Please choose a file to upload.',
        ]);

        try {
            $this->service->submitDeliverable($deliverable, $request->file('file'), $validated['notes'] ?? null);
        } catch (DriveException|WorkflowException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'Submit failed: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Submit failed: ' . $e->getMessage());
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => "{$deliverable->title} submitted for review."]);
        }

        return back()->with('success', "{$deliverable->title} submitted for review.");
    }

    public function addPost(ViralPackage $viralPackage, Request $request): RedirectResponse
    {
        $this->ensureAssigned($viralPackage);
        $kind = in_array($request->input('kind'), ['reel', 'article'], true) ? $request->input('kind') : 'social_post';

        try {
            $deliverable = $this->service->addDeliverable($viralPackage, $kind);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$deliverable->title} added.");
    }

    public function clearFile(ViralPackage $viralPackage, ViralPackageDeliverable $deliverable): RedirectResponse
    {
        $this->ensureAssigned($viralPackage);
        $this->ensureBelongs($deliverable, $viralPackage);

        try {
            $this->service->clearDeliverableFile($deliverable);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Content removed from {$deliverable->title}.");
    }

    public function updateCaption(Request $request, ViralPackage $viralPackage, ViralPackageDeliverable $deliverable): RedirectResponse
    {
        $this->ensureAssigned($viralPackage);
        $this->ensureBelongs($deliverable, $viralPackage);

        $data = $request->validate([
            'caption'         => ['nullable', 'string', 'max:5000'],
            'hashtags'        => ['nullable', 'string', 'max:2000'],
            'target_audience' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->service->updateCaption($deliverable, $data['caption'] ?? null, $data['hashtags'] ?? null, $data['target_audience'] ?? null);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Caption saved for {$deliverable->title}.");
    }

    public function publishLanding(Request $request, ViralPackage $viralPackage, ViralPackageDeliverable $deliverable): RedirectResponse
    {
        $this->ensureAssigned($viralPackage);
        $this->ensureBelongs($deliverable, $viralPackage);

        $validated = $request->validate([
            'landing_page_url' => ['required', 'url', 'max:2000'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ], [
            'landing_page_url.required' => 'Please enter the landing page URL.',
            'landing_page_url.url'      => 'Enter a valid URL (including https://).',
        ]);

        try {
            $this->service->submitLandingPage($deliverable, $validated['landing_page_url'], $validated['notes'] ?? null);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Landing page published for {$deliverable->title}.");
    }

    public function removePost(ViralPackage $viralPackage, ViralPackageDeliverable $deliverable): RedirectResponse
    {
        $this->ensureAssigned($viralPackage);
        $this->ensureBelongs($deliverable, $viralPackage);

        try {
            $this->service->removeDeliverable($deliverable);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$deliverable->title} removed.");
    }

    public function downloadAsset(ViralPackage $viralPackage, ViralPackageAsset $asset, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        $this->ensureAssigned($viralPackage);

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
        $this->ensureAssigned($viralPackage);
        return $this->buildAssetsZip($viralPackage, $drive);
    }

    public function downloadDeliverable(ViralPackage $viralPackage, ViralPackageDeliverable $deliverable, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        $this->ensureAssigned($viralPackage);
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

    private function ensureBelongs(ViralPackageDeliverable $deliverable, ViralPackage $package): void
    {
        if ($deliverable->viral_package_id !== $package->id) {
            abort(404);
        }
    }

    private function ensureAssigned(ViralPackage $package): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;

        // The member may access the package if they're the lead OR own any deliverable in it.
        $owns = $package->tech_team_id === $user->id
            || $package->deliverables()->where('assigned_to', $user->id)->exists();

        if (! $owns) {
            throw new \Illuminate\Auth\Access\AuthorizationException('This package is not assigned to you.');
        }
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

        $clientName   = $package->client?->displayName() ?? 'package';
        $downloadName = preg_replace('/[^A-Za-z0-9 _-]/', '_', $clientName) . ' assets.zip';

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend();
    }
}
