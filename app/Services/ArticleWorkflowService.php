<?php

namespace App\Services;

use App\Enums\ArticleStage;
use App\Events\ArticleStageTransitioned;
use App\Exceptions\DriveException;
use App\Exceptions\WorkflowException;
use App\Models\Article;
use App\Models\ArticleAsset;
use App\Models\ArticleType;
use App\Models\DriveFile;
use App\Models\Setting;
use App\Models\StageHistory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ArticleWorkflowService
{
    public function __construct(private readonly GoogleDriveService $drive) {}

    /**
     * Sales submits a new article. Uploads file to Inbox folder.
     *
     * @param  array{title:string,client_id:int,deadline?:?string,word_count_target?:?int,priority?:string,notes?:?string}  $data
     */
    public function submitArticle(array $data, UploadedFile $file, ?User $actor = null, array $assets = [], ?string $assetsFolderName = null): Article
    {
        $actor ??= Auth::user();
        $this->requireRole($actor, ['sales', 'admin'], 'submit articles');

        // If a type is set with its own destination folder, use it. Otherwise fall back to Inbox.
        $typeId = isset($data['article_type_id']) ? (int) $data['article_type_id'] : null;
        $type   = $typeId ? ArticleType::find($typeId) : null;

        $uploadFolder = ($type && $type->drive_folder_id)
            ? $type->drive_folder_id
            : $this->requireFolder('inbox');

        return DB::transaction(function () use ($actor, $data, $file, $uploadFolder, $type, $assets, $assetsFolderName) {
            $article = new Article;
            $article->article_code     = $this->generateArticleCode();
            $article->title            = $data['title'];
            $article->client_id        = ! empty($data['client_id']) ? (int) $data['client_id'] : null;
            $article->article_type_id  = $type?->id;
            $article->sales_rep_id     = $actor->id;
            $article->current_stage    = ArticleStage::INBOX;
            $article->priority         = $data['priority']          ?? 'medium';
            $article->deadline         = $data['deadline']          ?? null;
            $article->word_count_target = $data['word_count_target'] ?? null;
            $article->notes            = $data['notes']             ?? null;
            $article->submitted_at     = now();
            $article->stage_entered_at = now();
            $article->save();

            $driveFileId = $this->uploadFile($file, $uploadFolder, $article, $actor, ArticleStage::INBOX);
            $article->source_drive_file_id  = $driveFileId;
            $article->current_drive_file_id = $driveFileId;
            $article->save();

            // Handle assets — create a subfolder inside the configured "Assets" parent
            // and upload each file there. Links are stored in DB only.
            $this->attachAssets($article, $assets, $assetsFolderName, $actor);

            $note = $type ? "Submitted as {$type->name}" : 'Article submitted';
            $this->recordHistory($article, null, ArticleStage::INBOX, $actor, $note);
            event(new ArticleStageTransitioned($article, null, ArticleStage::INBOX, $actor));

            return $article->fresh(['client', 'salesRep', 'articleType', 'assets']);
        });
    }

    /**
     * Create an asset folder for the article (inside the configured "Assets" parent)
     * and upload each provided file/link.
     *
     * @param  array  $assets  Each item: ['type' => 'file'|'link', 'name' => ?string, 'file' => ?UploadedFile, 'url' => ?string]
     */
    private function attachAssets(Article $article, array $assets, ?string $folderName, ?User $actor): void
    {
        $assets = array_values(array_filter($assets, fn ($a) => ! empty($a['type'])));
        if (empty($assets) && empty($folderName)) {
            return;
        }

        $assetsParent = Setting::get('drive_folder_assets');
        $folderName   = trim((string) $folderName) ?: $article->title;

        $assetsFolderId = null;
        if ($assetsParent) {
            try {
                $assetsFolderId = $this->drive->createFolder($folderName, $assetsParent);
                $article->update([
                    'assets_folder_drive_id' => $assetsFolderId,
                    'assets_folder_name'     => $folderName,
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->uploadAssetsToFolder($article, $assets, $assetsFolderId, $actor);
    }

    /**
     * Save revision assets in a "Correction needed" subfolder of the article's
     * existing assets folder so tech team sees them grouped with the original assets.
     */
    private function attachRevisionAssets(Article $article, array $assets, ?User $actor): void
    {
        $assets = array_values(array_filter($assets, fn ($a) => ! empty($a['type'])));
        if (empty($assets)) {
            return;
        }

        // Ensure the article's main assets folder exists; create it on the fly if it
        // doesn't (sales may not have attached anything on first submission).
        $articleAssetsFolderId = $article->assets_folder_drive_id;
        if (! $articleAssetsFolderId) {
            $assetsParent = Setting::get('drive_folder_assets');
            if ($assetsParent) {
                try {
                    $articleAssetsFolderId = $this->drive->createFolder($article->title, $assetsParent);
                    $article->update([
                        'assets_folder_drive_id' => $articleAssetsFolderId,
                        'assets_folder_name'     => $article->title,
                    ]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        // Drive subfolder for this revision round. Numbered so multiple rounds don't collide.
        $previousRevisions = $article->history()
            ->where('to_stage', ArticleStage::REVISIONS->value)
            ->count();
        $suffix      = $previousRevisions === 0 ? '' : ' (' . ($previousRevisions + 1) . ')';
        $folderName  = 'Correction needed' . $suffix;

        $correctionFolderId = null;
        if ($articleAssetsFolderId) {
            try {
                $correctionFolderId = $this->drive->createFolder($folderName, $articleAssetsFolderId);
            } catch (\Throwable $e) {
                report($e);
                $correctionFolderId = $articleAssetsFolderId;
            }
        }

        $this->uploadAssetsToFolder($article, $assets, $correctionFolderId, $actor);
    }

    /**
     * Upload each asset (file or link) into Drive and persist an ArticleAsset row.
     * If $folderId is null, file assets are skipped but links are still recorded.
     */
    private function uploadAssetsToFolder(Article $article, array $assets, ?string $folderId, ?User $actor): void
    {
        foreach ($assets as $asset) {
            if ($asset['type'] === 'link' && ! empty($asset['url'])) {
                ArticleAsset::create([
                    'article_id' => $article->id,
                    'type'       => 'link',
                    'name'       => $asset['name'] ?? null,
                    'url'        => $asset['url'],
                    'created_by' => $actor?->id,
                ]);
            } elseif ($asset['type'] === 'file' && isset($asset['file']) && $folderId) {
                /** @var UploadedFile $f */
                $f         = $asset['file'];
                $assetName = trim((string) ($asset['name'] ?? '')) ?: $f->getClientOriginalName();

                try {
                    $driveFileId = $this->drive->uploadFile($f->getRealPath(), $folderId, $assetName);
                } catch (\Throwable $e) {
                    report($e);
                    continue;
                }

                ArticleAsset::create([
                    'article_id'        => $article->id,
                    'type'              => 'file',
                    'name'              => $assetName,
                    'drive_file_id'     => $driveFileId,
                    'original_filename' => $f->getClientOriginalName(),
                    'mime_type'         => $f->getMimeType(),
                    'file_size'         => $f->getSize(),
                    'created_by'        => $actor?->id,
                ]);
            }
        }
    }

    /**
     * Admin assigns the article to a tech writer.
     */
    public function assignArticle(Article|int $article, User|int $techWriter, ?User $actor = null, ?string $notes = null): Article
    {
        $actor ??= Auth::user();
        $this->requireRole($actor, ['admin'], 'assign articles');

        $article = $this->resolveArticle($article);
        $writer  = $techWriter instanceof User ? $techWriter : User::findOrFail($techWriter);

        if ($writer->role !== 'tech_team') {
            throw WorkflowException::invalidWriter();
        }

        $this->requireStage($article, [ArticleStage::INBOX, ArticleStage::ASSIGNED]);

        return DB::transaction(function () use ($article, $writer, $actor, $notes) {
            $from = $article->current_stage;

            $this->moveFile($article, ArticleStage::ASSIGNED);

            $article->tech_writer_id   = $writer->id;
            $article->current_stage    = ArticleStage::ASSIGNED;
            $article->stage_entered_at = now();
            $article->save();

            $this->recordHistory($article, $from, ArticleStage::ASSIGNED, $actor, $notes ?? "Assigned to {$writer->name}");
            event(new ArticleStageTransitioned($article, $from, ArticleStage::ASSIGNED, $actor, $notes));

            return $article->fresh();
        });
    }

    /**
     * Tech-team member picks up an Inbox article themselves (no admin needed).
     */
    public function selfAssign(Article|int $article, ?User $actor = null): Article
    {
        $actor ??= Auth::user();
        $this->requireRole($actor, ['tech_team', 'admin'], 'pick up articles');

        $article = $this->resolveArticle($article);
        $this->requireStage($article, [ArticleStage::INBOX]);

        return DB::transaction(function () use ($article, $actor) {
            $from = $article->current_stage;

            $this->moveFile($article, ArticleStage::ASSIGNED);

            $article->tech_writer_id   = $actor->id;
            $article->current_stage    = ArticleStage::ASSIGNED;
            $article->stage_entered_at = now();
            $article->save();

            $this->recordHistory($article, $from, ArticleStage::ASSIGNED, $actor, "Self-assigned by {$actor->name}");
            event(new ArticleStageTransitioned($article, $from, ArticleStage::ASSIGNED, $actor));

            return $article->fresh();
        });
    }

    /**
     * Tech writer starts working on an assigned article.
     */
    public function startWork(Article|int $article, ?User $actor = null): Article
    {
        $actor ??= Auth::user();
        $article = $this->resolveArticle($article);

        $this->requireAssignedWriter($article, $actor, 'start work');
        $this->requireStage($article, [ArticleStage::ASSIGNED, ArticleStage::REVISIONS]);

        return DB::transaction(function () use ($article, $actor) {
            $from = $article->current_stage;

            $this->moveFile($article, ArticleStage::IN_PROGRESS);

            $article->current_stage    = ArticleStage::IN_PROGRESS;
            $article->stage_entered_at = now();
            $article->save();

            $this->recordHistory($article, $from, ArticleStage::IN_PROGRESS, $actor);
            event(new ArticleStageTransitioned($article, $from, ArticleStage::IN_PROGRESS, $actor));

            return $article->fresh();
        });
    }

    /**
     * Tech team uploads the rewrite and submits straight to sales for review.
     * (Internal review by a tech lead is skipped — the team handles its own QA.)
     */
    public function submitForReview(Article|int $article, UploadedFile $file, ?User $actor = null, ?string $notes = null): Article
    {
        $actor ??= Auth::user();
        $article = $this->resolveArticle($article);

        $this->requireAssignedWriter($article, $actor, 'submit for review');
        $this->requireStage($article, [ArticleStage::IN_PROGRESS]);

        $salesReviewFolder = $this->requireFolder('client_approval');

        return DB::transaction(function () use ($article, $file, $actor, $notes, $salesReviewFolder) {
            $from = $article->current_stage;

            $newFileId = $this->uploadFile($file, $salesReviewFolder, $article, $actor, ArticleStage::CLIENT_APPROVAL);

            $article->current_drive_file_id = $newFileId;
            $article->current_stage         = ArticleStage::CLIENT_APPROVAL;
            $article->stage_entered_at      = now();
            $article->save();

            $this->recordHistory($article, $from, ArticleStage::CLIENT_APPROVAL, $actor, $notes ?? 'Rewrite submitted to sales for review');
            event(new ArticleStageTransitioned($article, $from, ArticleStage::CLIENT_APPROVAL, $actor, $notes));

            return $article->fresh();
        });
    }

    /**
     * Tech lead approves the rewrite, sending it to client approval.
     */
    public function approveByLead(Article|int $article, ?User $actor = null, ?string $notes = null): Article
    {
        $actor ??= Auth::user();
        $this->requireRole($actor, ['tech_team', 'admin'], 'approve articles for client review');

        $article = $this->resolveArticle($article);
        $this->requireStage($article, [ArticleStage::INTERNAL_REVIEW]);

        return DB::transaction(function () use ($article, $actor, $notes) {
            $from = $article->current_stage;

            $this->moveFile($article, ArticleStage::CLIENT_APPROVAL);

            $article->tech_lead_id     = $article->tech_lead_id ?? $actor->id;
            $article->current_stage    = ArticleStage::CLIENT_APPROVAL;
            $article->stage_entered_at = now();
            $article->save();

            $this->recordHistory($article, $from, ArticleStage::CLIENT_APPROVAL, $actor, $notes ?? 'Approved by tech lead');
            event(new ArticleStageTransitioned($article, $from, ArticleStage::CLIENT_APPROVAL, $actor, $notes));

            return $article->fresh();
        });
    }

    /**
     * Undo a revision request and send the article back to client review.
     * Useful when sales triggers "Send for revision" by mistake.
     */
    public function revokeRevision(Article|int $article, ?User $actor = null): Article
    {
        $actor ??= Auth::user();
        $article = $this->resolveArticle($article);

        $this->requireStage($article, [ArticleStage::REVISIONS]);
        $this->requireRole($actor, ['sales', 'admin'], 'revoke a correction');
        if ($actor->role === 'sales' && $article->sales_rep_id !== $actor->id) {
            throw WorkflowException::notAuthorized($actor, 'revoke this correction');
        }

        return DB::transaction(function () use ($article, $actor) {
            $from = $article->current_stage;

            $this->moveFile($article, ArticleStage::CLIENT_APPROVAL);

            $article->current_stage    = ArticleStage::CLIENT_APPROVAL;
            $article->stage_entered_at = now();
            $article->save();

            $this->recordHistory($article, $from, ArticleStage::CLIENT_APPROVAL, $actor, 'Correction revoked — article back in sales review');
            // Intentionally not firing ArticleStageTransitioned: re-notifying sales "ready for review"
            // for an action they just performed would be noise. The history row is the audit trail.

            return $article->fresh();
        });
    }

    /**
     * Send the article back for revisions. Allowed from internal_review (lead) or client_approval (sales).
     */
    public function requestRevision(Article|int $article, string $reason, ?User $actor = null, array $assets = []): Article
    {
        $actor ??= Auth::user();
        $article = $this->resolveArticle($article);

        $allowed = [ArticleStage::INTERNAL_REVIEW, ArticleStage::CLIENT_APPROVAL];
        if (! in_array($article->current_stage, $allowed, true)) {
            throw WorkflowException::invalidStageOneOf($article->current_stage, $allowed);
        }

        if ($article->current_stage === ArticleStage::INTERNAL_REVIEW) {
            $this->requireRole($actor, ['tech_team', 'admin'], 'send for revision');
        } else {
            $this->requireRole($actor, ['sales', 'admin'], 'send for revision');
            if ($actor->role === 'sales' && $article->sales_rep_id !== $actor->id) {
                throw WorkflowException::notAuthorized($actor, 'send this article for revision');
            }
        }

        return DB::transaction(function () use ($article, $reason, $actor, $assets) {
            $from = $article->current_stage;

            $this->moveFile($article, ArticleStage::REVISIONS);

            // Save any reference assets in a "Correction needed" subfolder before
            // recording history, so the count-based naming reflects this revision round.
            $this->attachRevisionAssets($article, $assets, $actor);

            $article->current_stage    = ArticleStage::REVISIONS;
            $article->stage_entered_at = now();
            $article->save();

            $this->recordHistory($article, $from, ArticleStage::REVISIONS, $actor, $reason);
            event(new ArticleStageTransitioned($article, $from, ArticleStage::REVISIONS, $actor, $reason));

            return $article->fresh();
        });
    }

    /**
     * Sales rep marks client approval. Article moves to Approved.
     */
    public function clientApproved(Article|int $article, ?User $actor = null, ?string $notes = null): Article
    {
        $actor ??= Auth::user();
        $article = $this->resolveArticle($article);

        $this->requireRole($actor, ['sales', 'admin'], 'mark client approval');
        if ($actor->role === 'sales' && $article->sales_rep_id !== $actor->id) {
            throw WorkflowException::notAuthorized($actor, 'mark client approval for this article');
        }

        $this->requireStage($article, [ArticleStage::CLIENT_APPROVAL]);

        return DB::transaction(function () use ($article, $actor, $notes) {
            $from = $article->current_stage;

            $this->moveFile($article, ArticleStage::APPROVED);

            $article->current_stage    = ArticleStage::APPROVED;
            $article->stage_entered_at = now();
            $article->save();

            $this->recordHistory($article, $from, ArticleStage::APPROVED, $actor, $notes ?? 'Client approved');
            event(new ArticleStageTransitioned($article, $from, ArticleStage::APPROVED, $actor, $notes));

            return $article->fresh();
        });
    }

    /**
     * Admin or sales rep marks article as published with the live URL.
     */
    public function markPublished(Article|int $article, string $publishedUrl, ?User $actor = null): Article
    {
        $actor ??= Auth::user();
        $article = $this->resolveArticle($article);

        $this->requireRole($actor, ['admin', 'sales', 'tech_team'], 'mark as published');
        $this->requireStage($article, [ArticleStage::APPROVED]);

        return DB::transaction(function () use ($article, $publishedUrl, $actor) {
            $from = $article->current_stage;

            $this->moveFile($article, ArticleStage::PUBLISHED);

            $article->current_stage    = ArticleStage::PUBLISHED;
            $article->published_at     = now();
            $article->published_url    = $publishedUrl;
            $article->stage_entered_at = now();
            $article->save();

            $this->recordHistory($article, $from, ArticleStage::PUBLISHED, $actor, "Published: {$publishedUrl}");
            event(new ArticleStageTransitioned($article, $from, ArticleStage::PUBLISHED, $actor, $publishedUrl));

            return $article->fresh();
        });
    }

    /**
     * Tech lead reassigns an article to a different writer.
     */
    public function reassignWriter(Article|int $article, User|int $newWriter, ?User $actor = null): Article
    {
        $actor ??= Auth::user();
        $this->requireRole($actor, ['admin', 'tech_team'], 'reassign writers');

        $article = $this->resolveArticle($article);
        $writer  = $newWriter instanceof User ? $newWriter : User::findOrFail($newWriter);

        if ($writer->role !== 'tech_team') {
            throw WorkflowException::invalidWriter();
        }

        $oldWriter = $article->techWriter?->name ?? 'unassigned';
        $article->update(['tech_writer_id' => $writer->id]);
        $this->recordHistory($article, $article->current_stage, $article->current_stage, $actor,
            "Reassigned from {$oldWriter} to {$writer->name}");

        return $article->fresh();
    }

    // -------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------

    private function resolveArticle(Article|int $article): Article
    {
        return $article instanceof Article ? $article : Article::findOrFail($article);
    }

    private function requireRole(?User $user, array $roles, string $action): void
    {
        if (! $user || ! in_array($user->role, $roles, true)) {
            throw WorkflowException::notAuthorized($user, $action);
        }
    }

    private function requireAssignedWriter(Article $article, ?User $user, string $action): void
    {
        if (! $user || $user->role !== 'tech_team' || $article->tech_writer_id !== $user->id) {
            throw WorkflowException::notAuthorized($user, "{$action} on this article");
        }
    }

    private function requireStage(Article $article, array $allowedStages): void
    {
        if (! in_array($article->current_stage, $allowedStages, true)) {
            throw count($allowedStages) === 1
                ? WorkflowException::invalidStage($article->current_stage, $allowedStages[0])
                : WorkflowException::invalidStageOneOf($article->current_stage, $allowedStages);
        }
    }

    private function requireFolder(string $stage): string
    {
        $folder = $this->drive->getStageFolderId($stage);
        if (! $folder) {
            throw DriveException::operationFailed("Drive folder for stage '{$stage}' is not configured. Run setup in admin settings.");
        }
        return $folder;
    }

    private function uploadFile(UploadedFile $file, string $folderId, Article $article, ?User $actor, ArticleStage $stage): string
    {
        $name = $this->driveFilename($article, $file);
        $driveFileId = $this->drive->uploadFile($file->getRealPath(), $folderId, $name);

        DriveFile::create([
            'article_id'        => $article->id,
            'drive_file_id'     => $driveFileId,
            'original_filename' => $file->getClientOriginalName(),
            'stage'             => $stage->value,
            'uploaded_by'       => $actor?->id,
            'uploaded_at'       => now(),
            'file_size'         => $file->getSize(),
            'mime_type'         => $file->getMimeType(),
        ]);

        return $driveFileId;
    }

    private function moveFile(Article $article, ArticleStage $newStage): void
    {
        if (! $article->current_drive_file_id) {
            return;
        }

        $folder = $this->requireFolder($newStage->value);

        try {
            $this->drive->moveFile($article->current_drive_file_id, $folder);
        } catch (DriveException $e) {
            // Stale/orphaned files (uploaded under a previous auth method, or deleted manually
            // in Drive) shouldn't block a stage transition. Clear the dangling reference so the
            // article still progresses; the user can re-upload if needed.
            if (str_contains(strtolower($e->getMessage()), 'file not found')
                || str_contains(strtolower($e->getMessage()), 'not found')) {
                \Illuminate\Support\Facades\Log::warning('Drive file no longer accessible — clearing reference', [
                    'article_id'   => $article->id,
                    'article_code' => $article->article_code,
                    'file_id'      => $article->current_drive_file_id,
                ]);
                $article->current_drive_file_id = null;
                $article->save();
                return;
            }
            throw $e;
        }
    }

    private function driveFilename(Article $article, UploadedFile $file): string
    {
        $ext = $file->getClientOriginalExtension() ?: 'bin';

        // Use just the article title; strip filesystem-illegal chars and cap length.
        $title = preg_replace('/[\\\\\/:*?"<>|]+/', '-', (string) $article->title);
        $title = trim(preg_replace('/\s+/', ' ', $title));
        $title = mb_substr($title, 0, 100);

        return $title !== ''
            ? "{$title}.{$ext}"
            : "{$article->article_code}.{$ext}"; // fallback only if title somehow empty
    }

    private function recordHistory(Article $article, ?ArticleStage $from, ArticleStage $to, ?User $actor, ?string $notes = null): void
    {
        StageHistory::create([
            'article_id' => $article->id,
            'from_stage' => $from?->value,
            'to_stage'   => $to->value,
            'changed_by' => $actor?->id,
            'changed_at' => now(),
            'notes'      => $notes,
        ]);
    }

    private function generateArticleCode(): string
    {
        return DB::transaction(function () {
            $latest = Article::withTrashed()->lockForUpdate()->orderByDesc('id')->first();
            $next   = 1;

            if ($latest && preg_match('/ART-(\d+)/', $latest->article_code, $m)) {
                $next = (int) $m[1] + 1;
            }

            return 'ART-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        });
    }
}
