<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand the enum so we can migrate existing rows safely.
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','sales','tech_writer','tech_lead','tech_team') NOT NULL DEFAULT 'sales'");

        // Move all writers and leads to the unified tech_team role.
        DB::table('users')
            ->whereIn('role', ['tech_writer', 'tech_lead'])
            ->update(['role' => 'tech_team']);

        // Shrink the enum to the final shape.
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','sales','tech_team') NOT NULL DEFAULT 'sales'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','sales','tech_writer','tech_lead','tech_team') NOT NULL DEFAULT 'sales'");

        // Best-effort: split tech_team back into tech_writer (we can't reconstruct who was a lead).
        DB::table('users')->where('role', 'tech_team')->update(['role' => 'tech_writer']);

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','sales','tech_writer','tech_lead') NOT NULL DEFAULT 'sales'");
    }
};
