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
            ->line("Client: {$this->article->client?->displayName()}")
            ->action(
                $notifiable->isAdmin() ? 'Assign writer' : 'View inbox',
                $this->urlFor($notifiable),
            );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'article_submitted',
            'article_id'     => $this->article->id,
            'article_code'   => $this->article->article_code,
            'article_title'  => $this->article->title,
            'message'        => "{$this->article->article_code} submitted, needs assignment",
            'url'            => $this->urlFor($notifiable),
        ];
    }

    /** Admins land on the assignment queue; tech team lands on the inbox they can pick up from. */
    private function urlFor(object $notifiable): string
    {
        return $notifiable->isAdmin()
            ? route('admin.assignments.index')
            : route('writer.articles.index', ['stage' => 'inbox']);
    }
}
