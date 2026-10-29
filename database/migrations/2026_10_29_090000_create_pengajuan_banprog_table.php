<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sub-role Banprog untuk Operator Bidang
        if (!Schema::hasColumn('users', 'akses_banprog')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('akses_banprog')->default(false);
            });
        }

        Schema::create('pengajuan_banprog', function (Blueprint $table) {
            $table->id();
            $table->string('no_banprog')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('bidang')->nullable();

            // Identitas LPKS
            $table->string('nama_lpks');
            $table->string('kabupaten_kota');
            $table->string('nama_kepala_lpks');
            $table->string('no_hp_kepala_lpks', 30);
            $table->string('kejuruan');
            $table->string('no_sk_penetapan')->nullable();
            $table->string('rekening_lpks')->nullable();
            $table->string('bank_lpks')->nullable();
            $table->date('tgl_pelaksanaan_mulai')->nullable();
            $table->date('tgl_pelaksanaan_selesai')->nullable();

            // Keuangan
            $table->decimal('nilai_banprog', 18, 2)->default(0);
            $table->decimal('nilai_termin_1', 18, 2)->default(0);
            $table->decimal('nilai_termin_2', 18, 2)->default(0);

            // Dokumen & checklist
            $table->text('link_drive_termin1')->nullable();
            $table->text('link_drive_laporan')->nullable();
            $table->json('checklist_termin1_json')->nullable();
            $table->json('checklist_termin2_json')->nullable();

            // Alur
            $table->string('status')->default('Draft Banprog');
            $table->date('tgl_pengajuan_termin1')->nullable();
            $table->date('tgl_pengajuan_termin2')->nullable();

            // Pencairan Termin 1
            $table->string('no_spm_t1')->nullable();
            $table->date('tgl_spm_t1')->nullable();
            $table->string('no_sp2d_t1')->nullable();
            $table->date('tgl_cair_t1')->nullable();

            // Pencairan Termin 2
            $table->string('no_spm_t2')->nullable();
            $table->date('tgl_spm_t2')->nullable();
            $table->string('no_sp2d_t2')->nullable();
            $table->date('tgl_cair_t2')->nullable();

            $table->text('catatan_revisi')->nullable();
            $table->json('histori_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_banprog');
        if (Schema::hasColumn('users', 'akses_banprog')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('akses_banprog');
            });
        }
    }
};
