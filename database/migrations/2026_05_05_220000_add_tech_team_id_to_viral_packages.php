<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viral_packages', function (Blueprint $table) {
            $table->foreignId('tech_team_id')->nullable()->after('sales_rep_id')->constrained('users')->nullOnDelete();
            $table->index('tech_team_id');
        });
    }

    public function down(): void
    {
        Schema::table('viral_packages', function (Blueprint $table) {
            $table->dropForeign(['tech_team_id']);
            $table->dropColumn('tech_team_id');
        });
    }
};
