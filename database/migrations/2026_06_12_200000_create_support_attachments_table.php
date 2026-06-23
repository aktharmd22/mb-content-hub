<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable'); // attachable_id, attachable_type
            $table->string('path');
            $table->string('original_name');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime', 120)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Migrate any existing single attachments into the new table.
        if (Schema::hasColumn('support_tickets', 'attachment_path')) {
            DB::table('support_tickets')->whereNotNull('attachment_path')->orderBy('id')->each(function ($t) {
                DB::table('support_attachments')->insert([
                    'attachable_id'   => $t->id,
                    'attachable_type' => \App\Models\SupportTicket::class,
                    'path'            => $t->attachment_path,
                    'original_name'   => $t->attachment_name ?? 'attachment',
                    'size'            => $t->attachment_size,
                    'mime'            => $t->attachment_mime,
                    'uploaded_by'     => $t->reporter_id,
                    'created_at'      => $t->created_at,
                    'updated_at'      => now(),
                ]);
            });

            Schema::table('support_tickets', function (Blueprint $table) {
                $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_size', 'attachment_mime']);
            });
        }

        if (Schema::hasColumn('support_ticket_replies', 'attachment_path')) {
            DB::table('support_ticket_replies')->whereNotNull('attachment_path')->orderBy('id')->each(function ($r) {
                DB::table('support_attachments')->insert([
                    'attachable_id'   => $r->id,
                    'attachable_type' => \App\Models\SupportTicketReply::class,
                    'path'            => $r->attachment_path,
                    'original_name'   => $r->attachment_name ?? 'attachment',
                    'size'            => $r->attachment_size,
                    'mime'            => $r->attachment_mime,
                    'uploaded_by'     => $r->user_id,
                    'created_at'      => $r->created_at,
                    'updated_at'      => now(),
                ]);
            });

            Schema::table('support_ticket_replies', function (Blueprint $table) {
                $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_size', 'attachment_mime']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_attachments');
    }
};
