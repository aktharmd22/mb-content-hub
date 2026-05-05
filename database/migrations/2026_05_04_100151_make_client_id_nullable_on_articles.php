<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing FK before altering the column
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->change();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable(false)->change();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients');
        });
    }
};
