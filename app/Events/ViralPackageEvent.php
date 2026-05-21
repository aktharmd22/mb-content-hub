<?php

namespace App\Events;

use App\Models\User;
use App\Models\ViralPackage;
use Illuminate\Foundation\Events\Dispatchable;

class ViralPackageEvent
{
    use Dispatchable;

    public function __construct(
        public ViralPackage $package,
        public string $action,
        public ?User $actor = null,
        public array $context = []
    ) {}
}
