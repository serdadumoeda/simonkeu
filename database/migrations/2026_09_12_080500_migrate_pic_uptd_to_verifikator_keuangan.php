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
        // 1. Update existing PIC UPTD users to Verifikator Keuangan
        try {
            DB::table('users')
                ->where('role', 'PIC UPTD')
                ->update(['role' => 'Verifikator Keuangan']);
        } catch (\Throwable $e) {}

        // 2. Drop PostgreSQL status check constraint if it exists
        try {
            DB::statement("ALTER TABLE pengajuan_ls DROP CONSTRAINT IF EXISTS pengajuan_ls_status_check");
        } catch (\Throwable $e) {}

        // 3. Update existing Menunggu Verifikasi UPTD pengajuan to Menunggu Verifikasi
        try {
            DB::table('pengajuan_ls')
                ->where('status', 'Menunggu Verifikasi UPTD')
                ->update(['status' => 'Menunggu Verifikasi']);
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
