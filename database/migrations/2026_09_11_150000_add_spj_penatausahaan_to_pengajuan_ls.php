<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pengajuan_ls', function (Blueprint $table) {
            // Upload oleh Verifikator: SP2D, SPM, SPP
            $table->string('spj_sp2d_link', 500)->nullable()->after('bukti_penyerahan');
            $table->string('spj_spm_link', 500)->nullable()->after('spj_sp2d_link');
            $table->string('spj_spp_link', 500)->nullable()->after('spj_spm_link');

            // Upload SPJ lengkap oleh Pemohon/UPTD/Bidang
            $table->string('spj_lengkap_link', 500)->nullable()->after('spj_spp_link');

            // Verifikasi SPJ oleh Verifikator
            $table->timestamp('spj_verified_at')->nullable()->after('spj_lengkap_link');
            $table->foreignId('spj_verified_by')->nullable()->after('spj_verified_at')->constrained('users')->onDelete('set null');

            // Batas waktu upload SPJ (default 30 hari setelah pencairan)
            $table->date('spj_deadline')->nullable()->after('spj_verified_by');

            // Status SPJ terpisah dari status utama
            $table->string('spj_status', 50)->default('Belum Upload')->after('spj_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_ls', function (Blueprint $table) {
            $table->dropForeign(['spj_verified_by']);
            $table->dropColumn([
                'spj_sp2d_link', 'spj_spm_link', 'spj_spp_link',
                'spj_lengkap_link', 'spj_verified_at', 'spj_verified_by',
                'spj_deadline', 'spj_status'
            ]);
        });
    }
};
