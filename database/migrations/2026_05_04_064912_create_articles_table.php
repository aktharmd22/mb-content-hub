<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('article_code', 20)->unique();
            $table->string('title');
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('sales_rep_id')->constrained('users');
            $table->foreignId('tech_writer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tech_lead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('current_stage', 32)->default('inbox');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->date('deadline')->nullable();
            $table->unsignedInteger('word_count_target')->nullable();
            $table->string('source_drive_file_id')->nullable();
            $table->string('current_drive_file_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('published_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('stage_entered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('current_stage');
            $table->index('deadline');
            $table->index(['sales_rep_id', 'current_stage']);
            $table->index(['tech_writer_id', 'current_stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
