<?php

namespace App\Notifications;

use App\Models\Article;
use App\Notifications\Concerns\RespectsUserChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArticleAssignedNotification extends Notification
{
    use Queueable, RespectsUserChannels;

    public function __construct(public readonly Article $article) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Article assigned: {$this->article->article_code}")
            ->greeting("Hi {$notifiable->name},")
            ->line("You've been assigned a new article to write.")
            ->line("**{$this->article->article_code}** — {$this->article->title}")
            ->line("Client: {$this->article->client?->displayName()}")
            ->when($this->article->deadline, fn ($m) => $m->line("Deadline: {$this->article->deadline->format('M j, Y')}"))
            ->action('Open article', route('writer.articles.show', $this->article))
            ->line('Good luck!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'article_assigned',
            'article_id'     => $this->article->id,
            'article_code'   => $this->article->article_code,
            'article_title'  => $this->article->title,
            'message'        => "You've been assigned to {$this->article->article_code}",
            'url'            => route('writer.articles.show', $this->article),
        ];
    }
}
