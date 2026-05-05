<?php

namespace App\Notifications;

use App\Enums\ArticleStage;
use App\Models\Article;
use App\Notifications\Concerns\RespectsUserChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovedNotification extends Notification
{
    use Queueable, RespectsUserChannels;

    public function __construct(public readonly Article $article) {}

    public function toMail(object $notifiable): MailMessage
    {
        // Two distinct triggers for this notification:
        //   1) CLIENT_APPROVAL — tech team submitted; sales must review.
        //   2) APPROVED        — sales verified; tech team can publish.
        return $this->isForTechTeam($notifiable)
            ? $this->mailForTechTeam($notifiable)
            : $this->mailForSales($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        if ($this->isForTechTeam($notifiable)) {
            return [
                'type'           => 'verified_publish',
                'article_id'     => $this->article->id,
                'article_code'   => $this->article->article_code,
                'article_title'  => $this->article->title,
                'message'        => "{$this->article->article_code} verified — ready to publish",
                'url'            => route('writer.articles.show', $this->article),
            ];
        }

        return [
            'type'           => 'sales_review_required',
            'article_id'     => $this->article->id,
            'article_code'   => $this->article->article_code,
            'article_title'  => $this->article->title,
            'message'        => "{$this->article->article_code} ready for your review",
            'url'            => route('sales.articles.show', $this->article),
        ];
    }

    private function isForTechTeam(object $notifiable): bool
    {
        return ($notifiable->role ?? null) === 'tech_team'
            && $this->article->current_stage === ArticleStage::APPROVED;
    }

    private function mailForSales(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ready for review: {$this->article->article_code}")
            ->greeting("Hi {$notifiable->name},")
            ->line("The tech team has uploaded a rewrite. Please check it.")
            ->line("**{$this->article->article_code}** — {$this->article->title}")
            ->when($this->article->client, fn ($m) => $m->line("Client: {$this->article->client->name}"))
            ->action('Review article', route('sales.articles.show', $this->article))
            ->line("If something needs fixing, send it back for correction. Otherwise mark it as verified.");
    }

    private function mailForTechTeam(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ready to publish: {$this->article->article_code}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Sales verified the article. You can publish it now.")
            ->line("**{$this->article->article_code}** — {$this->article->title}")
            ->action('Open article', route('writer.articles.show', $this->article));
    }
}
