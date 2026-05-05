<?php

namespace App\Notifications;

use App\Models\Article;
use App\Notifications\Concerns\RespectsUserChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeadlineApproachingNotification extends Notification
{
    use Queueable, RespectsUserChannels;

    public function __construct(public readonly Article $article) {}

    public function toMail(object $notifiable): MailMessage
    {
        $days = $this->article->days_until_deadline ?? 0;
        $when = $days < 0
            ? abs($days) . ' day' . (abs($days) === 1 ? '' : 's') . ' overdue'
            : ($days === 0 ? 'today' : "in {$days} day" . ($days === 1 ? '' : 's'));

        return (new MailMessage)
            ->subject("Deadline " . ($days < 0 ? 'overdue' : 'approaching') . ": {$this->article->article_code}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Heads up — your article's deadline is {$when}.")
            ->line("**{$this->article->article_code}** — {$this->article->title}")
            ->line("Stage: {$this->article->current_stage->label()}")
            ->action('Open article', $this->urlFor($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'deadline_approaching',
            'article_id'     => $this->article->id,
            'article_code'   => $this->article->article_code,
            'article_title'  => $this->article->title,
            'days_left'      => $this->article->days_until_deadline,
            'message'        => "Deadline approaching for {$this->article->article_code}",
            'url'            => $this->urlFor($notifiable),
        ];
    }

    private function urlFor(object $notifiable): string
    {
        return match ($notifiable->role ?? null) {
            'tech_team' => route('writer.articles.show', $this->article),
            'tech_team'   => route('lead.articles.show', $this->article),
            'sales'       => route('sales.articles.show', $this->article),
            default       => route('admin.articles.index', ['q' => $this->article->article_code]),
        };
    }
}
