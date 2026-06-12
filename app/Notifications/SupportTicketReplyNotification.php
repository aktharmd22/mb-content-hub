<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Notifications\Concerns\RespectsUserChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketReplyNotification extends Notification
{
    use Queueable, RespectsUserChannels;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly SupportTicketReply $reply,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $preview = \Illuminate\Support\Str::limit($this->reply->body, 140);

        return (new MailMessage)
            ->subject("New reply on {$this->ticket->code}: {$this->ticket->subject}")
            ->greeting("Hi {$notifiable->name},")
            ->line("**{$this->reply->user?->name}** replied on ticket {$this->ticket->code}.")
            ->line("> {$preview}")
            ->action('View ticket', route('support.show', $this->ticket));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'support_ticket_reply',
            'ticket_id'      => $this->ticket->id,
            'ticket_code'    => $this->ticket->code,
            'ticket_subject' => $this->ticket->subject,
            'replier'        => $this->reply->user?->name,
            'message'        => "{$this->reply->user?->name} replied on {$this->ticket->code}",
            'url'            => route('support.show', $this->ticket),
        ];
    }
}
