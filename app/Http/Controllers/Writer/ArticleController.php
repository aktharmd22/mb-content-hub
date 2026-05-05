<?php

namespace App\Http\Controllers\Writer;

use App\Enums\ArticleStage;
use App\Exceptions\DriveException;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Writer\SubmitForReviewRequest;
use App\Models\Article;
use App\Models\ArticleAsset;
use App\Models\Comment;
use App\Services\ArticleWorkflowService;
use App\Services\GoogleDriveService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $stages = ArticleStage::cases();
        $userId = auth()->id();

        // Show: (a) articles assigned to me + (b) Inbox articles waiting to be picked up.
        // The view marks Inbox rows as "available" so they're visually distinct from your work.
        $articles = Article::query()
            ->with(['client', 'salesRep', 'articleType'])
            ->where(function ($q) use ($userId) {
                $q->where('tech_writer_id', $userId)
                  ->orWhere('current_stage', ArticleStage::INBOX->value);
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->get('q'));
                $q->where(function ($w) use ($term) {
                    $w->where('title', 'like', "%{$term}%")
                      ->orWhere('article_code', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('stage'), fn ($q) => $q->where('current_stage', $request->get('stage')))
            // Inbox (available to pick up) first, then assigned work.
            // Within Inbox: newest submission at the top. Within assigned: nearest deadline first.
            ->orderByRaw('CASE WHEN current_stage = "inbox" THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN current_stage = "inbox" THEN submitted_at END DESC')
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->orderByDesc('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('writer.articles.index', compact('articles', 'stages'));
    }

    public function pickUp(Article $article, ArticleWorkflowService $workflow): RedirectResponse
    {
        try {
            $workflow->selfAssign($article);
        } catch (DriveException|WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Could not pick up article: ' . $e->getMessage());
        }

        return redirect()
            ->route('writer.articles.show', $article)
            ->with('success', "Picked up {$article->article_code}.");
    }

    public function updatePublishedUrl(Request $request, Article $article): RedirectResponse
    {
        $this->ensureAssignedToMe($article);

        if ($article->current_stage !== ArticleStage::PUBLISHED) {
            return back()->with('error', 'URL can only be edited on published articles.');
        }

        $url = $request->validate([
            'published_url' => ['required', 'url', 'max:500'],
        ])['published_url'];

        $article->update(['published_url' => $url]);

        return redirect()
            ->route('writer.articles.show', $article)
            ->with('success', 'Published URL updated.');
    }

    public function publish(Request $request, Article $article, ArticleWorkflowService $workflow): RedirectResponse
    {
        $url = $request->validate([
            'published_url' => ['required', 'url', 'max:500'],
        ])['published_url'];

        try {
            $workflow->markPublished($article, $url);
        } catch (DriveException|WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Publish failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('writer.articles.show', $article)
            ->with('success', 'Marked as published.');
    }

    public function destroy(Article $article, GoogleDriveService $drive): RedirectResponse
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins can delete articles.');
        }

        // Try to remove the Drive file too — best effort, don't block deletion if it fails.
        if ($article->current_drive_file_id) {
            try {
                $drive->deleteFile($article->current_drive_file_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $article->delete();

        return back()->with('success', "Article {$article->article_code} deleted.");
    }

    public function show(Article $article): View
    {
        $this->ensureAssignedToMe($article);

        $article->load([
            'client', 'salesRep', 'techWriter', 'techLead',
            'history.changedBy',
            'comments.user',
            'assets',
        ]);

        return view('writer.articles.show', compact('article'));
    }

    public function start(Article $article, ArticleWorkflowService $workflow): RedirectResponse
    {
        $this->ensureAssignedToMe($article);
        return $this->runTransition(fn () => $workflow->startWork($article), 'Started working.', $article);
    }

    public function submitForReview(SubmitForReviewRequest $request, Article $article, ArticleWorkflowService $workflow): RedirectResponse
    {
        $this->ensureAssignedToMe($article);
        return $this->runTransition(
            fn () => $workflow->submitForReview($article, $request->file('file'), notes: $request->input('notes')),
            'Submitted for review.',
            $article,
        );
    }

    public function comment(Request $request, Article $article): RedirectResponse
    {
        $this->ensureAssignedToMe($article);

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
        $this->ensureAssignedToMe($article);

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
        $this->ensureAssignedToMe($article);

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

    private function ensureAssignedToMe(Article $article): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return;
        }
        // Tech-team members can view any Inbox article (so they can decide whether to pick it up).
        if ($user->isTechTeam() && $article->current_stage === \App\Enums\ArticleStage::INBOX) {
            return;
        }
        if (! $user->isTechTeam() || $article->tech_writer_id !== $user->id) {
            throw new AuthorizationException('This article is not assigned to you.');
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
            ->route('writer.articles.show', $article)
            ->with('success', $successMessage);
    }
}
