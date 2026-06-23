<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('description');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_name');
            $table->string('attachment_mime', 120)->nullable()->after('attachment_size');
        });

        Schema::table('support_ticket_replies', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('body');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_name');
            $table->string('attachment_mime', 120)->nullable()->after('attachment_size');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_size', 'attachment_mime']);
        });
        Schema::table('support_ticket_replies', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_size', 'attachment_mime']);
        });
    }
};
