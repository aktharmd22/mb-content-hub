<?php

namespace App\Notifications;

use App\Models\Article;
use App\Notifications\Concerns\RespectsUserChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewRequiredNotification extends Notification
{
    use Queueable, RespectsUserChannels;

    public function __construct(public readonly Article $article) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Review needed: {$this->article->article_code}")
            ->greeting("Hi {$notifiable->name},")
            ->line("A rewrite is ready for your review.")
            ->line("**{$this->article->article_code}** — {$this->article->title}")
            ->line("Writer: {$this->article->techWriter?->name}")
            ->line("Client: {$this->article->client?->name}")
            ->action('Review article', route('lead.articles.show', $this->article));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'review_required',
            'article_id'     => $this->article->id,
            'article_code'   => $this->article->article_code,
            'article_title'  => $this->article->title,
            'message'        => "{$this->article->article_code} ready for your review",
            'url'            => route('lead.articles.show', $this->article),
        ];
    }
}
