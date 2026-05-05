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

    public function requestRevision(Request $request, Article $article, ArticleWorkflowService $workflow): RedirectResponse
    {
        $this->ensureOwnArticle($article);

        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ])['reason'];

        return $this->runTransition(fn () => $workflow->requestRevision($article, $reason), 'Sent for revision.', $article);
    }

    public function publish(Request $request, Article $article, ArticleWorkflowService $workflow): RedirectResponse
    {
        $this->ensureOwnArticle($article);

        $url = $request->validate([
            'published_url' => ['required', 'url', 'max:500'],
        ])['published_url'];

        return $this->runTransition(fn () => $workflow->markPublished($article, $url), 'Marked as published.', $article);
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
