<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viral_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('sales_rep_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->string('drive_folder_id', 128)->nullable();
            $table->string('drive_folder_name')->nullable();
            $table->string('drive_assets_folder_id', 128)->nullable();
            $table->string('drive_deliverables_folder_id', 128)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['sales_rep_id', 'status']);
            $table->index('status');
        });

        Schema::create('viral_package_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viral_package_id')->constrained('viral_packages')->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('name')->nullable();
            $table->string('drive_file_id', 128)->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('url', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('viral_package_id');
        });

        Schema::create('viral_package_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viral_package_id')->constrained('viral_packages')->cascadeOnDelete();
            $table->string('kind', 20);
            $table->unsignedTinyInteger('slot_number')->default(1);
            $table->string('title');
            $table->string('stage', 20)->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('drive_file_id', 128)->nullable();
            $table->string('drive_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['viral_package_id', 'stage']);
            $table->index(['assigned_to', 'stage']);
        });

        Schema::create('viral_package_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deliverable_id')->constrained('viral_package_deliverables')->cascadeOnDelete();
            $table->string('from_stage', 20)->nullable();
            $table->string('to_stage', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index('deliverable_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viral_package_history');
        Schema::dropIfExists('viral_package_deliverables');
        Schema::dropIfExists('viral_package_assets');
        Schema::dropIfExists('viral_packages');
    }
};
