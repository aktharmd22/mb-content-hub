<?php

namespace App\Listeners;

use App\Events\ViralPackageEvent;
use App\Models\User;
use App\Models\ViralPackageDeliverable;
use App\Notifications\ViralPackageNotification;
use Illuminate\Support\Facades\Notification;

class SendViralPackageNotifications
{
    public function handle(ViralPackageEvent $event): void
    {
        $package = $event->package;

        match ($event->action) {
            'created'               => $this->onCreated($package),
            'deliverable_submitted' => $this->onDeliverableSubmitted($package, $event->context['deliverable_id'] ?? null),
            'correction_requested'  => $this->onCorrectionRequested($package, $event->context['deliverable_id'] ?? null),
            'all_approved'          => $this->onAllApproved($package),
            'completed'             => $this->onCompleted($package),
            default                 => null,
        };
    }

    private function onCreated($package): void
    {
        $techTeam = User::where('role', 'tech_team')->where('is_active', true)->get();
        Notification::send($techTeam, new ViralPackageNotification($package, 'created'));
    }

    private function onDeliverableSubmitted($package, ?int $deliverableId): void
    {
        $deliverable = $deliverableId ? ViralPackageDeliverable::find($deliverableId) : null;
        $salesRep = $package->salesRep;
        if ($salesRep) {
            $salesRep->notify(new ViralPackageNotification($package, 'deliverable_submitted', $deliverable));
        }
    }

    private function onCorrectionRequested($package, ?int $deliverableId): void
    {
        $deliverable = $deliverableId ? ViralPackageDeliverable::find($deliverableId) : null;
        $techTeam = User::where('role', 'tech_team')->where('is_active', true)->get();
        Notification::send($techTeam, new ViralPackageNotification($package, 'correction_requested', $deliverable));
    }

    private function onAllApproved($package): void
    {
        $salesRep = $package->salesRep;
        if ($salesRep) {
            $salesRep->notify(new ViralPackageNotification($package, 'all_approved'));
        }
    }

    private function onCompleted($package): void
    {
        $recipients = collect([$package->salesRep])
            ->merge(User::where('role', 'tech_team')->where('is_active', true)->get())
            ->filter()
            ->unique('id');

        Notification::send($recipients, new ViralPackageNotification($package, 'completed'));
    }
}
