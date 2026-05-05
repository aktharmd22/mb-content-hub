<?php

namespace App\Notifications\Concerns;

trait RespectsUserChannels
{
    /**
     * Channels honor the user's email_notifications_enabled toggle.
     * Database (in-app) is always on so the bell badge stays accurate.
     *
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (($notifiable->email_notifications_enabled ?? true) && ! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
