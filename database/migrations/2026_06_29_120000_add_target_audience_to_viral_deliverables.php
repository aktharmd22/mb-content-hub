<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viral_package_deliverables', function (Blueprint $table) {
            $table->text('target_audience')->nullable()->after('hashtags');
        });
    }

    public function down(): void
    {
        Schema::table('viral_package_deliverables', function (Blueprint $table) {
            $table->dropColumn('target_audience');
        });
    }
};
