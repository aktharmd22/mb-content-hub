<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Notifications\Concerns\RespectsUserChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketCreatedNotification extends Notification
{
    use Queueable, RespectsUserChannels;

    public function __construct(public readonly SupportTicket $ticket) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New support ticket: {$this->ticket->code}")
            ->greeting("Hi {$notifiable->name},")
            ->line("A new support ticket has been raised.")
            ->line("**{$this->ticket->code}** — {$this->ticket->subject}")
            ->line("Reported by: {$this->ticket->reporter?->name}")
            ->line("Priority: {$this->ticket->priorityLabel()}")
            ->action('View ticket', route('support.show', $this->ticket));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'support_ticket_created',
            'ticket_id'      => $this->ticket->id,
            'ticket_code'    => $this->ticket->code,
            'ticket_subject' => $this->ticket->subject,
            'message'        => "New support ticket {$this->ticket->code}: {$this->ticket->subject}",
            'url'            => route('support.show', $this->ticket),
        ];
    }
}
