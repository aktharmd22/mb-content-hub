<?php

namespace App\Http\Controllers\Sales;

use App\Enums\ArticleStage;
use App\Exceptions\DriveException;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreArticleRequest;
use App\Models\Article;
use App\Models\ArticleAsset;
use App\Models\ArticleType;
use App\Models\Client;
use App\Models\Comment;
use App\Services\ArticleWorkflowService;
use App\Services\GoogleDriveService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArticleController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $articles = Article::where('sales_rep_id', auth()->id())
            ->with('client')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->get('q'));
                $q->where(function ($w) use ($term) {
                    $w->where('title', 'like', "%{$term}%")
                      ->orWhere('article_code', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('stage'), fn ($q) => $q->where('current_stage', $request->get('stage')))
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->get('client_id')))
            ->orderByDesc('submitted_at')
            ->paginate(20)
            ->withQueryString();

        $clients = Client::orderBy('name')->get(['id', 'name']);
        $stages  = ArticleStage::cases();

        return view('sales.articles.index', compact('articles', 'clients', 'stages'));
    }

    public function create(): View
    {
        $this->authorize('create', Article::class);
        $clients = Client::orderBy('name')->get();
        $types   = ArticleType::active()->orderBy('sort_order')->orderBy('name')->get();
        return view('sales.articles.create', compact('clients', 'types'));
    }

    public function store(StoreArticleRequest $request, ArticleWorkflowService $workflow): RedirectResponse
    {
        // Validation already ran via the form-request rules. Build the assets array
        // by zipping the validated metadata with the actual uploaded files.
        $validated = $request->validated();
        $assets    = $this->buildAssetsPayload($request);

        try {
            $article = $workflow->submitArticle(
                data: $validated,
                file: $request->file('file'),
                assets: $assets,
                assetsFolderName: $validated['assets_folder_name'] ?? null,
            );
        } catch (DriveException|WorkflowException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('error', 'Could not submit article. ' . $e->getMessage());
        }

        $assetCount = count($assets);
        $msg = "Article {$article->article_code} submitted." . ($assetCount > 0 ? " ({$assetCount} asset" . ($assetCount > 1 ? 's' : '') . " attached)" : '');

        return redirect()
            ->route('sales.articles.show', $article)
            ->with('success', $msg);
    }

    /** Build [type, name, file?, url?] tuples from the request's assets[] payload. */
    private function buildAssetsPayload(\Illuminate\Http\Request $request): array
    {
        $rows  = $request->input('assets', []);
        $files = $request->file('assets', []);
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

    public function show(Article $article): View
    {
        $this->authorize('view', $article);

        $article->load([
            'client', 'salesRep', 'techWriter', 'techLead',
            'history.changedBy',
            'comments.user',
            'assets',
        ]);

        return view('sales.articles.show', compact('article'));
    }

    public function clientApproved(Article $article, ArticleWorkflowService $workflow): RedirectResponse
    {
        $this->ensureOwnArticle($article);
        return $this->runTransition(fn () => $workflow->clientApproved($article), 'Marked as client-approved.', $article);
    }

    public function revokeRevision(Article $article, ArticleWorkflowService $workflow): RedirectResponse
    {
        $this->ensureOwnArticle($article);

        return $this->runTransition(
            fn () => $workflow->revokeRevision($article),
            'Correction revoked. Article is back in sales review.',
            $article,
        );
    }

    public function requestRevision(Request $request, Article $article, ArticleWorkflowService $workflow): RedirectResponse
    {
        $this->ensureOwnArticle($article);

        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ])['reason'];

        $assets = $this->buildAssetsPayload($request);

        return $this->runTransition(
            fn () => $workflow->requestRevision($article, $reason, assets: $assets),
            'Sent for revision.',
            $article,
        );
    }

    public function comment(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('comment', $article);

        $body = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ])['comment'];

        Comment::create([
            'article_id' => $article->id,
            'user_id'    => auth()->id(),
            'comment'    => $body,
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function download(Article $article, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        $this->authorize('view', $article);

        if (! $article->current_drive_file_id) {
            return back()->with('error', 'No file is attached to this article yet.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'article_');

        try {
            $drive->downloadFile($article->current_drive_file_id, $tempPath);
            $meta = $drive->getFileMetadata($article->current_drive_file_id);
        } catch (DriveException $e) {
            @unlink($tempPath);
            return back()->with('error', $e->getMessage());
        }

        return response()
            ->download($tempPath, $meta['name'] ?? "{$article->article_code}.bin")
            ->deleteFileAfterSend();
    }

    public function destroy(Article $article, GoogleDriveService $drive): RedirectResponse
    {
        $this->ensureOwnArticle($article);

        // Best-effort: also remove the Drive file. Log failures but don't block deletion.
        if ($article->current_drive_file_id) {
            try {
                $drive->deleteFile($article->current_drive_file_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $code = $article->article_code;
        $article->delete();

        return redirect()
            ->route('sales.articles.index')
            ->with('success', "Article {$code} deleted.");
    }

    public function downloadAsset(Article $article, ArticleAsset $asset, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        $this->authorize('view', $article);

        if ($asset->article_id !== $article->id) {
            abort(404);
        }

        if ($asset->type === 'link') {
            return $asset->url
                ? redirect()->away($asset->url)
                : back()->with('error', 'This asset has no URL.');
        }

        if (! $asset->drive_file_id) {
            return back()->with('error', 'This asset has no file attached.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'asset_');

        try {
            $drive->downloadFile($asset->drive_file_id, $tempPath);
        } catch (DriveException $e) {
            @unlink($tempPath);
            return back()->with('error', $e->getMessage());
        }

        $filename = $asset->original_filename ?: ($asset->name ?: 'asset');

        return response()->download($tempPath, $filename)->deleteFileAfterSend();
    }

    public function replaceAsset(Request $request, Article $article, ArticleAsset $asset, GoogleDriveService $drive): RedirectResponse
    {
        $this->ensureOwnArticle($article);

        if ($asset->article_id !== $article->id) {
            abort(404);
        }

        if ($asset->type !== 'file') {
            return back()->with('error', 'Only file assets can be replaced. Delete and re-add links.');
        }

        $request->validate([
            'file' => ['required', 'file', 'max:204800'],
        ]);

        $newFile = $request->file('file');
        $folderId = $article->assets_folder_drive_id;

        if (! $folderId) {
            return back()->with('error', 'This article has no assets folder yet.');
        }

        // Best-effort: delete the old Drive file before uploading the new one (keeps folder clean)
        if ($asset->drive_file_id) {
            try {
                $drive->deleteFile($asset->drive_file_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Upload the replacement
        $filename = $newFile->getClientOriginalName();
        try {
            $newDriveFileId = $drive->uploadFile($newFile->getRealPath(), $folderId, $filename);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Could not upload replacement: ' . $e->getMessage());
        }

        $asset->update([
            'drive_file_id'     => $newDriveFileId,
            'original_filename' => $filename,
            'mime_type'         => $newFile->getMimeType(),
            'file_size'         => $newFile->getSize(),
        ]);

        return back()->with('success', "Asset replaced — old file removed, '{$filename}' uploaded.");
    }

    public function destroyAsset(Article $article, ArticleAsset $asset, GoogleDriveService $drive): RedirectResponse
    {
        $this->ensureOwnArticle($article);

        if ($asset->article_id !== $article->id) {
            abort(404);
        }

        // Best-effort: remove the Drive file too
        if ($asset->drive_file_id) {
            try {
                $drive->deleteFile($asset->drive_file_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $assetName = $asset->name ?: $asset->original_filename ?: 'Asset';
        $asset->delete();

        return back()->with('success', "{$assetName} removed.");
    }

    private function ensureOwnArticle(Article $article): void
    {
        $user = auth()->user();
        if (! $user->isAdmin() && $article->sales_rep_id !== $user->id) {
            throw new AuthorizationException('You can only act on your own articles.');
        }
    }

    private function runTransition(callable $action, string $successMessage, Article $article): RedirectResponse
    {
        try {
            $action();
        } catch (DriveException|WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Action failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('sales.articles.show', $article)
            ->with('success', $successMessage);
    }
}
