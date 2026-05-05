<?php

namespace App\Console\Commands;

use App\Exceptions\DriveException;
use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class DriveSetup extends Command
{
    protected $signature = 'drive:setup
                            {--name=Malayznbeat Platform Storage : Name of the root folder to create}';

    protected $description = 'Create the root + 8 stage folders in Google Drive and persist their IDs to settings';

    public function handle(GoogleDriveService $drive): int
    {
        if (! $drive->isConfigured()) {
            $this->error('Drive is not configured. Upload service-account credentials in admin settings first.');
            return self::FAILURE;
        }

        $this->info("Testing connection...");
        $test = $drive->testConnection();
        if (! $test['ok']) {
            $this->error("Connection failed: " . ($test['error'] ?? 'unknown error'));
            return self::FAILURE;
        }
        $this->line("  ✓ Connected as " . ($test['account_email'] ?? 'unknown'));

        $this->info("Creating folder structure...");

        try {
            $folders = $drive->setupFolderStructure((string) $this->option('name'));
        } catch (DriveException $e) {
            $this->error("Setup failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->newLine();
        foreach ($folders as $stage => $id) {
            $this->line("  {$stage}: {$id}");
        }

        $this->newLine();
        $this->info("Drive setup complete.");
        return self::SUCCESS;
    }
}
