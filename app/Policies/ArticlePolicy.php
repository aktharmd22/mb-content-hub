<?php

namespace App\Policies;

use App\Enums\ArticleStage;
use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'sales', 'tech_team'], true);
    }

    public function view(User $user, Article $article): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isTechTeam()) return true; // anyone on the tech team can see any article
        if ($article->sales_rep_id === $user->id) return true;

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSales() || $user->isAdmin();
    }

    public function update(User $user, Article $article): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isSales() && $article->sales_rep_id === $user->id) return true;
        return false;
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->isAdmin();
    }

    public function comment(User $user, Article $article): bool
    {
        return $this->view($user, $article);
    }

    public function assign(User $user): bool
    {
        return $user->isAdmin();
    }

    public function review(User $user, Article $article): bool
    {
        return $user->isTechTeam() && $article->current_stage === ArticleStage::INTERNAL_REVIEW;
    }

    public function work(User $user, Article $article): bool
    {
        return $user->isTechTeam()
            && $article->tech_writer_id === $user->id
            && in_array($article->current_stage, [
                ArticleStage::ASSIGNED,
                ArticleStage::IN_PROGRESS,
                ArticleStage::REVISIONS,
            ], true);
    }
}
