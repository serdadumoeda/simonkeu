<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menghapus constraint check pada tabel pengajuan_ls dan users secara permanen.
     * Ini menghilangkan kebutuhan ALTER TABLE di setiap request controller,
     * yang menyebabkan deadlock dan 504 Gateway Time-out.
     */
    public function up(): void
    {
        // Drop status check constraint pada pengajuan_ls (jika ada)
        try {
            DB::statement("ALTER TABLE pengajuan_ls DROP CONSTRAINT IF EXISTS pengajuan_ls_status_check");
        } catch (\Throwable $e) {
            // Abaikan jika constraint tidak ada atau database tidak mendukung
        }

        // Drop role check constraint pada users (jika ada)
        try {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
        } catch (\Throwable $e) {
            // Abaikan jika constraint tidak ada atau database tidak mendukung
        }
    }

    public function down(): void
    {
        // Tidak perlu mengembalikan constraint — sengaja dihapus
    }
};
