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
        'tgl_spp' => 'date',
        'tgl_spm_t1' => 'date',
        'tgl_cair_t1' => 'date',
        'tgl_spm_t2' => 'date',
        'tgl_cair_t2' => 'date',
        'spj_deadline' => 'date',
    ];

    public const KATEGORI_TERMIN1 = 'Termin I';
    public const KATEGORI_TERMIN2 = 'Termin II';

    // Alur disamakan dengan Pengajuan LS (untuk masing-masing Termin I dan Termin II)
    public const S_DRAFT = 'Draft Banprog';
    public const S_VERIF = 'Menunggu Verifikasi';
    public const S_PERBAIKAN = 'Perlu Perbaikan';
    public const S_PPK = 'Proses Persetujuan PPK';
    public const S_SPP = 'Penerbitan SPP';
    public const S_SAKTI = 'Diajukan ke SAKTI';
    public const S_SP2D = 'Belum Terbit SP2D';
    public const S_CAIR = 'Dicairkan';
    public const S_SELESAI = 'Selesai';

    // Alias kompatibilitas
    public const S_VERIF_T1 = self::S_VERIF;
    public const S_PPK_T1 = self::S_PPK;

    public const STATUS_ORDER = [
        self::S_DRAFT,
        self::S_VERIF,
        self::S_PPK,
        self::S_SPP,
        self::S_SAKTI,
        self::S_SP2D,
        self::S_CAIR,
        self::S_SELESAI,
    ];

    public const CHECKLIST_TERMIN1 = [
        1 => 'Surat Permohonan Pencairan Bantuan kepada PPK (ditandatangani Kepala LPKS / BLKK)',
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
            return 15;
        }
        $idx = array_search($this->status, self::STATUS_ORDER);
        if ($idx === false) return 0;
        return (int) round($idx / (count(self::STATUS_ORDER) - 1) * 100);
    }

    public function getTerminAktifAttribute(): int
    {
        return $this->kategori_pengajuan === self::KATEGORI_TERMIN2 ? 2 : 1;
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::S_DRAFT => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
            self::S_VERIF => 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50',
            self::S_PERBAIKAN => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
            self::S_PPK, self::S_SPP, self::S_SAKTI => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
            self::S_SP2D => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
            self::S_CAIR => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
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
