<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_banprog', function (Blueprint $table) {
            $table->string('nama_kepala_lpks')->nullable()->change();
            $table->string('no_hp_kepala_lpks', 30)->nullable()->change();

            if (!Schema::hasColumn('pengajuan_banprog', 'no_spp')) {
                $table->string('no_spp')->nullable()->after('tgl_pengajuan_termin2');
            }
            if (!Schema::hasColumn('pengajuan_banprog', 'tgl_spp')) {
                $table->date('tgl_spp')->nullable()->after('no_spp');
            }
            if (!Schema::hasColumn('pengajuan_banprog', 'bukti_penyerahan')) {
                $table->text('bukti_penyerahan')->nullable()->after('tgl_cair_t2');
            }
            if (!Schema::hasColumn('pengajuan_banprog', 'spj_status')) {
                $table->string('spj_status')->default('Belum Upload')->after('bukti_penyerahan');
            }
            if (!Schema::hasColumn('pengajuan_banprog', 'spj_verifikator_link')) {
                $table->text('spj_verifikator_link')->nullable()->after('spj_status');
            }
            if (!Schema::hasColumn('pengajuan_banprog', 'spj_pemohon_link')) {
                $table->text('spj_pemohon_link')->nullable()->after('spj_verifikator_link');
            }
            if (!Schema::hasColumn('pengajuan_banprog', 'catatan_spj')) {
                $table->text('catatan_spj')->nullable()->after('spj_pemohon_link');
            }
            if (!Schema::hasColumn('pengajuan_banprog', 'spj_deadline')) {
                $table->date('spj_deadline')->nullable()->after('catatan_spj');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_banprog', function (Blueprint $table) {
            foreach (['no_spp', 'tgl_spp', 'bukti_penyerahan', 'spj_status', 'spj_verifikator_link', 'spj_pemohon_link', 'catatan_spj', 'spj_deadline'] as $col) {
                if (Schema::hasColumn('pengajuan_banprog', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
