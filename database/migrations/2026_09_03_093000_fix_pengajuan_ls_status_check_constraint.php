<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop constraint pengajuan_ls_status_check on PostgreSQL if it exists
        try {
            DB::statement("ALTER TABLE pengajuan_ls DROP CONSTRAINT IF EXISTS pengajuan_ls_status_check");
        } catch (\Throwable $e) {
            // Ignore if constraint does not exist or not supported
        }

        try {
            Schema::table('pengajuan_ls', function (Blueprint $table) {
                $table->string('status', 50)->default('Draft')->change();
            });
        } catch (\Throwable $e) {
            // Ignore if change fails
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
