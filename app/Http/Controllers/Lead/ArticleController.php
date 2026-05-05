<?php

namespace App\Http\Controllers\Lead;

use App\Enums\ArticleStage;
use App\Exceptions\DriveException;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleAsset;
use App\Models\Comment;
use App\Models\User;
use App\Services\ArticleWorkflowService;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $stages = ArticleStage::cases();
        $stage  = $request->get('stage', ArticleStage::CLIENT_APPROVAL->value);

        $articles = Article::query()
            ->with(['client', 'techWriter', 'salesRep'])
            ->when($stage !== 'all', fn ($q) => $q->where('current_stage', $stage))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->get('q'));
                $q->where(function ($w) use ($term) {
                    $w->where('title', 'like', "%{$term}%")
                      ->orWhere('article_code', 'like', "%{$term}%");
                });
            })
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->paginate(20)
            ->withQueryString();

        return view('lead.articles.index', compact('articles', 'stages', 'stage'));
    }

    public function show(Article $article): View
    {
        $article->load([
            'client', 'salesRep', 'techWriter', 'techLead',
            'history.changedBy',
            'comments.user',
            'assets',
        ]);

        $writers = User::where('role', 'tech_team')->where('is_active', true)->orderBy('name')->get();

        return view('lead.articles.show', compact('article', 'writers'));
    }

    public function approve(Article $article, ArticleWorkflowService $workflow): RedirectResponse
    {
        return $this->runTransition(fn () => $workflow->approveByLead($article), 'Approved and sent to client.', $article);
    }

    public function requestRevision(Request $request, Article $article, ArticleWorkflowService $workflow): RedirectResponse
    {
        $reason = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ])['reason'];

        return $this->runTransition(fn () => $workflow->requestRevision($article, $reason), 'Sent for revision.', $article);
    }

    public function reassign(Request $request, Article $article, ArticleWorkflowService $workflow): RedirectResponse
    {
        $writerId = $request->validate([
            'tech_writer_id' => ['required', 'integer', 'exists:users,id'],
        ])['tech_writer_id'];

        try {
            $workflow->reassignWriter($article, (int) $writerId);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Reassign failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('lead.articles.show', $article)
            ->with('success', 'Article reassigned.');
    }

    public function comment(Request $request, Article $article): RedirectResponse
    {
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

    public function downloadCurrent(Article $article, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        return $this->downloadDriveFile($article, $article->current_drive_file_id, $drive);
    }

    public function downloadSource(Article $article, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        return $this->downloadDriveFile($article, $article->source_drive_file_id, $drive);
    }

    public function downloadAsset(Article $article, ArticleAsset $asset, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
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

    private function downloadDriveFile(Article $article, ?string $fileId, GoogleDriveService $drive): BinaryFileResponse|RedirectResponse
    {
        if (! $fileId) {
            return back()->with('error', 'No file is attached.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'article_');

        try {
            $drive->downloadFile($fileId, $tempPath);
            $meta = $drive->getFileMetadata($fileId);
        } catch (DriveException $e) {
            @unlink($tempPath);
            return back()->with('error', $e->getMessage());
        }

        return response()
            ->download($tempPath, $meta['name'] ?? "{$article->article_code}.bin")
            ->deleteFileAfterSend();
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
            ->route('lead.articles.show', $article)
            ->with('success', $successMessage);
    }
}
