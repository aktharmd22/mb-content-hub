<?php

namespace App\Listeners;

use App\Enums\ArticleStage;
use App\Events\ArticleStageTransitioned;
use App\Models\User;
use App\Notifications\ApprovedNotification;
use App\Notifications\ArticleAssignedNotification;
use App\Notifications\ArticleSubmittedNotification;
use App\Notifications\ReviewRequiredNotification;
use App\Notifications\RevisionRequestedNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class SendStageTransitionNotifications
{
    public function handle(ArticleStageTransitioned $event): void
    {
        $article = $event->article;

        match ($event->toStage) {
            // Sales just submitted — notify both admins (so they can assign) and the tech team (so they see what's incoming).
            ArticleStage::INBOX => $this->onSubmitted($article),

            // Admin assigned a writer — notify just that writer.
            ArticleStage::ASSIGNED => $this->notify($article->techWriter, new ArticleAssignedNotification($article)),

            // Legacy stage (kept for back-compat with old data) — alert tech team if anything still lands here.
            ArticleStage::INTERNAL_REVIEW => $this->notifyTechTeam(new ReviewRequiredNotification($article)),

            // Sales bounced it back for correction — assigned writer needs to act.
            ArticleStage::REVISIONS => $this->notify($article->techWriter, new RevisionRequestedNotification($article, $event->notes)),

            // Tech team submitted rewrite (or correction) — sales needs to review.
            ArticleStage::CLIENT_APPROVAL => $this->notify($article->salesRep, new ApprovedNotification($article)),

            // Sales verified the corrected version — tech team can now publish.
            ArticleStage::APPROVED => $this->notify($article->techWriter, new ApprovedNotification($article)),

            default => null, // IN_PROGRESS, PUBLISHED — no automated notification
        };
    }

    private function onSubmitted($article): void
    {
        $notification = new ArticleSubmittedNotification($article);
        $this->notifyAdmins($notification);
        $this->notifyTechTeam($notification);
    }

    private function notify(?User $user, Notification $notification): void
    {
        if (! $user || ! $user->is_active) return;
        $user->notify($notification);
    }

    private function notifyAdmins(Notification $notification): void
    {
        NotificationFacade::send(
            User::where('role', 'admin')->where('is_active', true)->get(),
            $notification,
        );
    }

    private function notifyTechTeam(Notification $notification): void
    {
        NotificationFacade::send(
            User::where('role', 'tech_team')->where('is_active', true)->get(),
            $notification,
        );
    }
}
