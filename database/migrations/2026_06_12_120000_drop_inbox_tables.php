<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('inbox_messages');
        Schema::dropIfExists('inbox_participants');
        Schema::dropIfExists('inbox_conversations');
    }

    public function down(): void
    {
        // No-op — inbox module has been removed.
    }
};
