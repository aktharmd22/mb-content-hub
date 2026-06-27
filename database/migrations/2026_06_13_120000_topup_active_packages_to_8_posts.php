<?php

use App\Models\ViralPackage;
use App\Models\ViralPackageDeliverable;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * One-time top-up: bring every ACTIVE package up to 8 social posts.
     * Only adds posts (never removes); leaves completed packages untouched.
     */
    public function up(): void
    {
        $target = 8;

        ViralPackage::where('status', 'active')->orderBy('id')->each(function (ViralPackage $pkg) use ($target) {
            $posts = ViralPackageDeliverable::where('viral_package_id', $pkg->id)->where('kind', 'social_post');
            $count = (int) $posts->count();
            if ($count >= $target) {
                return;
            }

            $slot = (int) ViralPackageDeliverable::where('viral_package_id', $pkg->id)
                ->where('kind', 'social_post')
                ->max('slot_number');

            for ($i = $count; $i < $target; $i++) {
                $slot++;
                ViralPackageDeliverable::create([
                    'viral_package_id' => $pkg->id,
                    'kind'             => 'social_post',
                    'slot_number'      => $slot,
                    'title'            => 'Post ' . $slot,
                    'stage'            => 'pending',
                ]);
            }
        });
    }

    public function down(): void
    {
        // No-op — we don't auto-remove posts on rollback.
    }
};
