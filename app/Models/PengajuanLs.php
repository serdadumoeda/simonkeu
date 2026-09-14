<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanLs extends Model
{
    use HasFactory;

    // Supaya Laravel tahu nama tabel kita
    protected $table = 'pengajuan_ls';

    // Kolom-kolom yang boleh diisi (Mass Assignment protection)
    protected $fillable = [
        'no_pengajuan',
        'tgl_pengajuan',
        'user_id',
        'bidang',
        'nama_kegiatan',
        'no_akun',
        'jenis_belanja',
        'nilai_bruto',
        'potongan_pajak',
        'nilai_neto',
        'uraian_pembayaran',
        'link_google_drive',
        'data_dukung_json',
        'pic_uptd_id',
        'verifikator_id',
        'ppk_id',
        'operator_pembayaran_id',
        'bendahara_id',
        'status',
        'catatan_koreksi',
        'no_spm',
        'tgl_spm',
        'no_sp2d',
        'tgl_cair',
        'bukti_penyerahan',
        'kategori_pengajuan',
        'spj_sp2d_link',
        'spj_spm_link',
        'spj_spp_link',
        'spj_lengkap_link',
        'spj_verified_at',
        'spj_verified_by',
        'spj_deadline',
        'verifikator_spm_deadline',
        'spj_verifikator_deadline',
        'spj_status'
    ];

    // Relasi ke tabel User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function picUptd()
    {
        return $this->belongsTo(User::class, 'pic_uptd_id');
    }
    public function verifikator()
    {
        return $this->belongsTo(User::class, 'verifikator_id');
    }
    public function ppk()
    {
        return $this->belongsTo(User::class, 'ppk_id');
    }
    public function operatorPembayaran()
    {
        return $this->belongsTo(User::class, 'operator_pembayaran_id');
    }
    public function bendahara()
    {
        return $this->belongsTo(User::class, 'bendahara_id');
    }
    public function spjVerifiedBy()
    {
        return $this->belongsTo(User::class, 'spj_verified_by');
    }

    /**
     * Accessor for Overall Progress Percentage (0 - 100)
     */
    public function getOverallProgressPercentAttribute()
    {
        if ($this->status == 'Draft') return 10;
        if ($this->status == 'Menunggu Verifikasi') return 20;
        if ($this->status == 'Perlu Perbaikan') return 20;
        if ($this->status == 'Proses Persetujuan PPK') return 35;
        if ($this->status == 'Diajukan ke SAKTI') return 50;
        if ($this->status == 'Belum Terbit SP2D') return 65;
        if ($this->status == 'Dicairkan') return 75;
        if ($this->status == 'Selesai') {
            $spj = $this->spj_status ?? 'Belum Upload';
            if ($spj == 'SPJ Lengkap') return 100;
            if ($spj == 'Menunggu Verifikasi SPJ') return 90;
            if ($spj == 'Menunggu Upload Pemohon') return 85;
            return 80;
        }
        return 0;
    }

    /**
     * Accessor for Overall Status Label
     */
    public function getOverallStatusLabelAttribute()
    {
        if ($this->status == 'Draft') return 'Draft';
        if ($this->status == 'Menunggu Verifikasi') return 'Verifikasi Keuangan';
        if ($this->status == 'Perlu Perbaikan') return 'Perlu Perbaikan';
        if ($this->status == 'Proses Persetujuan PPK') return 'Proses Persetujuan PPK';
        if ($this->status == 'Diajukan ke SAKTI') return 'Proses SAKTI (SPM)';
        if ($this->status == 'Belum Terbit SP2D') return 'Menunggu SP2D';
        if ($this->status == 'Dicairkan') return 'Sudah Cair';
        if ($this->status == 'Selesai') {
            $spj = $this->spj_status ?? 'Belum Upload';
            if ($spj == 'SPJ Lengkap') return 'Lengkap & Verified';
            if ($spj == 'Menunggu Verifikasi SPJ') return 'Verifikasi SPJ';
            if ($spj == 'Menunggu Upload Pemohon') return 'Upload SPJ Pemohon';
            return 'Upload SPM/SP2D';
        }
        return $this->status;
    }

    /**
     * Accessor for Overall Status Badge CSS Classes
     */
    public function getOverallStatusBadgeClassAttribute()
    {
        if ($this->status == 'Draft') return 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-50';
        if ($this->status == 'Menunggu Verifikasi') return 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-50';
        if ($this->status == 'Perlu Perbaikan') return 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-50';
        if ($this->status == 'Proses Persetujuan PPK') return 'bg-info bg-opacity-10 text-info border border-info border-opacity-50';
        if ($this->status == 'Diajukan ke SAKTI') return 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-50';
        if ($this->status == 'Belum Terbit SP2D') return 'bg-dark bg-opacity-10 text-dark border border-dark border-opacity-50';
        if ($this->status == 'Dicairkan') return 'bg-success bg-opacity-10 text-success border border-success border-opacity-50';
        if ($this->status == 'Selesai') {
            $spj = $this->spj_status ?? 'Belum Upload';
            if ($spj == 'SPJ Lengkap') return 'bg-success text-white';
            if ($spj == 'Menunggu Verifikasi SPJ') return 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50';
            if ($spj == 'Menunggu Upload Pemohon') return 'bg-info bg-opacity-10 text-info border border-info border-opacity-50';
            return 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-50';
        }
        return 'bg-secondary';
    }

    /**
     * Accessor for Progress Bar Color
     */
    public function getOverallProgressColorAttribute()
    {
        $pct = $this->overall_progress_percent;
        if ($pct >= 100) return 'bg-success';
        if ($pct >= 80) return 'bg-primary';
        if ($pct >= 50) return 'bg-primary';
        if ($pct >= 35) return 'bg-info';
        if ($this->status == 'Perlu Perbaikan') return 'bg-danger';
        return 'bg-warning';
    }

    /**
     * Helper to generate Direct WhatsApp Notification Link (wa.me) to specific stage target actor
     */
    public function getWhatsappNotificationUrl($targetUserOrPhone = null, $customMessage = null)
    {
        $targetName = 'Bapak/Ibu';
        $targetPhone = '628123456789';

        if ($targetUserOrPhone instanceof User) {
            $targetName = $targetUserOrPhone->name;
            $targetPhone = $targetUserOrPhone->no_wa ?? '628123456789';
        } elseif (is_array($targetUserOrPhone)) {
            $targetName = $targetUserOrPhone['name'] ?? 'Bapak/Ibu';
            $targetPhone = $targetUserOrPhone['no_wa'] ?? $targetUserOrPhone['phone'] ?? '628123456789';
        } elseif (is_string($targetUserOrPhone) && !empty($targetUserOrPhone)) {
            $targetPhone = $targetUserOrPhone;
        }

        $phone = preg_replace('/[^0-9]/', '', $targetPhone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        if ($customMessage) {
            $text = $customMessage;
        } else {
            $neto = 'Rp ' . number_format($this->nilai_neto, 0, ',', '.');
            $text = "📌 *SIMONKEU BPVP SURAKARTA - NOTIFIKASI BERKAS SPJ*\n\n"
                  . "Yth. *{$targetName}*,\n"
                  . "Permohonan berikut memerlukan perhatian / tindakan Anda:\n\n"
                  . "🔹 *No Pengajuan:* {$this->no_pengajuan}\n"
                  . "🏢 *Bidang/UPTD:* {$this->bidang}\n"
                  . "📋 *Kegiatan:* {$this->nama_kegiatan}\n"
                  . "💰 *Nilai Neto:* {$neto}\n"
                  . "📊 *Status Berkas:* {$this->overall_status_label} ({$this->overall_progress_percent}%)\n\n"
                  . "Silakan klik tautan berikut untuk membuka & menindaklanjuti berkas:\n"
                  . url("/pengajuan/{$this->id}");
        }

        return "https://wa.me/{$phone}?text=" . urlencode($text);
    }
}
