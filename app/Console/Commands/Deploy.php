<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Deploy extends Command
{
    protected $signature = 'deploy';

    protected $description = 'Post-deploy steps: run migrations and clear all stale caches (config, routes, views, events)';

    public function handle(): int
    {
        $this->info('→ Running migrations...');
        $this->call('migrate', ['--force' => true]);

        $this->newLine();
        $this->info('→ Clearing stale caches (config, routes, views, events)...');
        $this->call('optimize:clear');

        $this->newLine();
        $this->info('✅ Deploy complete. New routes, events, and views are now live.');

        return self::SUCCESS;
    }
}
