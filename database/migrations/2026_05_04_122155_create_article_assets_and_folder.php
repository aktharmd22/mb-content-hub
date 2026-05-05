<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['file', 'link']);
            $table->string('name')->nullable();
            $table->string('drive_file_id')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('url', 1000)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->string('assets_folder_drive_id')->nullable()->after('current_drive_file_id');
            $table->string('assets_folder_name')->nullable()->after('assets_folder_drive_id');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['assets_folder_drive_id', 'assets_folder_name']);
        });
        Schema::dropIfExists('article_assets');
    }
};
