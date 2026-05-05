<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStage;
use App\Exceptions\DriveException;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use App\Services\ArticleWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(): View
    {
        $articles = Article::where('current_stage', ArticleStage::INBOX->value)
            ->with(['client', 'salesRep'])
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->paginate(25);

        $writers = User::where('role', 'tech_team')
            ->where('is_active', true)
            ->withCount([
                'articlesAsTechWriter as active_count' => fn ($q) => $q->whereIn('current_stage', [
                    ArticleStage::ASSIGNED->value,
                    ArticleStage::IN_PROGRESS->value,
                    ArticleStage::REVISIONS->value,
                ]),
            ])
            ->orderBy('name')
            ->get();

        return view('admin.assignments.index', compact('articles', 'writers'));
    }

    public function assign(Request $request, Article $article, ArticleWorkflowService $workflow): RedirectResponse
    {
        $writerId = $request->validate([
            'tech_writer_id' => ['required', 'integer', 'exists:users,id'],
        ])['tech_writer_id'];

        try {
            $workflow->assignArticle($article, (int) $writerId);
        } catch (DriveException|WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Assign failed: ' . $e->getMessage());
        }

        return back()->with('success', "Article {$article->article_code} assigned.");
    }
}
