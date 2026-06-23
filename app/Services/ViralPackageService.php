<?php

namespace App\Services;

use App\Events\ViralPackageEvent;
use App\Exceptions\DriveException;
use App\Exceptions\WorkflowException;
use App\Models\Client;
use App\Models\Setting;
use App\Models\User;
use App\Models\ViralPackage;
use App\Models\ViralPackageAsset;
use App\Models\ViralPackageDeliverable;
use App\Models\ViralPackageHistory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViralPackageService
{
    public function __construct(private readonly GoogleDriveService $drive) {}

    /**
     * Create a new viral package for a client, auto-seeding the 7 deliverable slots
     * (1 article + 5 social posts + 1 reel) and attaching any reference assets.
     */
    public function createPackage(int $clientId, int $techTeamId, array $assets = [], ?User $actor = null): ViralPackage
    {
        $actor ??= Auth::user();

        if (! $actor || ! in_array($actor->role, ['sales', 'admin'], true)) {
            throw WorkflowException::notAuthorized($actor, 'create a viral package');
        }

        $client = Client::findOrFail($clientId);

        // Verify the assigned tech is actually a tech_team member
        $techMember = User::where('id', $techTeamId)
            ->where('role', 'tech_team')
            ->where('is_active', true)
            ->first();
        if (! $techMember) {
            throw new WorkflowException('Selected tech team member is not valid.');
        }

        return DB::transaction(function () use ($client, $techMember, $actor, $assets) {
            // Enforce one active viral package per client — across the whole business.
            // Lock matching rows FOR UPDATE so two simultaneous creates can't both pass.
            $existing = ViralPackage::where('client_id', $client->id)
                ->where('status', 'active')
                ->with('salesRep')
                ->lockForUpdate()
                ->first();
            if ($existing) {
                $owner = $existing->salesRep?->name ?? 'another sales rep';
                throw new WorkflowException("{$client->name} already has an active viral package (handled by {$owner}). Wait until it's delivered or ask them to remove it.");
            }

            // 1. Create the package row
            $package = ViralPackage::create([
                'client_id'    => $client->id,
                'sales_rep_id' => $actor->id,
                'tech_team_id' => $techMember->id,
                'status'       => 'active',
            ]);

            // 2. Create the Drive folder structure (best effort — failures don't block creation)
            $this->ensureDriveFolders($package);

            // 3. Auto-seed the 7 deliverable slots
            $this->seedDeliverables($package);

            // 4. Attach any reference assets to the Assets/ subfolder
            if (! empty($assets)) {
                $this->attachAssets($package, $assets, $actor);
            }

            // 5. Fire event for tech team notification (only the assigned person gets pinged)
            event(new ViralPackageEvent($package, 'created', $actor));

            return $package->fresh(['deliverables', 'assets', 'client', 'techTeam']);
        });
    }

    /**
     * Reassign a package to a different tech team member.
     */
    public function reassignTechTeam(ViralPackage $package, int $newTechTeamId, ?User $actor = null): ViralPackage
    {
        $actor ??= Auth::user();
        $this->requireRole($actor, ['sales', 'admin'], 'reassign a viral package');

        if ($actor->role === 'sales' && $package->sales_rep_id !== $actor->id) {
            throw WorkflowException::notAuthorized($actor, 'reassign this package');
        }

        if ($package->isCompleted()) {
            throw new WorkflowException('This package is already completed and cannot be reassigned.');
        }

        $newTech = User::where('id', $newTechTeamId)
            ->where('role', 'tech_team')
            ->where('is_active', true)
            ->first();
        if (! $newTech) {
            throw new WorkflowException('Selected tech team member is not valid.');
        }

        $package->update(['tech_team_id' => $newTech->id]);
        return $package->fresh();
    }

    /**
     * Tech team picks up a deliverable to work on.
     */
    public function pickUpDeliverable(ViralPackageDeliverable $deliverable, ?User $actor = null): ViralPackageDeliverable
    {
        $actor ??= Auth::user();
        $this->requireRole($actor, ['tech_team', 'admin'], 'pick up a deliverable');

        if ($deliverable->stage !== 'pending' && $deliverable->stage !== 'in_progress') {
            throw new WorkflowException("This deliverable is in '{$deliverable->stage}' stage and can't be picked up.");
        }

        return DB::transaction(function () use ($deliverable, $actor) {
            $from = $deliverable->stage;
            $deliverable->update([
                'stage'       => 'in_progress',
                'assigned_to' => $actor->id,
            ]);
            $this->recordHistory($deliverable, $from, 'in_progress', $actor, 'Picked up by tech team');
            return $deliverable->fresh();
        });
    }

    /**
     * Tech team uploads a deliverable file and submits it for sales review.
     */
    public function submitDeliverable(ViralPackageDeliverable $deliverable, UploadedFile $file, ?string $notes = null, ?User $actor = null): ViralPackageDeliverable
    {
        $actor ??= Auth::user();
        $this->requireRole($actor, ['tech_team', 'admin'], 'submit a deliverable');

        if (! in_array($deliverable->stage, ['pending', 'in_progress'], true)) {
            throw new WorkflowException("This deliverable can't be submitted from '{$deliverable->stage}' stage.");
        }

        $targetFolderId = $this->folderForDeliverable($deliverable);

        if (! $targetFolderId) {
            throw new DriveException('Could not access the Drive folder for this package.');
        }

        // If there's an existing file, delete it first (replace, don't accumulate)
        if ($deliverable->drive_file_id) {
            try {
                $this->drive->deleteFile($deliverable->drive_file_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $filename = $deliverable->title . '.' . $file->getClientOriginalExtension();

        try {
            $driveFileId = $this->drive->uploadFile($file->getRealPath(), $targetFolderId, $filename);
        } catch (\Throwable $e) {
            report($e);
            throw new DriveException('Upload failed: ' . $e->getMessage());
        }

        return DB::transaction(function () use ($deliverable, $driveFileId, $filename, $file, $notes, $actor) {
            $from = $deliverable->stage;
            $deliverable->update([
                'stage'          => 'review',
                'assigned_to'    => $actor->id,
                'drive_file_id'  => $driveFileId,
                'drive_filename' => $filename,
                'mime_type'      => $file->getMimeType(),
                'file_size'      => $file->getSize(),
                'notes'          => $notes,
                'submitted_at'   => now(),
            ]);
            $this->recordHistory($deliverable, $from, 'review', $actor, $notes ?: 'Submitted for review');

            event(new ViralPackageEvent($deliverable->package, 'deliverable_submitted', $actor, ['deliverable_id' => $deliverable->id]));

            return $deliverable->fresh();
        });
    }

    /**
     * Sales approves a deliverable.
     */
    public function approveDeliverable(ViralPackageDeliverable $deliverable, ?User $actor = null): ViralPackageDeliverable
    {
        $actor ??= Auth::user();
        $package = $deliverable->package;

        $this->requireRole($actor, ['sales', 'admin'], 'approve a deliverable');
        if ($actor->role === 'sales' && $package->sales_rep_id !== $actor->id) {
            throw WorkflowException::notAuthorized($actor, 'approve this deliverable');
        }

        if ($deliverable->stage !== 'review') {
            throw new WorkflowException("Only deliverables in 'Ready for review' can be approved.");
        }

        return DB::transaction(function () use ($deliverable, $actor) {
            $from = $deliverable->stage;
            $deliverable->update([
                'stage'       => 'approved',
                'approved_at' => now(),
            ]);
            $this->recordHistory($deliverable, $from, 'approved', $actor, 'Approved');

            // If this was the final piece, notify everyone it's ready to mark delivered.
            $package = $deliverable->package->fresh(['deliverables']);
            if ($package->canBeMarkedDelivered()) {
                event(new ViralPackageEvent($package, 'all_approved', $actor));
            }

            return $deliverable->fresh();
        });
    }

    /**
     * Sales requests changes on a deliverable. Optionally attaches correction assets,
     * which are saved in the package's "Correction needed" subfolder.
     */
    public function requestCorrection(ViralPackageDeliverable $deliverable, string $reason, array $correctionAssets = [], ?User $actor = null): ViralPackageDeliverable
    {
        $actor ??= Auth::user();
        $package = $deliverable->package;

        $this->requireRole($actor, ['sales', 'admin'], 'request a correction');
        if ($actor->role === 'sales' && $package->sales_rep_id !== $actor->id) {
            throw WorkflowException::notAuthorized($actor, 'request correction on this deliverable');
        }

        if ($deliverable->stage !== 'review') {
            throw new WorkflowException("Only deliverables in 'Ready for review' can be sent for correction.");
        }

        // If sales attached file(s), make sure Drive can actually store them — otherwise
        // the files would be silently dropped. Fail loudly BEFORE changing anything.
        $hasFileAssets = collect($correctionAssets)
            ->contains(fn ($a) => ($a['type'] ?? null) === 'file' && isset($a['file']));
        if ($hasFileAssets) {
            $this->ensureDriveFolders($package);
            $package->refresh();
            if (! $package->drive_corrections_folder_id) {
                throw new WorkflowException(
                    'Google Drive isn\'t set up for this package, so the attached file can\'t be saved. '
                    . 'Remove the attachment and send the correction as text only, or ask an admin to configure Drive in Settings.'
                );
            }
        }

        return DB::transaction(function () use ($deliverable, $reason, $correctionAssets, $actor) {
            $from = $deliverable->stage;

            // 1. Move the rejected deliverable file (if any) into the Corrections folder.
            //    The original file's drive_file_id is preserved as a ViralPackageAsset so
            //    tech team can still download the rejected version for reference.
            //    Returns true only if the rejected file was safely preserved (archived).
            $archived = $this->archiveRejectedFileToCorrections($deliverable, $actor);

            // 2. Update the slot. Only clear the file pointer if the rejected version was
            //    safely archived — otherwise keep it so the reference isn't lost.
            $updates = [
                'stage' => 'in_progress',
                'notes' => $reason,
            ];
            if ($archived || ! $deliverable->drive_file_id) {
                $updates += [
                    'drive_file_id'  => null,
                    'drive_filename' => null,
                    'mime_type'      => null,
                    'file_size'      => null,
                ];
            }
            $deliverable->update($updates);
            $this->recordHistory($deliverable, $from, 'in_progress', $actor, "Correction requested: {$reason}");

            // 3. Save any reference files/links sales attached, also into Corrections/
            if (! empty($correctionAssets)) {
                $this->attachCorrectionAssets($deliverable, $correctionAssets, $actor);
            }

            event(new ViralPackageEvent($deliverable->package, 'correction_requested', $actor, ['deliverable_id' => $deliverable->id]));

            return $deliverable->fresh();
        });
    }

    /**
     * Move the deliverable's current Drive file into the package's Corrections/{title}/
     * folder, and create a ViralPackageAsset record so it remains visible/downloadable.
     */
    private function archiveRejectedFileToCorrections(ViralPackageDeliverable $deliverable, User $actor): bool
    {
        if (! $deliverable->drive_file_id) {
            return false;
        }

        $package = $deliverable->package;
        $this->ensureDriveFolders($package);
        $package->refresh();

        if (! $package->drive_corrections_folder_id) {
            return false; // Drive not configured — leave the file where it is
        }

        // Create (or reuse — Drive allows duplicate folder names, but in practice we get one per round) subfolder per deliverable
        $correctionFolderId = null;
        try {
            $correctionFolderId = $this->drive->createFolder($deliverable->title, $package->drive_corrections_folder_id);
        } catch (\Throwable $e) {
            report($e);
            return false;
        }

        try {
            $this->drive->moveFile($deliverable->drive_file_id, $correctionFolderId);
        } catch (\Throwable $e) {
            report($e);
            return false;
        }

        // Record as a package-level asset (NOT tied to the deliverable) so the deliverable
        // card's "Reference files" stays focused on what sales attached; the archived
        // rejected version lives in the package's Reference assets list.
        ViralPackageAsset::create([
            'viral_package_id'  => $package->id,
            'type'              => 'file',
            'name'              => 'Rejected: ' . $deliverable->title . ' (' . now()->format('M j, H:i') . ')',
            'drive_file_id'     => $deliverable->drive_file_id,
            'original_filename' => $deliverable->drive_filename,
            'mime_type'         => $deliverable->mime_type,
            'file_size'         => $deliverable->file_size,
            'created_by'        => $actor->id,
        ]);

        return true;
    }

    /**
     * Sales marks the entire package as delivered (only when all 7 are approved).
     */
    public function markDelivered(ViralPackage $package, ?User $actor = null): ViralPackage
    {
        $actor ??= Auth::user();
        $this->requireRole($actor, ['sales', 'admin'], 'mark a package as delivered');

        if ($actor->role === 'sales' && $package->sales_rep_id !== $actor->id) {
            throw WorkflowException::notAuthorized($actor, 'mark this package as delivered');
        }

        $package->load('deliverables');
        if (! $package->canBeMarkedDelivered()) {
            throw new WorkflowException('All deliverables must be approved before marking delivered.');
        }

        $package->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        event(new ViralPackageEvent($package, 'completed', $actor));

        return $package->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function seedDeliverables(ViralPackage $package): void
    {
        $rows = [
            ['kind' => 'article',     'slot' => 1, 'title' => 'Article'],
            ['kind' => 'social_post', 'slot' => 1, 'title' => 'Post 1'],
            ['kind' => 'social_post', 'slot' => 2, 'title' => 'Post 2'],
            ['kind' => 'social_post', 'slot' => 3, 'title' => 'Post 3'],
            ['kind' => 'social_post', 'slot' => 4, 'title' => 'Post 4'],
            ['kind' => 'social_post', 'slot' => 5, 'title' => 'Post 5'],
            ['kind' => 'reel',        'slot' => 1, 'title' => 'Reel'],
        ];
        foreach ($rows as $row) {
            ViralPackageDeliverable::create([
                'viral_package_id' => $package->id,
                'kind'             => $row['kind'],
                'slot_number'      => $row['slot'],
                'title'            => $row['title'],
                'stage'            => 'pending',
            ]);
        }
    }

    /**
     * Create the package's Drive folder + Assets / Deliverables subfolders,
     * and inside Deliverables also create Article / Social Posts / Reel subfolders
     * so each kind has its own home. Best-effort — won't throw if Drive misconfigured.
     */
    private function ensureDriveFolders(ViralPackage $package): void
    {
        $parentId = Setting::get('drive_folder_viral_packages');
        if (! $parentId) {
            return;
        }

        $package->loadMissing('client');

        try {
            // Top-level package folder
            $packageFolderId = $package->drive_folder_id;
            if (! $packageFolderId) {
                $folderName = $package->client->name . ' — ' . now()->format('Y-m-d');
                $packageFolderId = $this->drive->createFolder($folderName, $parentId);
                $package->update([
                    'drive_folder_id'   => $packageFolderId,
                    'drive_folder_name' => $folderName,
                ]);
            }

            // Reference Assets
            if (! $package->drive_assets_folder_id) {
                $package->update(['drive_assets_folder_id' => $this->drive->createFolder('Reference Assets', $packageFolderId)]);
            }

            // Deliverables (parent for the 3 kind subfolders)
            $deliverablesFolderId = $package->drive_deliverables_folder_id;
            if (! $deliverablesFolderId) {
                $deliverablesFolderId = $this->drive->createFolder('Deliverables', $packageFolderId);
                $package->update(['drive_deliverables_folder_id' => $deliverablesFolderId]);
            }

            // Per-kind subfolders inside Deliverables
            if (! $package->drive_article_folder_id) {
                $package->update(['drive_article_folder_id' => $this->drive->createFolder('Article', $deliverablesFolderId)]);
            }
            if (! $package->drive_posts_folder_id) {
                $package->update(['drive_posts_folder_id' => $this->drive->createFolder('Social Posts', $deliverablesFolderId)]);
            }
            if (! $package->drive_reel_folder_id) {
                $package->update(['drive_reel_folder_id' => $this->drive->createFolder('Reel', $deliverablesFolderId)]);
            }

            // Corrections folder (top-level inside package folder)
            if (! $package->drive_corrections_folder_id) {
                $package->update(['drive_corrections_folder_id' => $this->drive->createFolder('Corrections', $packageFolderId)]);
            }

            $package->refresh();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Return the Drive folder ID where this deliverable's file should be uploaded.
     * Routes to the kind-specific subfolder (Article / Social Posts / Reel).
     * Falls back to the generic Deliverables folder if kind subfolders aren't set up yet.
     */
    private function folderForDeliverable(ViralPackageDeliverable $deliverable): ?string
    {
        $package = $deliverable->package;
        // Make sure all kind subfolders exist (lazy creation if missing)
        if (! $package->drive_article_folder_id || ! $package->drive_posts_folder_id || ! $package->drive_reel_folder_id) {
            $this->ensureDriveFolders($package);
            $package->refresh();
        }

        return match ($deliverable->kind) {
            'article'     => $package->drive_article_folder_id ?: $package->drive_deliverables_folder_id,
            'social_post' => $package->drive_posts_folder_id   ?: $package->drive_deliverables_folder_id,
            'reel'        => $package->drive_reel_folder_id    ?: $package->drive_deliverables_folder_id,
            default       => $package->drive_deliverables_folder_id,
        };
    }

    private function ensureDeliverablesFolder(ViralPackage $package): ?string
    {
        if ($package->drive_deliverables_folder_id) {
            return $package->drive_deliverables_folder_id;
        }
        $this->ensureDriveFolders($package);
        return $package->fresh()->drive_deliverables_folder_id;
    }

    private function attachAssets(ViralPackage $package, array $assets, User $actor): void
    {
        $assets = array_values(array_filter($assets, fn ($a) => ! empty($a['type'])));
        if (empty($assets)) {
            return;
        }

        $folderId = $package->drive_assets_folder_id;

        foreach ($assets as $asset) {
            if ($asset['type'] === 'link' && ! empty($asset['url'])) {
                ViralPackageAsset::create([
                    'viral_package_id' => $package->id,
                    'type'             => 'link',
                    'name'             => $asset['name'] ?? null,
                    'url'              => $asset['url'],
                    'created_by'       => $actor->id,
                ]);
            } elseif ($asset['type'] === 'file' && isset($asset['file']) && $folderId) {
                /** @var UploadedFile $f */
                $f = $asset['file'];
                $name = trim((string) ($asset['name'] ?? '')) ?: $f->getClientOriginalName();

                try {
                    $driveFileId = $this->drive->uploadFile($f->getRealPath(), $folderId, $name);
                } catch (\Throwable $e) {
                    report($e);
                    continue;
                }

                ViralPackageAsset::create([
                    'viral_package_id'  => $package->id,
                    'type'              => 'file',
                    'name'              => $name,
                    'drive_file_id'     => $driveFileId,
                    'original_filename' => $f->getClientOriginalName(),
                    'mime_type'         => $f->getMimeType(),
                    'file_size'         => $f->getSize(),
                    'created_by'        => $actor->id,
                ]);
            }
        }
    }

    private function attachCorrectionAssets(ViralPackageDeliverable $deliverable, array $assets, User $actor): void
    {
        $assets = array_values(array_filter($assets, fn ($a) => ! empty($a['type'])));
        if (empty($assets)) {
            return;
        }

        $package = $deliverable->package;

        // Ensure top-level Drive folders exist (including the "Corrections" parent folder)
        $this->ensureDriveFolders($package);
        $package->refresh();

        // Inside the Corrections folder, create a subfolder per deliverable.
        // Reused on subsequent correction rounds — they all land in the same place.
        $correctionFolderId = null;
        if ($package->drive_corrections_folder_id) {
            try {
                $correctionFolderId = $this->drive->createFolder($deliverable->title, $package->drive_corrections_folder_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        foreach ($assets as $asset) {
            if ($asset['type'] === 'link' && ! empty($asset['url'])) {
                ViralPackageAsset::create([
                    'viral_package_id' => $package->id,
                    'deliverable_id'   => $deliverable->id,
                    'type'             => 'link',
                    'name'             => 'Correction for ' . $deliverable->title . ($asset['name'] ? ' — ' . $asset['name'] : ''),
                    'url'              => $asset['url'],
                    'created_by'       => $actor->id,
                ]);
            } elseif ($asset['type'] === 'file' && isset($asset['file']) && $correctionFolderId) {
                /** @var UploadedFile $f */
                $f = $asset['file'];
                $name = $f->getClientOriginalName();
                try {
                    $driveFileId = $this->drive->uploadFile($f->getRealPath(), $correctionFolderId, $name);
                } catch (\Throwable $e) {
                    report($e);
                    continue;
                }
                ViralPackageAsset::create([
                    'viral_package_id'  => $package->id,
                    'deliverable_id'    => $deliverable->id,
                    'type'              => 'file',
                    'name'              => 'Correction for ' . $deliverable->title,
                    'drive_file_id'     => $driveFileId,
                    'original_filename' => $name,
                    'mime_type'         => $f->getMimeType(),
                    'file_size'         => $f->getSize(),
                    'created_by'        => $actor->id,
                ]);
            }
        }
    }

    public function attachAdditionalAssets(ViralPackage $package, array $assets, ?User $actor = null): void
    {
        $actor ??= Auth::user();
        $this->ensureDriveFolders($package);
        $package = $package->fresh();

        // If any file assets are present, Drive must be able to store them — otherwise
        // they'd be silently skipped while the UI shows "added". Fail loudly instead.
        $hasFileAssets = collect($assets)
            ->contains(fn ($a) => ($a['type'] ?? null) === 'file' && isset($a['file']));
        if ($hasFileAssets && ! $package->drive_assets_folder_id) {
            throw new WorkflowException(
                'Google Drive isn\'t set up for this package, so files can\'t be uploaded. '
                . 'Add links instead, or ask an admin to configure Drive in Settings.'
            );
        }

        $this->attachAssets($package, $assets, $actor);
    }

    /**
     * Public re-trigger of Drive folder creation for an existing package whose folder
     * wasn't created the first time (typically because the admin setting was missing).
     */
    public function retryDriveFolders(ViralPackage $package): void
    {
        $this->ensureDriveFolders($package);
    }

    private function recordHistory(ViralPackageDeliverable $deliverable, ?string $from, string $to, ?User $actor, ?string $notes = null): void
    {
        ViralPackageHistory::create([
            'deliverable_id' => $deliverable->id,
            'from_stage'     => $from,
            'to_stage'       => $to,
            'changed_by'     => $actor?->id,
            'notes'          => $notes,
            'changed_at'     => now(),
        ]);
    }

    private function requireRole(?User $actor, array $allowed, string $action): void
    {
        if (! $actor || ! in_array($actor->role, $allowed, true)) {
            throw WorkflowException::notAuthorized($actor, $action);
        }
    }
}
