<?php

namespace App\Notifications;

use App\Models\Article;
use App\Notifications\Concerns\RespectsUserChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RevisionRequestedNotification extends Notification
{
    use Queueable, RespectsUserChannels;

    public function __construct(
        public readonly Article $article,
        public readonly ?string $reason = null,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $msg = (new MailMessage)
            ->subject("Revision needed: {$this->article->article_code}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your article needs some revisions before it can move forward.")
            ->line("**{$this->article->article_code}** — {$this->article->title}");

        if ($this->reason) {
            $msg->line("**Feedback:** {$this->reason}");
        }

        return $msg
            ->action('Review feedback', route('writer.articles.show', $this->article));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'revision_requested',
            'article_id'     => $this->article->id,
            'article_code'   => $this->article->article_code,
            'article_title'  => $this->article->title,
            'reason'         => $this->reason,
            'message'        => "Revision requested on {$this->article->article_code}",
            'url'            => route('writer.articles.show', $this->article),
        ];
    }
}
