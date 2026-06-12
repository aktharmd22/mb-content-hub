<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Notifications\Concerns\RespectsUserChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketUpdatedNotification extends Notification
{
    use Queueable, RespectsUserChannels;

    /** @param string $kind 'status' | 'assigned' | 'bounced' */
    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly string $kind = 'status',
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $headline = match ($this->kind) {
            'assigned' => "Ticket {$this->ticket->code} assigned to you",
            'bounced'  => "Ticket {$this->ticket->code} bounced back to Admin pool",
            default    => "Update on ticket {$this->ticket->code}",
        };

        return (new MailMessage)
            ->subject($headline)
            ->greeting("Hi {$notifiable->name},")
            ->line($headline)
            ->line("**Subject:** {$this->ticket->subject}")
            ->line("**Status:** {$this->ticket->statusLabel()}")
            ->action('View ticket', route('support.show', $this->ticket));
    }

    public function toArray(object $notifiable): array
    {
        $message = match ($this->kind) {
            'assigned' => "Ticket {$this->ticket->code} assigned to you",
            'bounced'  => "Ticket {$this->ticket->code} bounced back to Admin pool",
            default    => "Ticket {$this->ticket->code} updated · {$this->ticket->statusLabel()}",
        };

        return [
            'type'           => 'support_ticket_updated',
            'ticket_id'      => $this->ticket->id,
            'ticket_code'    => $this->ticket->code,
            'ticket_subject' => $this->ticket->subject,
            'kind'           => $this->kind,
            'message'        => $message,
            'url'            => route('support.show', $this->ticket),
        ];
    }
}
