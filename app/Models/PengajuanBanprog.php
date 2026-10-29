<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanBanprog extends Model
{
    protected $table = 'pengajuan_banprog';

    protected $guarded = ['id'];

    protected $casts = [
        'checklist_termin1_json' => 'array',
        'checklist_termin2_json' => 'array',
        'histori_json' => 'array',
        'tgl_pelaksanaan_mulai' => 'date',
        'tgl_pelaksanaan_selesai' => 'date',
        'tgl_pengajuan_termin1' => 'date',
        'tgl_pengajuan_termin2' => 'date',
        'tgl_spm_t1' => 'date',
        'tgl_cair_t1' => 'date',
        'tgl_spm_t2' => 'date',
        'tgl_cair_t2' => 'date',
    ];

    // 11 status alur Banprog (sesuai workflow)
    public const S_DRAFT = 'Draft Banprog';
    public const S_VERIF_T1 = 'Menunggu Verifikasi';
    public const S_PERBAIKAN = 'Perlu Perbaikan';
    public const S_PPK_T1 = 'Proses Persetujuan PPK';
    public const S_SPM_T1 = 'Proses SPM Termin 1';
    public const S_SP2D_T1 = 'Menunggu SP2D Termin 1';
    public const S_LAPORAN = 'Menunggu Laporan LPKS';
    public const S_VERIF_T2 = 'Verifikasi Laporan T2';
    public const S_PPK_T2 = 'Proses Persetujuan PPK T2';
    public const S_SPM_T2 = 'Proses SPM Termin 2';
    public const S_SP2D_T2 = 'Menunggu SP2D Termin 2';
    public const S_SELESAI = 'Selesai';

    public const STATUS_ORDER = [
        self::S_DRAFT, self::S_VERIF_T1, self::S_PPK_T1, self::S_SPM_T1, self::S_SP2D_T1,
        self::S_LAPORAN, self::S_VERIF_T2, self::S_PPK_T2, self::S_SPM_T2, self::S_SP2D_T2, self::S_SELESAI,
    ];

    public const CHECKLIST_TERMIN1 = [
        1 => 'Surat Permohonan Pencairan Bantuan kepada PPK (ditandatangani Kepala LPKS)',
        2 => 'SK Penetapan Penerima Banprog',
        3 => 'Program Pelatihan & Kurikulum',
        4 => 'PKS (Perjanjian Kerja Sama)',
        5 => 'RPDB/RAB (tidak lebih dari 70%)',
        6 => 'Rencana Pembelian Bahan Pelatihan (dirinci jenis, jumlah, harga, total biaya)',
        7 => 'Surat Pernyataan Kesanggupan Melaksanakan Banprog',
        8 => 'Jadwal Pelaksanaan Pelatihan (ditandatangani Kepala BLKK)',
        9 => 'SPTJM',
        10 => 'Kuitansi',
        11 => 'Fotocopy NPWP',
        12 => 'Fotocopy Buku Tabungan',
        13 => 'Screenshot peserta pelatihan dari akun kelembagaan (16 siswa)',
    ];

    public const CHECKLIST_HIGHLIGHT = [4, 10];

    public const CHECKLIST_TERMIN2 = [
        1 => 'Laporan Pelaksanaan Kegiatan',
        2 => 'Absensi Peserta (16 siswa)',
        3 => 'Dokumentasi / Foto Kegiatan',
        4 => 'Rekap Penggunaan Dana Termin 1 (70%)',
        5 => 'Bukti Pembelian Bahan Pelatihan',
        6 => 'Kuitansi Termin 2',
        7 => 'SPTJM Termin 2',
        8 => 'Surat Permohonan Pencairan Termin 2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getProgressAttribute(): int
    {
        if ($this->status === self::S_PERBAIKAN) {
            return $this->tgl_cair_t1 ? 55 : 15;
        }
        $idx = array_search($this->status, self::STATUS_ORDER);
        if ($idx === false) return 0;
        return (int) round($idx / (count(self::STATUS_ORDER) - 1) * 100);
    }

    public function getTerminAktifAttribute(): int
    {
        return $this->tgl_cair_t1 ? 2 : 1;
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::S_DRAFT => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
            self::S_VERIF_T1, self::S_VERIF_T2 => 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50',
            self::S_PERBAIKAN => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
            self::S_PPK_T1, self::S_PPK_T2 => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
            self::S_SPM_T1, self::S_SPM_T2, self::S_SP2D_T1, self::S_SP2D_T2 => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
            self::S_LAPORAN => 'bg-orange text-white',
            self::S_SELESAI => 'bg-success text-white',
            default => 'bg-light text-dark border',
        };
    }

    public function addHistori(string $aksi, ?string $catatan = null, $user = null): void
    {
        $h = $this->histori_json ?? [];
        $h[] = [
            'waktu' => now()->format('Y-m-d H:i'),
            'user' => $user?->name ?? auth()->user()?->name,
            'role' => $user?->role ?? auth()->user()?->role,
            'aksi' => $aksi,
            'status' => $this->status,
            'catatan' => $catatan,
        ];
        $this->histori_json = $h;
    }
}
