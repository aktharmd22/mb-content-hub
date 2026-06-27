<?php

use App\Models\ViralPackage;
use App\Models\ViralPackageDeliverable;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * One-time top-up: bring every ACTIVE package up to 2 reels.
     * Only adds (never removes); skips completed packages.
     */
    public function up(): void
    {
        $target = 2;

        ViralPackage::where('status', 'active')->orderBy('id')->each(function (ViralPackage $pkg) use ($target) {
            $count = (int) ViralPackageDeliverable::where('viral_package_id', $pkg->id)->where('kind', 'reel')->count();
            if ($count >= $target) {
                return;
            }

            $slot = (int) ViralPackageDeliverable::where('viral_package_id', $pkg->id)->where('kind', 'reel')->max('slot_number');

            for ($i = $count; $i < $target; $i++) {
                $slot++;
                ViralPackageDeliverable::create([
                    'viral_package_id' => $pkg->id,
                    'kind'             => 'reel',
                    'slot_number'      => $slot,
                    'title'            => 'Reel ' . $slot,
                    'stage'            => 'pending',
                ]);
            }
        });
    }

    public function down(): void
    {
        // No-op.
    }
};
