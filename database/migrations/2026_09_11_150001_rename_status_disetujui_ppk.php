<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Drop constraint jika ada (PostgreSQL)
        try {
            DB::statement("ALTER TABLE pengajuan_ls DROP CONSTRAINT IF EXISTS pengajuan_ls_status_check");
        } catch (\Throwable $e) {}

        // Update semua record yang masih berstatus 'Disetujui PPK'
        DB::table('pengajuan_ls')
            ->where('status', 'Disetujui PPK')
            ->update(['status' => 'Proses Persetujuan PPK']);
    }

    public function down(): void
    {
        // Kembalikan ke status lama
        DB::table('pengajuan_ls')
            ->where('status', 'Proses Persetujuan PPK')
            ->update(['status' => 'Disetujui PPK']);
    }
};
