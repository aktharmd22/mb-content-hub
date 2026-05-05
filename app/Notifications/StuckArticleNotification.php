<?php

namespace App\Notifications;

use App\Models\Article;
use App\Notifications\Concerns\RespectsUserChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StuckArticleNotification extends Notification
{
    use Queueable, RespectsUserChannels;

    public function __construct(
        public readonly Article $article,
        public readonly int $daysInStage,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Article stuck: {$this->article->article_code}")
            ->greeting("Hi {$notifiable->name},")
            ->line("This article has been in **{$this->article->current_stage->label()}** for {$this->daysInStage} days.")
            ->line("**{$this->article->article_code}** — {$this->article->title}")
            ->line("Writer: {$this->article->techWriter?->name ?? 'unassigned'}")
            ->line("Sales: {$this->article->salesRep?->name}")
            ->action('Open article', route('admin.articles.index', ['q' => $this->article->article_code]));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'stuck_article',
            'article_id'     => $this->article->id,
            'article_code'   => $this->article->article_code,
            'article_title'  => $this->article->title,
            'stage'          => $this->article->current_stage->value,
            'days_in_stage'  => $this->daysInStage,
            'message'        => "{$this->article->article_code} stuck in {$this->article->current_stage->label()} for {$this->daysInStage}d",
            'url'            => route('admin.articles.index', ['q' => $this->article->article_code]),
        ];
    }
}
