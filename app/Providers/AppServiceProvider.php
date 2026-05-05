<?php

namespace App\Providers;

use App\Services\ArticleWorkflowService;
use App\Services\GoogleDriveService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GoogleDriveService::class);
        $this->app->singleton(ArticleWorkflowService::class);
    }

    public function boot(): void
    {
        Paginator::useTailwind();
    }
}
