<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pengajuan_ls', function (Blueprint $table) {
            $table->id();
            $table->string('no_pengajuan', 50)->unique();
            $table->date('tgl_pengajuan');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('bidang', 50);
            $table->string('nama_kegiatan', 255);
            $table->string('no_akun', 50);
            $table->string('jenis_belanja', 100);
            $table->string('nama_pihak_ketiga', 100)->nullable();
            $table->string('npwp', 50)->nullable();
            $table->string('no_rekening', 50)->nullable();
            $table->string('bank', 50)->nullable();
            $table->decimal('nilai_bruto', 15, 2)->default(0);
            $table->decimal('potongan_pajak', 15, 2)->default(0);
            $table->decimal('nilai_neto', 15, 2)->default(0);
            $table->text('uraian_pembayaran')->nullable();
            $table->string('link_google_drive', 255);
            $table->text('data_dukung_json')->nullable();
            $table->foreignId('pic_uptd_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['Draft', 'Menunggu Verifikasi UPTD', 'Menunggu Verifikasi', 'Perlu Perbaikan', 'Disetujui PPK', 'Diajukan ke SAKTI', 'Belum Terbit SP2D', 'Dicairkan', 'Selesai'])->default('Draft');
            $table->text('catatan_koreksi')->nullable();
            $table->string('no_spm', 50)->nullable();
            $table->date('tgl_cair')->nullable();
            $table->string('no_sp2d', 50)->nullable();
            $table->timestamps(); // otomatis membuat created_at dan updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_ls');
    }
};
