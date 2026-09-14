<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengajuanLs;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 0. Ambil Parameter Tahun & Bidang & Filter Ketepatan
        $tahunAktif = $request->get('tahun', date('Y'));
        $filterBidang = $request->get('bidang', '');
        $filterKetepatan = $request->get('ketepatan', ''); // 'tepat_waktu', 'terlambat'

        // Ambil daftar tahun unik secara dinamis dari database
        $tahunListDb = PengajuanLs::whereNotNull('tgl_pengajuan')
            ->pluck('tgl_pengajuan')
            ->map(fn($d) => Carbon::parse($d)->format('Y'))
            ->unique()
            ->toArray();

        if (!in_array(date('Y'), $tahunListDb)) {
            $tahunListDb[] = date('Y');
        }
        rsort($tahunListDb);
        $daftarTahun = array_values($tahunListDb);

        // Ambil daftar bidang & UPTD unik
        $daftarBidang = User::where('role', 'Operator Bidang')
            ->distinct()
            ->pluck('bidang')
            ->merge(PengajuanLs::distinct()->pluck('bidang'))
            ->filter(fn($val) => !empty($val) && $val !== 'None' && $val !== 'Keuangan')
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // 1. Siapkan Query Dasar dengan Filter Hak Akses & Tahun Anggaran
        $query = PengajuanLs::query();

        if ($tahunAktif !== 'semua' && !empty($tahunAktif)) {
            $query->whereYear('tgl_pengajuan', $tahunAktif);
        }

        if (!empty($filterBidang)) {
            $query->where('bidang', $filterBidang);
        }

        // Jika yang login adalah Operator Bidang, dia hanya menghitung data bidangnya saja
        if (Auth::user()->role == 'Operator Bidang') {
            $query->where('bidang', Auth::user()->bidang);
        }

        // 2. Menghitung Statistik Status
        $totalPengajuan = $query->count();
        $menungguVerifikasi = (clone $query)->where('status', 'Menunggu Verifikasi')->count();
        $perluPerbaikan = (clone $query)->where('status', 'Perlu Perbaikan')->count();
        $prosesPersetujuanPpk = (clone $query)->where('status', 'Proses Persetujuan PPK')->count();
        $diajukanSakti = (clone $query)->where('status', 'Diajukan ke SAKTI')->count();
        $menungguSp2d = (clone $query)->where('status', 'Belum Terbit SP2D')->count();
        $dicairkan = (clone $query)->whereIn('status', ['Dicairkan', 'Selesai'])->count();

        // 3. Menghitung Total Nilai Neto Tahun Anggaran Ini
        $totalNilaiBulanIni = (clone $query)->sum('nilai_neto');

        // 4. Siapkan Data untuk Grafik (Jumlah Pengajuan per Bidang di Tahun Aktif)
        $grafikQuery = (clone $query)->select('bidang', DB::raw('count(*) as total'));
        $dataGrafik = $grafikQuery->groupBy('bidang')->get();

        $labelBidang = [];
        $angkaBidang = [];
        foreach ($dataGrafik as $data) {
            $labelBidang[] = $data->bidang;
            $angkaBidang[] = $data->total;
        }

        // =========================================================================
        // 5. KALKULASI EXECUTIVE MONITORING KETEPATAN WAKTU (SLA) & STAKEHOLDER
        // =========================================================================
        $allPengajuanSla = (clone $query)->get();

        $spmTepatWaktuCount = 0;
        $spmTerlambatCount = 0;
        $spjPemohonTepatWaktuCount = 0;
        $spjPemohonTerlambatCount = 0;
        $spjVerifikasiTepatWaktuCount = 0;
        $spjVerifikasiTerlambatCount = 0;

        $daftarSpmMonitoring = [];

        foreach ($allPengajuanSla as $p) {
            $now = Carbon::now();

            // A. Evaluasi SLA Upload SPM/SP2D (2 Hari dari Bendahara Penyerahan Uang)
            $spmSlaStatus = 'Belum Mulai';
            $isSpmDone = !empty($p->no_spm) || !empty($p->tgl_spm);

            if ($isSpmDone) {
                if (!empty($p->verifikator_spm_deadline)) {
                    $spmDeadline = Carbon::parse($p->verifikator_spm_deadline);
                    $spmDate = !empty($p->tgl_spm) ? Carbon::parse($p->tgl_spm) : Carbon::parse($p->updated_at);
                    if ($spmDate->gt($spmDeadline)) {
                        $spmSlaStatus = 'Terlambat';
                        $spmTerlambatCount++;
                    } else {
                        $spmSlaStatus = 'Tepat Waktu';
                        $spmTepatWaktuCount++;
                    }
                } else {
                    $spmSlaStatus = 'Tepat Waktu';
                    $spmTepatWaktuCount++;
                }
            } elseif (!empty($p->verifikator_spm_deadline)) {
                $spmDeadline = Carbon::parse($p->verifikator_spm_deadline);
                if ($now->gt($spmDeadline)) {
                    $spmSlaStatus = 'Terlambat';
                    $spmTerlambatCount++;
                } else {
                    $spmSlaStatus = 'Berjalan';
                }
            }

            // B. Evaluasi SLA Upload SPJ Pemohon (5 Hari dari SPM Upload)
            $spjPemohonSlaStatus = 'Belum Mulai';
            $isSpjUploaded = in_array($p->spj_status, ['Menunggu Verifikasi SPJ', 'SPJ Lengkap']) || !empty($p->spj_file);

            if ($isSpjUploaded) {
                if (!empty($p->spj_deadline)) {
                    $spjDeadline = Carbon::parse($p->spj_deadline);
                    $uploadDate = !empty($p->updated_at) ? Carbon::parse($p->updated_at) : Carbon::now();
                    if ($uploadDate->gt($spjDeadline)) {
                        $spjPemohonSlaStatus = 'Terlambat';
                        $spjPemohonTerlambatCount++;
                    } else {
                        $spjPemohonSlaStatus = 'Tepat Waktu';
                        $spjPemohonTepatWaktuCount++;
                    }
                } else {
                    $spjPemohonSlaStatus = 'Tepat Waktu';
                    $spjPemohonTepatWaktuCount++;
                }
            } elseif (!empty($p->spj_deadline)) {
                $spjDeadline = Carbon::parse($p->spj_deadline);
                if ($now->gt($spjDeadline)) {
                    $spjPemohonSlaStatus = 'Terlambat';
                    $spjPemohonTerlambatCount++;
                } else {
                    $spjPemohonSlaStatus = 'Berjalan';
                }
            }

            // C. Evaluasi SLA Verifikasi SPJ (2 Hari dari SPJ Upload)
            $spjVerifikatorSlaStatus = 'Belum Mulai';
            $isSpjVerified = ($p->spj_status == 'SPJ Lengkap');

            if ($isSpjVerified) {
                if (!empty($p->spj_verifikator_deadline)) {
                    $spjVerifDeadline = Carbon::parse($p->spj_verifikator_deadline);
                    $verifDate = Carbon::parse($p->updated_at);
                    if ($verifDate->gt($spjVerifDeadline)) {
                        $spjVerifikatorSlaStatus = 'Terlambat';
                        $spjVerifikasiTerlambatCount++;
                    } else {
                        $spjVerifikatorSlaStatus = 'Tepat Waktu';
                        $spjVerifikasiTepatWaktuCount++;
                    }
                } else {
                    $spjVerifikatorSlaStatus = 'Tepat Waktu';
                    $spjVerifikasiTepatWaktuCount++;
                }
            } elseif (!empty($p->spj_verifikator_deadline)) {
                $spjVerifDeadline = Carbon::parse($p->spj_verifikator_deadline);
                if ($now->gt($spjVerifDeadline)) {
                    $spjVerifikatorSlaStatus = 'Terlambat';
                    $spjVerifikasiTerlambatCount++;
                } else {
                    $spjVerifikatorSlaStatus = 'Berjalan';
                }
            }

            $isOverallTerlambat = ($spmSlaStatus == 'Terlambat' || $spjPemohonSlaStatus == 'Terlambat' || $spjVerifikatorSlaStatus == 'Terlambat');
            $isOverallTepatWaktu = ($spmSlaStatus == 'Tepat Waktu' || $spjPemohonSlaStatus == 'Tepat Waktu' || $spjVerifikatorSlaStatus == 'Tepat Waktu') && !$isOverallTerlambat;

            // Filter Ketepatan jika dipilih user
            if ($filterKetepatan == 'tepat_waktu' && !$isOverallTepatWaktu) {
                continue;
            }
            if ($filterKetepatan == 'terlambat' && !$isOverallTerlambat) {
                continue;
            }

            $isUptd = str_contains(strtoupper($p->bidang), 'UPTD') || str_contains(strtoupper($p->bidang), 'SATPEL');

            $daftarSpmMonitoring[] = [
                'pengajuan' => $p,
                'is_uptd' => $isUptd,
                'spm_sla_status' => $spmSlaStatus,
                'spj_pemohon_sla_status' => $spjPemohonSlaStatus,
                'spj_verifikator_sla_status' => $spjVerifikatorSlaStatus,
                'is_overall_terlambat' => $isOverallTerlambat,
                'is_overall_tepat_waktu' => $isOverallTepatWaktu,
            ];
        }

        // Hitung Skor SLA Keseluruhan (%)
        $totalSlaEvaluated = ($spmTepatWaktuCount + $spmTerlambatCount) + ($spjPemohonTepatWaktuCount + $spjPemohonTerlambatCount) + ($spjVerifikasiTepatWaktuCount + $spjVerifikasiTerlambatCount);
        $totalSlaPassed = $spmTepatWaktuCount + $spjPemohonTepatWaktuCount + $spjVerifikasiTepatWaktuCount;
        $overallSlaScore = $totalSlaEvaluated > 0 ? round(($totalSlaPassed / $totalSlaEvaluated) * 100, 1) : 100;

        // =========================================================================
        // 6. METRIKS PERFORMA & KETERLIBATAN PIHAK YANG TERLIBAT (STAKEHOLDERS)
        // =========================================================================
        $bidangPerformance = [];
        foreach ($daftarBidang as $bName) {
            $bQuery = (clone $query)->where('bidang', $bName);
            $totalB = $bQuery->count();
            if ($totalB == 0) continue;

            $isUptd = str_contains(strtoupper($bName), 'UPTD') || str_contains(strtoupper($bName), 'SATPEL');

            $spjUploadedCount = (clone $bQuery)->whereIn('spj_status', ['Menunggu Verifikasi SPJ', 'SPJ Lengkap'])->count();
            $spjTerlambatCount = (clone $bQuery)->where(function($q) {
                $q->where('spj_deadline', '<', Carbon::now())
                  ->whereNotIn('spj_status', ['SPJ Lengkap']);
            })->count();

            $bidangPerformance[] = [
                'bidang' => $bName,
                'is_uptd' => $isUptd,
                'total_pengajuan' => $totalB,
                'total_nilai' => (clone $bQuery)->sum('nilai_neto'),
                'spj_uploaded' => $spjUploadedCount,
                'spj_terlambat' => $spjTerlambatCount,
                'timeliness_rate' => $totalB > 0 ? round((($totalB - $spjTerlambatCount) / $totalB) * 100, 1) : 100,
            ];
        }

        // Urutkan UPTD di paling atas / kelompokkan rapi
        usort($bidangPerformance, function($a, $b) {
            if ($a['is_uptd'] === $b['is_uptd']) {
                return strcmp($a['bidang'], $b['bidang']);
            }
            return $a['is_uptd'] ? -1 : 1;
        });

        // Performa Role Utama
        $verifikatorCount = User::where('role', 'Verifikator Keuangan')->count();
        $ppkCount = User::where('role', 'PPK')->count();
        $operatorPembayaranCount = User::where('role', 'Operator Pembayaran')->count();
        $bendaharaCount = User::where('role', 'Bendahara')->count();

        $stakeholderMetrics = [
            'verifikator' => [
                'total_user' => $verifikatorCount,
                'verified_total' => (clone $query)->whereNotIn('status', ['Draft', 'Menunggu Verifikasi'])->count(),
                'spm_tepat_waktu' => $spmTepatWaktuCount,
                'spm_terlambat' => $spmTerlambatCount,
            ],
            'ppk' => [
                'total_user' => $ppkCount,
                'approved_total' => (clone $query)->whereNotIn('status', ['Draft', 'Menunggu Verifikasi', 'Perlu Perbaikan', 'Proses Persetujuan PPK'])->count(),
            ],
            'operator_pembayaran' => [
                'total_user' => $operatorPembayaranCount,
                'spm_issued_total' => (clone $query)->whereNotNull('no_spm')->count(),
            ],
            'bendahara' => [
                'total_user' => $bendaharaCount,
                'cair_total' => (clone $query)->whereIn('status', ['Dicairkan', 'Selesai'])->count(),
                'penyerahan_total' => (clone $query)->whereNotNull('bukti_penyerahan')->count(),
            ]
        ];

        // 7. Limit Data SPM untuk tabel standar dashboard (20 item)
        $daftarSpm = (clone $query)->whereNotNull('no_spm')
            ->orderBy('tgl_spm', 'desc')
            ->select('id', 'no_pengajuan', 'nama_kegiatan', 'no_spm', 'tgl_spm', 'status', 'spj_status', 'nilai_neto')
            ->take(20)
            ->get();

        // =========================================================================
        // 8. DATA TO-DO LIST (TASK INBOX) BERDASARKAN ROLE USER YANG LOGIN
        // =========================================================================
        $userRole = Auth::user()->role;
        $userBidang = Auth::user()->bidang;

        $todoItems = [];
        $todoTitle = '';
        $todoSubtitle = '';

        if ($userRole == 'Operator Bidang') {
            $todoTitle = 'Daftar Tugas & Tindakan Operator Bidang / UPTD';
            $todoSubtitle = 'Daftar berkas yang memerlukan tindakan perbaikan, upload SPJ, atau pengajuan baru dari bidang Anda.';

            // 1. Dokumen Perlu Perbaikan (Revisi)
            $revisiItems = PengajuanLs::where('bidang', $userBidang)
                ->where('status', 'Perlu Perbaikan')
                ->orderBy('updated_at', 'desc')
                ->get();
            foreach ($revisiItems as $item) {
                $todoItems[] = [
                    'type' => 'danger',
                    'icon' => 'bi-exclamation-triangle-fill',
                    'title' => 'Revisi Berkas: ' . $item->no_pengajuan,
                    'desc' => 'Dikembalikan oleh Verifikator Keuangan. Catatan: "' . ($item->catatan_koreksi ?? 'Perlu perbaikan kelengkapan') . '"',
                    'date' => Carbon::parse($item->updated_at)->diffForHumans(),
                    'badge' => 'Segera Diperbaiki',
                    'badge_class' => 'bg-danger text-white',
                    'action_url' => route('pengajuan.show', $item->id),
                    'action_label' => 'Perbaiki Dokumen',
                ];
            }

            // 2. SPM Terbit - Tenggat Upload SPJ (5 Hari Kerja)
            $pendingSpjItems = PengajuanLs::where('bidang', $userBidang)
                ->whereNotNull('no_spm')
                ->whereNotIn('spj_status', ['Menunggu Verifikasi SPJ', 'SPJ Lengkap'])
                ->orderBy('spj_deadline', 'asc')
                ->get();
            foreach ($pendingSpjItems as $item) {
                $deadline = !empty($item->spj_deadline) ? Carbon::parse($item->spj_deadline) : null;
                $isOverdue = $deadline && Carbon::now()->gt($deadline);
                $diffText = $deadline ? ($isOverdue ? 'Terlambat ' . $deadline->diffForHumans() : 'Tenggat ' . $deadline->diffForHumans()) : 'Tenggat 5 Hari';

                $todoItems[] = [
                    'type' => $isOverdue ? 'danger' : 'warning',
                    'icon' => 'bi-upload',
                    'title' => 'Unggah Berkas SPJ: ' . $item->no_pengajuan,
                    'desc' => 'SPM terbit. Segera unggah pertanggungjawaban (SPJ) kegiatan: "' . \Illuminate\Support\Str::limit($item->nama_kegiatan, 40) . '"',
                    'date' => $diffText,
                    'badge' => $isOverdue ? '🔴 Melebihi SLA' : '🟡 SLA 5 Hari',
                    'badge_class' => $isOverdue ? 'bg-danger text-white' : 'bg-warning bg-opacity-25 text-dark border border-warning',
                    'action_url' => route('pengajuan.show', $item->id),
                    'action_label' => 'Upload SPJ',
                ];
            }
        } elseif ($userRole == 'Verifikator Keuangan') {
            $todoTitle = 'Daftar Tugas & Antrean Verifikator Keuangan';
            $todoSubtitle = 'Daftar pengajuan baru yang butuh verifikasi SPM (SLA 2 hari) dan SPJ Pemohon (SLA 2 hari).';

            // 1. Antrean Verifikasi SPM (Status: Menunggu Verifikasi)
            $verifSpmItems = PengajuanLs::where('status', 'Menunggu Verifikasi')
                ->orderBy('tgl_pengajuan', 'asc')
                ->get();
            foreach ($verifSpmItems as $item) {
                $deadline = !empty($item->verifikator_spm_deadline) ? Carbon::parse($item->verifikator_spm_deadline) : null;
                $isOverdue = $deadline && Carbon::now()->gt($deadline);

                $todoItems[] = [
                    'type' => $isOverdue ? 'danger' : 'primary',
                    'icon' => 'bi-shield-check',
                    'title' => 'Verifikasi SPM Baru: ' . $item->no_pengajuan,
                    'desc' => 'Bidang: ' . $item->bidang . ' | Kegiatan: "' . \Illuminate\Support\Str::limit($item->nama_kegiatan, 40) . '" (Rp ' . number_format($item->nilai_neto, 0, ',', '.') . ')',
                    'date' => $deadline ? ($isOverdue ? 'Terlambat SLA' : 'SLA ' . $deadline->diffForHumans()) : 'Max 2 Hari Kerja',
                    'badge' => $isOverdue ? '🔴 Melebihi SLA 2 Hari' : '🔵 Antrean Verifikasi SPM',
                    'badge_class' => $isOverdue ? 'bg-danger text-white' : 'bg-primary bg-opacity-15 text-primary border border-primary border-opacity-25',
                    'action_url' => route('pengajuan.show', $item->id),
                    'action_label' => 'Verifikasi & Terbitkan SPM',
                ];
            }

            // 2. Antrean Verifikasi SPJ (Status: Menunggu Verifikasi SPJ)
            $verifSpjItems = PengajuanLs::where('spj_status', 'Menunggu Verifikasi SPJ')
                ->orderBy('updated_at', 'asc')
                ->get();
            foreach ($verifSpjItems as $item) {
                $deadline = !empty($item->spj_verifikator_deadline) ? Carbon::parse($item->spj_verifikator_deadline) : null;
                $isOverdue = $deadline && Carbon::now()->gt($deadline);

                $todoItems[] = [
                    'type' => $isOverdue ? 'danger' : 'info',
                    'icon' => 'bi-check2-all',
                    'title' => 'Verifikasi SPJ Pemohon: ' . $item->no_pengajuan,
                    'desc' => 'SPJ telah diunggah oleh ' . $item->bidang . '. Periksa kelengkapan kuitansi/bukti SPJ.',
                    'date' => $deadline ? ($isOverdue ? 'Terlambat SLA' : 'SLA ' . $deadline->diffForHumans()) : 'Max 2 Hari Kerja',
                    'badge' => $isOverdue ? '🔴 Melebihi SLA 2 Hari' : '🟢 Antrean Verif SPJ',
                    'badge_class' => $isOverdue ? 'bg-danger text-white' : 'bg-info bg-opacity-15 text-info border border-info border-opacity-25',
                    'action_url' => route('pengajuan.show', $item->id),
                    'action_label' => 'Periksa & Sahkan SPJ',
                ];
            }
        } elseif ($userRole == 'PPK') {
            $todoTitle = 'Daftar Tugas & Antrean Persetujuan PPK';
            $todoSubtitle = 'Daftar pengajuan yang telah diverifikasi dan menunggu tanda tangan / persetujuan Pejabat Pembuat Komitmen.';

            $ppkItems = PengajuanLs::where('status', 'Proses Persetujuan PPK')
                ->orderBy('updated_at', 'asc')
                ->get();
            foreach ($ppkItems as $item) {
                $todoItems[] = [
                    'type' => 'primary',
                    'icon' => 'bi-person-check-fill',
                    'title' => 'Persetujuan PPK: ' . $item->no_pengajuan,
                    'desc' => 'Bidang: ' . $item->bidang . ' | Kegiatan: "' . \Illuminate\Support\Str::limit($item->nama_kegiatan, 40) . '" | Neto: Rp ' . number_format($item->nilai_neto, 0, ',', '.'),
                    'date' => Carbon::parse($item->updated_at)->diffForHumans(),
                    'badge' => 'Menunggu Approval PPK',
                    'badge_class' => 'bg-primary text-white',
                    'action_url' => route('pengajuan.show', $item->id),
                    'action_label' => 'Tinjau & Setujui',
                ];
            }
        } elseif ($userRole == 'Operator Pembayaran') {
            $todoTitle = 'Daftar Tugas Operator SAKTI / Pembayaran';
            $todoSubtitle = 'Daftar pengajuan yang telah disetujui PPK dan siap diajukan ke aplikasi SAKTI.';

            $saktiItems = PengajuanLs::where('status', 'Diajukan ke SAKTI')
                ->orderBy('updated_at', 'asc')
                ->get();
            foreach ($saktiItems as $item) {
                $todoItems[] = [
                    'type' => 'info',
                    'icon' => 'bi-send-check-fill',
                    'title' => 'Proses SAKTI (SPM): ' . $item->no_pengajuan,
                    'desc' => 'Telah disetujui PPK. Rekam nomor SPM dari SAKTI untuk pengajuan: "' . \Illuminate\Support\Str::limit($item->nama_kegiatan, 40) . '"',
                    'date' => Carbon::parse($item->updated_at)->diffForHumans(),
                    'badge' => 'Proses SAKTI',
                    'badge_class' => 'bg-info text-white',
                    'action_url' => route('pengajuan.show', $item->id),
                    'action_label' => 'Input No SPM SAKTI',
                ];
            }
        } elseif ($userRole == 'Bendahara') {
            $todoTitle = 'Daftar Tugas Bendahara Pengeluaran';
            $todoSubtitle = 'Daftar pengajuan yang menunggu terbit SP2D / pencairan serta penyerahan uang.';

            $cairItems = PengajuanLs::where('status', 'Belum Terbit SP2D')
                ->orderBy('updated_at', 'asc')
                ->get();
            foreach ($cairItems as $item) {
                $todoItems[] = [
                    'type' => 'success',
                    'icon' => 'bi-wallet2',
                    'title' => 'Pencairan SP2D / Penyerahan Uang: ' . $item->no_pengajuan,
                    'desc' => 'SPM: ' . ($item->no_spm ?? '-') . ' | Nilai Neto: Rp ' . number_format($item->nilai_neto, 0, ',', '.') . ' | Bidang: ' . $item->bidang,
                    'date' => Carbon::parse($item->updated_at)->diffForHumans(),
                    'badge' => 'Siap Dicairkan',
                    'badge_class' => 'bg-success text-white',
                    'action_url' => route('pengajuan.show', $item->id),
                    'action_label' => 'Proses Pencairan / Penyerahan',
                ];
            }
        }

        return view('dashboard', compact(
            'totalPengajuan',
            'menungguVerifikasi',
            'perluPerbaikan',
            'prosesPersetujuanPpk',
            'diajukanSakti',
            'menungguSp2d',
            'dicairkan',
            'totalNilaiBulanIni',
            'labelBidang',
            'angkaBidang',
            'daftarSpm',
            'daftarTahun',
            'tahunAktif',
            'daftarBidang',
            'filterBidang',
            'filterKetepatan',
            'spmTepatWaktuCount',
            'spmTerlambatCount',
            'spjPemohonTepatWaktuCount',
            'spjPemohonTerlambatCount',
            'spjVerifikasiTepatWaktuCount',
            'spjVerifikasiTerlambatCount',
            'overallSlaScore',
            'daftarSpmMonitoring',
            'bidangPerformance',
            'stakeholderMetrics',
            'todoItems',
            'todoTitle',
            'todoSubtitle'
        ));
    }

    public function markNotificationAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
        $notification->is_read = true;
        $notification->save();

        return back();
    }
}