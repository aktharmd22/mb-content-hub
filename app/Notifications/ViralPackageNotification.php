<?php

namespace App\Notifications;

use App\Models\ViralPackage;
use App\Models\ViralPackageDeliverable;
use App\Notifications\Concerns\RespectsUserChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ViralPackageNotification extends Notification
{
    use Queueable, RespectsUserChannels;

    public function __construct(
        public readonly ViralPackage $package,
        public readonly string $kind,
        public readonly ?ViralPackageDeliverable $deliverable = null
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $clientName = $this->package->client?->name ?? 'a client';
        $url = $this->urlFor($notifiable);

        return match ($this->kind) {
            'created' => (new MailMessage)
                ->subject("New viral package for {$clientName}")
                ->greeting("Hi {$notifiable->name},")
                ->line("A new viral package has been created for **{$clientName}**.")
                ->line('It contains 7 deliverables: 1 Article, 5 Social posts, 1 Reel.')
                ->action('Open package', $url),

            'deliverable_submitted' => (new MailMessage)
                ->subject("{$clientName} — {$this->deliverable?->title} ready for review")
                ->greeting("Hi {$notifiable->name},")
                ->line("**{$this->deliverable?->title}** for **{$clientName}** is ready for your review.")
                ->action('Review', $url),

            'correction_requested' => (new MailMessage)
                ->subject("{$clientName} — {$this->deliverable?->title} needs changes")
                ->greeting("Hi {$notifiable->name},")
                ->line("Sales has requested changes on **{$this->deliverable?->title}** for **{$clientName}**.")
                ->action('Open deliverable', $url),

            'all_approved' => (new MailMessage)
                ->subject("{$clientName} — all 7 deliverables approved")
                ->greeting("Hi {$notifiable->name},")
                ->line("All 7 deliverables for **{$clientName}** have been approved.")
                ->line('You can now mark the package as delivered.')
                ->action('Open package', $url),

            'completed' => (new MailMessage)
                ->subject("{$clientName} — viral package delivered")
                ->greeting("Hi {$notifiable->name},")
                ->line("The viral package for **{$clientName}** has been marked as delivered. 🎉"),

            default => (new MailMessage)->subject('Viral package update')->line('There is a viral package update.'),
        };
    }

    public function toArray(object $notifiable): array
    {
        $clientName = $this->package->client?->name ?? 'client';
        $deliverableTitle = $this->deliverable?->title;

        $message = match ($this->kind) {
            'created'               => "New viral package for {$clientName} — 7 deliverables ready",
            'deliverable_submitted' => "{$clientName} — {$deliverableTitle} ready for review",
            'correction_requested'  => "{$clientName} — {$deliverableTitle} needs changes",
            'all_approved'          => "{$clientName} — all 7 deliverables approved, ready to mark delivered",
            'completed'             => "{$clientName} — viral package delivered",
            default                 => 'Viral package update',
        };

        return [
            'type'             => 'viral_package_' . $this->kind,
            'kind'             => $this->kind,
            'viral_package_id' => $this->package->id,
            'deliverable_id'   => $this->deliverable?->id,
            'message'          => $message,
            'url'              => $this->urlFor($notifiable),
        ];
    }

    private function urlFor(object $notifiable): string
    {
        return match ($notifiable->role ?? 'sales') {
            'tech_team' => route('writer.viral-packages.show', $this->package),
            'sales'     => route('sales.viral-packages.show', $this->package),
            'admin'     => route('admin.viral-packages.show', $this->package),
            default     => route('sales.viral-packages.show', $this->package),
        };
    }
}
