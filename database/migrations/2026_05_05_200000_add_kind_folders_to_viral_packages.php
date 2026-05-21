<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viral_packages', function (Blueprint $table) {
            $table->string('drive_article_folder_id', 128)->nullable()->after('drive_deliverables_folder_id');
            $table->string('drive_posts_folder_id', 128)->nullable()->after('drive_article_folder_id');
            $table->string('drive_reel_folder_id', 128)->nullable()->after('drive_posts_folder_id');
        });
    }

    public function down(): void
    {
        Schema::table('viral_packages', function (Blueprint $table) {
            $table->dropColumn(['drive_article_folder_id', 'drive_posts_folder_id', 'drive_reel_folder_id']);
        });
    }
};
