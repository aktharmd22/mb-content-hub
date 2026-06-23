<?php

namespace App\Providers;

use App\Events\ArticleStageTransitioned;
use App\Events\ViralPackageEvent;
use App\Listeners\SendStageTransitionNotifications;
use App\Listeners\SendViralPackageNotifications;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Explicit event → listener map. Registered here (instead of relying on
     * auto-discovery) so notifications keep working even when the event cache
     * is stale — e.g. after `php artisan event:cache` ran before a listener existed.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ArticleStageTransitioned::class => [
            SendStageTransitionNotifications::class,
        ],
        ViralPackageEvent::class => [
            SendViralPackageNotifications::class,
        ],
    ];

    /**
     * Disable auto-discovery so the explicit map above is the single source of
     * truth — prevents double-registration (which would send duplicate notifications).
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
