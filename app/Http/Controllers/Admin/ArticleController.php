<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStage;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $articles = Article::with(['client', 'salesRep', 'techWriter'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->get('q'));
                $q->where(function ($w) use ($term) {
                    $w->where('title', 'like', "%{$term}%")
                      ->orWhere('article_code', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('stage'), fn ($q) => $q->where('current_stage', $request->get('stage')))
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->get('client_id')))
            ->when($request->filled('sales_rep_id'), fn ($q) => $q->where('sales_rep_id', $request->get('sales_rep_id')))
            ->when($request->filled('tech_writer_id'), fn ($q) => $q->where('tech_writer_id', $request->get('tech_writer_id')))
            ->when($request->filled('from'), fn ($q) => $q->where('submitted_at', '>=', $request->get('from')))
            ->when($request->filled('to'),   fn ($q) => $q->where('submitted_at', '<=', $request->get('to') . ' 23:59:59'))
            ->orderByDesc('submitted_at')
            ->paginate(25)
            ->withQueryString();

        $stages    = ArticleStage::cases();
        $clients   = Client::orderBy('name')->get(['id', 'name']);
        $salesReps = User::where('role', 'sales')->orderBy('name')->get(['id', 'name']);
        $writers   = User::where('role', 'tech_team')->orderBy('name')->get(['id', 'name']);

        return view('admin.articles.index', compact('articles', 'stages', 'clients', 'salesReps', 'writers'));
    }

    public function export(Request $request): StreamedResponse
    {
        $articles = Article::with(['client', 'salesRep', 'techWriter', 'techLead'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim((string) $request->get('q'));
                $q->where(function ($w) use ($term) {
                    $w->where('title', 'like', "%{$term}%")
                      ->orWhere('article_code', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('stage'), fn ($q) => $q->where('current_stage', $request->get('stage')))
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->get('client_id')))
            ->when($request->filled('sales_rep_id'), fn ($q) => $q->where('sales_rep_id', $request->get('sales_rep_id')))
            ->when($request->filled('tech_writer_id'), fn ($q) => $q->where('tech_writer_id', $request->get('tech_writer_id')))
            ->when($request->filled('from'), fn ($q) => $q->where('submitted_at', '>=', $request->get('from')))
            ->when($request->filled('to'),   fn ($q) => $q->where('submitted_at', '<=', $request->get('to') . ' 23:59:59'))
            ->orderByDesc('submitted_at')
            ->get();

        $filename = 'articles-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($articles) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Code', 'Title', 'Client', 'Sales rep', 'Writer', 'Tech lead',
                'Stage', 'Priority', 'Deadline', 'Word target',
                'Submitted at', 'Published at', 'Published URL',
            ]);

            foreach ($articles as $a) {
                fputcsv($out, [
                    $a->article_code,
                    $a->title,
                    $a->client?->name,
                    $a->salesRep?->name,
                    $a->techWriter?->name,
                    $a->techLead?->name,
                    $a->current_stage->label(),
                    $a->priority,
                    $a->deadline?->format('Y-m-d'),
                    $a->word_count_target,
                    $a->submitted_at?->format('Y-m-d H:i'),
                    $a->published_at?->format('Y-m-d H:i'),
                    $a->published_url,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(Article $article, \App\Services\GoogleDriveService $drive): RedirectResponse
    {
        // Best-effort delete the Drive file too — don't block deletion if it fails.
        if ($article->current_drive_file_id) {
            try {
                $drive->deleteFile($article->current_drive_file_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $code = $article->article_code;
        $article->delete();

        return back()->with('success', "Article {$code} deleted.");
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action'         => ['required', Rule::in(['reassign_writer', 'change_deadline', 'archive'])],
            'article_ids'    => ['required', 'array', 'min:1'],
            'article_ids.*'  => ['integer', 'exists:articles,id'],
            'tech_writer_id' => ['required_if:action,reassign_writer', 'nullable', 'integer', 'exists:users,id'],
            'deadline'       => ['required_if:action,change_deadline', 'nullable', 'date'],
        ]);

        $articles = Article::whereIn('id', $data['article_ids']);

        match ($data['action']) {
            'reassign_writer' => $articles->update(['tech_writer_id' => $data['tech_writer_id']]),
            'change_deadline' => $articles->update(['deadline' => $data['deadline']]),
            'archive'         => $articles->each->delete(),
        };

        $count = count($data['article_ids']);
        $verb  = match ($data['action']) {
            'reassign_writer' => 'reassigned',
            'change_deadline' => 'updated',
            'archive'         => 'archived',
        };

        return back()->with('success', "{$count} article" . ($count > 1 ? 's' : '') . " {$verb}.");
    }
}
