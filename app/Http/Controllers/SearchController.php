<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->get('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['articles' => [], 'clients' => [], 'users' => []]);
        }

        $user = $request->user();
        $like = "%{$term}%";

        $articles = Article::query()
            ->with('client')
            ->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                  ->orWhere('article_code', 'like', $like);
            })
            ->when(! $user->isAdmin() && ! $user->isTechLead(), function ($q) use ($user) {
                $q->where(function ($w) use ($user) {
                    $w->where('sales_rep_id', $user->id)
                      ->orWhere('tech_writer_id', $user->id);
                });
            })
            ->limit(8)
            ->get()
            ->map(fn ($a) => [
                'type'        => 'article',
                'id'          => $a->id,
                'code'        => $a->article_code,
                'title'       => $a->title,
                'meta'        => $a->client?->name ?? '—',
                'url'         => $this->articleUrl($a, $user),
                'stage'       => $a->current_stage->value,
                'stage_label' => $a->current_stage->label(),
                'stage_color' => $a->current_stage->color(),
            ]);

        $clients = Client::query()
            ->where('name', 'like', $like)
            ->orWhere('company', 'like', $like)
            ->withCount('articles')
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'type' => 'client',
                'id'   => $c->id,
                'name' => $c->name,
                'meta' => trim(($c->company ?? '') . ' · ' . $c->articles_count . ' articles', ' ·'),
                'url'  => $user->isSales() || $user->isAdmin()
                    ? route('sales.clients.edit', $c)
                    : '#',
            ]);

        $users = collect();
        if ($user->isAdmin()) {
            $users = User::query()
                ->where(function ($q) use ($like) {
                    $q->where('username', 'like', $like)
                      ->orWhere('name', 'like', $like)
                      ->orWhere('email', 'like', $like);
                })
                ->limit(5)
                ->get()
                ->map(fn ($u) => [
                    'type' => 'user',
                    'id'   => $u->id,
                    'name' => $u->name,
                    'meta' => '@' . $u->username . ' · ' . $u->role_label,
                    'url'  => route('admin.users.edit', $u),
                ]);
        }

        return response()->json([
            'articles' => $articles,
            'clients'  => $clients,
            'users'    => $users,
        ]);
    }

    private function articleUrl(Article $article, User $user): string
    {
        return match ($user->role) {
            'admin'       => route('admin.articles.index', ['q' => $article->article_code]),
            'sales'       => route('sales.articles.show', $article),
            'tech_team' => route('writer.articles.show', $article),
            'tech_team'   => route('lead.articles.show', $article),
            default       => '#',
        };
    }
}
