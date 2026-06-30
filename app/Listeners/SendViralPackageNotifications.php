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
        // Notify every distinct tech member who owns at least one deliverable
        // (Article / Posts / Reel / Landing can each have a different owner).
        $recipients = $this->deliverableOwners($package);

        if ($recipients->isEmpty() && $package->techTeam) {
            $recipients = collect([$package->techTeam]);
        }

        Notification::send($recipients, new ViralPackageNotification($package, 'created'));
    }

    /** Distinct, active users assigned to any deliverable on the package. */
    private function deliverableOwners($package)
    {
        $ids = $package->deliverables()->whereNotNull('assigned_to')->pluck('assigned_to')->unique();

        return $ids->isEmpty()
            ? collect()
            : User::whereIn('id', $ids)->get();
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
        // Notify the person who owns this specific deliverable; fall back to the lead.
        $tech = $deliverable?->assignee ?? $package->techTeam;
        if ($tech) {
            $tech->notify(new ViralPackageNotification($package, 'correction_requested', $deliverable));
        }
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
        // Notify sales rep + every tech member who owns a deliverable
        $recipients = $this->deliverableOwners($package)
            ->push($package->salesRep)
            ->push($package->techTeam)
            ->filter()
            ->unique('id');

        Notification::send($recipients, new ViralPackageNotification($package, 'completed'));
    }
}
