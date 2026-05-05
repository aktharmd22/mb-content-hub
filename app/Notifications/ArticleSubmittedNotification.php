<?php

namespace App\Notifications;

use App\Models\Article;
use App\Notifications\Concerns\RespectsUserChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArticleSubmittedNotification extends Notification
{
    use Queueable, RespectsUserChannels;

    public function __construct(public readonly Article $article) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New article submitted: {$this->article->article_code}")
            ->greeting("Hi {$notifiable->name},")
            ->line("A new article has been submitted and needs assignment.")
            ->line("**{$this->article->article_code}** — {$this->article->title}")
            ->line("Submitted by: {$this->article->salesRep?->name}")
            ->line("Client: {$this->article->client?->name}")
            ->action('Assign writer', route('admin.assignments.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'article_submitted',
            'article_id'     => $this->article->id,
            'article_code'   => $this->article->article_code,
            'article_title'  => $this->article->title,
            'message'        => "{$this->article->article_code} submitted, needs assignment",
            'url'            => route('admin.assignments.index'),
        ];
    }
}
