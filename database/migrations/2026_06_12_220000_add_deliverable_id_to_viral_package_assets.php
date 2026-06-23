<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viral_package_assets', function (Blueprint $table) {
            $table->foreignId('deliverable_id')->nullable()->after('viral_package_id')
                ->constrained('viral_package_deliverables')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('viral_package_assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deliverable_id');
        });
    }
};
