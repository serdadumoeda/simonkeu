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

        // Ambil daftar bidang & UPTD unik (termasuk nama akun UPTD)
        $uptdUserNames = User::where('role', 'Operator Bidang')
            ->where('bidang', 'UPTD')
            ->pluck('name')
            ->toArray();

        $daftarBidang = User::where('role', 'Operator Bidang')
            ->distinct()
            ->pluck('bidang')
            ->merge(PengajuanLs::distinct()->pluck('bidang'))
            ->merge($uptdUserNames)
            ->filter(fn($val) => !empty($val) && $val !== 'None' && $val !== 'Keuangan')
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Operator Bidang: hanya tampilkan bidang miliknya di dropdown filter
        if (Auth::user()->role == 'Operator Bidang') {
            $userB = Auth::user()->bidang;
            $userN = Auth::user()->name;
            $daftarBidang = array_values(array_filter($daftarBidang, fn($b) => $b === $userB || $b === $userN));
        }

        // 1. Siapkan Query Dasar dengan Filter Hak Akses & Tahun Anggaran
        $query = PengajuanLs::query();

        if ($tahunAktif !== 'semua' && !empty($tahunAktif)) {
            $query->whereYear('tgl_pengajuan', $tahunAktif);
        }

        if (!empty($filterBidang)) {
            $query->where(function($q) use ($filterBidang) {
                $q->where('bidang', $filterBidang)
                  ->orWhereHas('user', function($uQ) use ($filterBidang) {
                      $uQ->where('name', $filterBidang);
                  });
            });
        }

        // Jika yang login adalah Operator Bidang, dia hanya menghitung data bidangnya saja
        if (Auth::user()->role == 'Operator Bidang') {
            $userB = Auth::user()->bidang;
            if ($userB === 'UPTD' || str_contains(strtoupper($userB), 'UPTD')) {
                // UPTD: filter per user_id agar data UPTD A tidak terlihat oleh UPTD B
                $query->where('user_id', Auth::id());
            } else {
                $query->where('bidang', Auth::user()->bidang);
            }
        }

        // 2. Menghitung Statistik Status
        $totalPengajuan = $query->count();
        $draftCount = (clone $query)->where('status', 'Draft')->count();
        $menungguVerifikasi = (clone $query)->where('status', 'Menunggu Verifikasi')->count();
        $perluPerbaikan = (clone $query)->where('status', 'Perlu Perbaikan')->count();
        $prosesPersetujuanPpk = (clone $query)->where('status', 'Proses Persetujuan PPK')->count();
        $penerbitanSpp = (clone $query)->where('status', 'Penerbitan SPP')->count();
        $sppMenungguTtd = (clone $query)->where('status', 'SPP Menunggu TTD UPTD')->count();
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
        // 5. KALKULASI SLA UTAMA (PENCAIRAN KEUANGAN 7 HARI / 1 MINGGU) & SLA PASCA CAIR
        // =========================================================================
        $allPengajuanSla = (clone $query)->with('user')->orderBy('tgl_pengajuan', 'desc')->orderBy('id', 'desc')->get();

        $pencairanTepatWaktuCount = 0;
        $pencairanDalamProsesCount = 0;
        $pencairanTerlambatCount = 0;

        $spmTepatWaktuCount = 0;
        $spmTerlambatCount = 0;
        $spjPemohonTepatWaktuCount = 0;
        $spjPemohonTerlambatCount = 0;
        $spjVerifikasiTepatWaktuCount = 0;
        $spjVerifikasiTerlambatCount = 0;

        $daftarSpmMonitoring = [];

        foreach ($allPengajuanSla as $p) {
            $now = Carbon::now();

            // A. SLA UTAMA: Pemohon Mengajukan s/d Bendahara Menyerahkan Uang (Max 7 Hari / 1 Minggu)
            $tglPengajuan = !empty($p->tgl_pengajuan) ? Carbon::parse($p->tgl_pengajuan) : Carbon::parse($p->created_at);
            $tglSelesaiCair = !empty($p->tgl_cair) ? Carbon::parse($p->tgl_cair) : (in_array($p->status, ['Dicairkan', 'Selesai']) ? Carbon::parse($p->updated_at) : null);

            $pencairanSlaStatus = 'Dalam Proses';
            $durasiPencairanHari = 0;

            if ($tglSelesaiCair) {
                $durasiPencairanHari = (int) round($tglPengajuan->diffInDays($tglSelesaiCair));
                if ($durasiPencairanHari <= 7) {
                    $pencairanSlaStatus = 'Tepat Waktu';
                    $pencairanTepatWaktuCount++;
                } else {
                    $pencairanSlaStatus = 'Terlambat';
                    $pencairanTerlambatCount++;
                }
            } else {
                $durasiPencairanHari = (int) round($tglPengajuan->diffInDays($now));
                if ($durasiPencairanHari <= 7) {
                    $pencairanSlaStatus = 'Dalam Proses';
                    $pencairanDalamProsesCount++;
                } else {
                    $pencairanSlaStatus = 'Terlambat';
                    $pencairanTerlambatCount++;
                }
            }

            // B. Evaluasi SLA Upload SPM/SP2D oleh Verifikator (2 Hari)
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

            // C. Evaluasi SLA Upload SPJ Pemohon (5 Hari)
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

            // D. Evaluasi SLA Verifikasi SPJ (2 Hari)
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

            $isOverallTerlambat = ($pencairanSlaStatus == 'Terlambat' || $spmSlaStatus == 'Terlambat' || $spjPemohonSlaStatus == 'Terlambat' || $spjVerifikatorSlaStatus == 'Terlambat');
            $isOverallTepatWaktu = ($pencairanSlaStatus == 'Tepat Waktu' || $spmSlaStatus == 'Tepat Waktu' || $spjPemohonSlaStatus == 'Tepat Waktu' || $spjVerifikatorSlaStatus == 'Tepat Waktu') && !$isOverallTerlambat;

            // Filter Ketepatan jika dipilih user
            if ($filterKetepatan == 'tepat_waktu' && !$isOverallTepatWaktu) {
                continue;
            }
            if ($filterKetepatan == 'terlambat' && !$isOverallTerlambat) {
                continue;
            }

            $isUptd = str_contains(strtoupper($p->bidang), 'UPTD');

            $daftarSpmMonitoring[] = [
                'pengajuan' => $p,
                'is_uptd' => $isUptd,
                'pencairan_sla_status' => $pencairanSlaStatus,
                'durasi_pencairan_hari' => $durasiPencairanHari,
                'spm_sla_status' => $spmSlaStatus,
                'spj_pemohon_sla_status' => $spjPemohonSlaStatus,
                'spj_verifikator_sla_status' => $spjVerifikatorSlaStatus,
                'is_overall_terlambat' => $isOverallTerlambat,
                'is_overall_tepat_waktu' => $isOverallTepatWaktu,
            ];
        }

        // Hitung Skor SLA Keseluruhan (%)
        $totalSlaEvaluated = ($pencairanTepatWaktuCount + $pencairanTerlambatCount) + ($spmTepatWaktuCount + $spmTerlambatCount) + ($spjPemohonTepatWaktuCount + $spjPemohonTerlambatCount) + ($spjVerifikasiTepatWaktuCount + $spjVerifikasiTerlambatCount);
        $totalSlaPassed = $pencairanTepatWaktuCount + $spmTepatWaktuCount + $spjPemohonTepatWaktuCount + $spjVerifikasiTepatWaktuCount;
        $overallSlaScore = $totalSlaEvaluated > 0 ? round(($totalSlaPassed / $totalSlaEvaluated) * 100, 1) : 100;

        // =========================================================================
        // 6. METRIKS PERFORMA & KETERLIBATAN PIHAK YANG TERLIBAT (STAKEHOLDERS)
        // =========================================================================
        // Untuk Operator Bidang, daftarBidang sudah difilter ke bidang sendiri saja,
        // sehingga bidangPerformance juga otomatis hanya menampilkan bidangnya.
        $bidangPerformance = [];

        // A. Proses Bidang Pusat (Non-UPTD): POKJA, Pemberdayaan, Penyelenggara, Produktivitas, Umum, dll.
        $pusatBidangList = User::where('role', 'Operator Bidang')
            ->where('bidang', '!=', 'UPTD')
            ->pluck('bidang')
            ->merge(PengajuanLs::where('bidang', '!=', 'UPTD')->pluck('bidang'))
            ->filter(fn($val) => !empty($val) && $val !== 'None' && $val !== 'Keuangan' && $val !== 'UPTD')
            ->unique()
            ->sort()
            ->values();

        foreach ($pusatBidangList as $pName) {
            $bQuery = (clone $query)->where('bidang', $pName);
            $totalB = $bQuery->count();
            if ($totalB == 0) continue;

            $spjUploadedCount = (clone $bQuery)->whereIn('spj_status', ['Menunggu Verifikasi SPJ', 'SPJ Lengkap'])->count();
            $spjTerlambatCount = (clone $bQuery)->where(function($q) {
                $q->where('spj_deadline', '<', Carbon::now())
                  ->whereNotIn('spj_status', ['SPJ Lengkap']);
            })->count();

            $bidangPerformance[] = [
                'bidang' => $pName,
                'is_uptd' => false,
                'total_pengajuan' => $totalB,
                'total_nilai' => (clone $bQuery)->sum('nilai_neto'),
                'spj_uploaded' => $spjUploadedCount,
                'spj_terlambat' => $spjTerlambatCount,
                'timeliness_rate' => $totalB > 0 ? round((($totalB - $spjTerlambatCount) / $totalB) * 100, 1) : 100,
            ];
        }

        // B. Breakdown Per User UPTD (misal: BLK_Kulon Progo, BLK_Wonogiri, BLK_Pacitan, BLK_Madiun, dll.)
        $uptdUsers = User::where('bidang', 'UPTD')->get();
        $uptdUserIdsInPengajuan = (clone $query)->where('bidang', 'UPTD')->pluck('user_id')->filter()->unique();
        $allUptdUserIds = $uptdUsers->pluck('id')->merge($uptdUserIdsInPengajuan)->unique();
        $allUptdUsers = User::whereIn('id', $allUptdUserIds)->get();

        $trackedUptdUserIds = [];

        foreach ($allUptdUsers as $uUptd) {
            $bQuery = (clone $query)->where(function($q) use ($uUptd) {
                $q->where('user_id', $uUptd->id)
                  ->orWhere('bidang', $uUptd->name);
            });

            $totalB = $bQuery->count();
            if ($totalB == 0) continue;

            $trackedUptdUserIds[] = $uUptd->id;

            $spjUploadedCount = (clone $bQuery)->whereIn('spj_status', ['Menunggu Verifikasi SPJ', 'SPJ Lengkap'])->count();
            $spjTerlambatCount = (clone $bQuery)->where(function($q) {
                $q->where('spj_deadline', '<', Carbon::now())
                  ->whereNotIn('spj_status', ['SPJ Lengkap']);
            })->count();

            $bidangPerformance[] = [
                'bidang' => $uUptd->name,
                'is_uptd' => true,
                'total_pengajuan' => $totalB,
                'total_nilai' => (clone $bQuery)->sum('nilai_neto'),
                'spj_uploaded' => $spjUploadedCount,
                'spj_terlambat' => $spjTerlambatCount,
                'timeliness_rate' => $totalB > 0 ? round((($totalB - $spjTerlambatCount) / $totalB) * 100, 1) : 100,
            ];
        }

        // C. Fallback jika ada Pengajuan UPTD tanpa user_id terdaftar
        $untrackedUptdQuery = (clone $query)->where('bidang', 'UPTD')->whereNotIn('user_id', $trackedUptdUserIds);
        $untrackedCount = $untrackedUptdQuery->count();
        if ($untrackedCount > 0) {
            $spjUploadedCount = (clone $untrackedUptdQuery)->whereIn('spj_status', ['Menunggu Verifikasi SPJ', 'SPJ Lengkap'])->count();
            $spjTerlambatCount = (clone $untrackedUptdQuery)->where(function($q) {
                $q->where('spj_deadline', '<', Carbon::now())
                  ->whereNotIn('spj_status', ['SPJ Lengkap']);
            })->count();

            $bidangPerformance[] = [
                'bidang' => 'UPTD (Lainnya)',
                'is_uptd' => true,
                'total_pengajuan' => $untrackedCount,
                'total_nilai' => (clone $untrackedUptdQuery)->sum('nilai_neto'),
                'spj_uploaded' => $spjUploadedCount,
                'spj_terlambat' => $spjTerlambatCount,
                'timeliness_rate' => $untrackedCount > 0 ? round((($untrackedCount - $spjTerlambatCount) / $untrackedCount) * 100, 1) : 100,
            ];
        }

        // Urutkan UPTD di paling atas / kelompokkan rapi berdasarkan nama
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

            $isUptdUser = $userBidang === 'UPTD' || str_contains(strtoupper($userBidang), 'UPTD');

            // 1. Dokumen Perlu Perbaikan (Revisi)
            if ($isUptdUser) {
                $revisiItems = PengajuanLs::where('user_id', Auth::id())
                    ->where('status', 'Perlu Perbaikan')
                    ->orderBy('updated_at', 'desc')
                    ->get();
            } else {
                $revisiItems = PengajuanLs::where('bidang', $userBidang)
                    ->where('status', 'Perlu Perbaikan')
                    ->orderBy('updated_at', 'desc')
                    ->get();
            }
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

            // 2. SPP Menunggu Tanda Tangan UPTD
            if ($isUptdUser) {
                $sppTtdItems = PengajuanLs::where('user_id', Auth::id())
                    ->where('status', 'SPP Menunggu TTD UPTD')
                    ->orderBy('updated_at', 'desc')
                    ->get();
                foreach ($sppTtdItems as $item) {
                    $todoItems[] = [
                        'type' => 'warning',
                        'icon' => 'bi-pen-fill',
                        'title' => 'Tanda Tangani SPP: ' . $item->no_pengajuan,
                        'desc' => 'Dokumen SPP (No. ' . ($item->no_spp ?? '-') . ') telah diterbitkan. Download, tanda tangani (tanpa cap basah), dan unggah kembali.',
                        'date' => Carbon::parse($item->updated_at)->diffForHumans(),
                        'badge' => '📄 Perlu TTD SPP',
                        'badge_class' => 'bg-warning bg-opacity-25 text-dark border border-warning',
                        'action_url' => route('pengajuan.show', $item->id),
                        'action_label' => 'TTD & Upload SPP',
                    ];
                }
            }

            // 3. SPM Terbit - Tenggat Upload SPJ (5 Hari Kerja)
            if ($isUptdUser) {
                $pendingSpjItems = PengajuanLs::where('user_id', Auth::id())
                    ->whereNotNull('no_spm')
                    ->whereNotIn('spj_status', ['Menunggu Verifikasi SPJ', 'SPJ Lengkap'])
                    ->orderBy('spj_deadline', 'asc')
                    ->get();
            } else {
                $pendingSpjItems = PengajuanLs::where('bidang', $userBidang)
                    ->whereNotNull('no_spm')
                    ->whereNotIn('spj_status', ['Menunggu Verifikasi SPJ', 'SPJ Lengkap'])
                    ->orderBy('spj_deadline', 'asc')
                    ->get();
            }
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
            $todoSubtitle = 'Daftar pengajuan yang memerlukan penerbitan SPP dan proses SPM SAKTI.';

            // 1. Antrean Penerbitan SPP
            $sppItems = PengajuanLs::where('status', 'Penerbitan SPP')
                ->orderBy('updated_at', 'asc')
                ->get();
            foreach ($sppItems as $item) {
                $todoItems[] = [
                    'type' => 'warning',
                    'icon' => 'bi-file-earmark-text-fill',
                    'title' => 'Penerbitan SPP: ' . $item->no_pengajuan,
                    'desc' => 'Disetujui PPK. Terbitkan SPP untuk pengajuan: "' . \Illuminate\Support\Str::limit($item->nama_kegiatan, 40) . '"',
                    'date' => Carbon::parse($item->updated_at)->diffForHumans(),
                    'badge' => 'Penerbitan SPP',
                    'badge_class' => 'bg-warning text-dark',
                    'action_url' => route('pengajuan.show', $item->id),
                    'action_label' => 'Terbitkan SPP',
                ];
            }

            // 2. Antrean Validasi SPP Bertandatangan UPTD
            $sppTtdItems = PengajuanLs::where('status', 'SPP Menunggu TTD UPTD')
                ->whereNotNull('spp_signed_link')
                ->orderBy('spp_signed_at', 'asc')
                ->get();
            foreach ($sppTtdItems as $item) {
                $todoItems[] = [
                    'type' => 'success',
                    'icon' => 'bi-check2-circle',
                    'title' => 'Validasi SPP TTD UPTD: ' . $item->no_pengajuan,
                    'desc' => 'SPP ' . ($item->no_spp ?? '-') . ' sudah ditandatangani UPTD. Validasi dan lanjutkan ke proses SAKTI (SPM).',
                    'date' => $item->spp_signed_at ? Carbon::parse($item->spp_signed_at)->diffForHumans() : 'Baru diunggah',
                    'badge' => '✅ SPP Siap Validasi',
                    'badge_class' => 'bg-success bg-opacity-15 text-success border border-success border-opacity-25',
                    'action_url' => route('pengajuan.show', $item->id),
                    'action_label' => 'Validasi & Lanjutkan ke SAKTI',
                ];
            }

            // 3. Antrean Proses SAKTI/SPM (PPSPM)
            $saktiItems = PengajuanLs::where('status', 'Diajukan ke SAKTI')
                ->orderBy('updated_at', 'asc')
                ->get();
            foreach ($saktiItems as $item) {
                $todoItems[] = [
                    'type' => 'info',
                    'icon' => 'bi-send-check-fill',
                    'title' => 'Proses SAKTI / SPM (PPSPM): ' . $item->no_pengajuan,
                    'desc' => 'SPP diterbitkan (' . ($item->no_spp ?? '-') . '). Rekam nomor SPM dari SAKTI untuk pengajuan: "' . \Illuminate\Support\Str::limit($item->nama_kegiatan, 40) . '"',
                    'date' => Carbon::parse($item->updated_at)->diffForHumans(),
                    'badge' => 'Proses SAKTI (PPSPM)',
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

        // 6. KALKULASI PETA KEMACETAN LAYANAN (BOTTLENECK HEATMAP)
        $bottleneckStages = [
            [
                'key' => 'verifikasi_uptd',
                'label' => 'Verifikasi UPTD',
                'actor' => 'PIC UPTD',
                'icon' => 'bi-building-check',
                'statuses' => ['Menunggu Verifikasi UPTD'],
            ],
            [
                'key' => 'verifikasi_keuangan',
                'label' => 'Verifikasi Keuangan',
                'actor' => 'Verifikator Keu',
                'icon' => 'bi-shield-check',
                'statuses' => ['Menunggu Verifikasi'],
            ],
            [
                'key' => 'persetujuan_ppk',
                'label' => 'Persetujuan PPK',
                'actor' => 'PPK',
                'icon' => 'bi-file-earmark-person',
                'statuses' => ['Proses Persetujuan PPK'],
            ],
            [
                'key' => 'proses_sakti',
                'label' => 'Proses SAKTI / SPM',
                'actor' => 'Op Pembayaran',
                'icon' => 'bi-receipt',
                'statuses' => ['Penerbitan SPP', 'SPP Menunggu TTD UPTD', 'Diajukan ke SAKTI'],
            ],
            [
                'key' => 'pencairan_sp2d',
                'label' => 'Pencairan SP2D',
                'actor' => 'Bendahara',
                'icon' => 'bi-wallet2',
                'statuses' => ['Belum Terbit SP2D'],
            ],
        ];

        $heatmapBottleneck = [];
        $hasBottleneckAlert = false;
        $bottleneckAlertMessage = '';

        foreach ($bottleneckStages as $stg) {
            $count = (clone $query)->whereIn('status', $stg['statuses'])->count();
            
            $oldestDoc = (clone $query)->whereIn('status', $stg['statuses'])->orderBy('updated_at', 'asc')->first();
            $isOverdue = false;
            $overdueDays = 0;
            if ($oldestDoc && $oldestDoc->updated_at) {
                $days = (int) round(Carbon::parse($oldestDoc->updated_at)->diffInDays(Carbon::now()));
                if ($days >= 2) {
                    $isOverdue = true;
                    $overdueDays = $days;
                }
            }

            $level = 'success';
            $statusText = '🟢 Lancar';
            if ($count > 5 || $isOverdue) {
                $level = 'danger';
                $statusText = '🔴 Macet / Bottleneck';
                if (!$hasBottleneckAlert) {
                    $hasBottleneckAlert = true;
                    $bottleneckAlertMessage = '🚨 Perhatian: Terdeteksi hambatan di meja ' . $stg['label'] . ' (' . $count . ' berkas pending' . ($isOverdue ? ', terlama ' . $overdueDays . ' hari' : '') . ').';
                }
            } elseif ($count >= 3) {
                $level = 'warning';
                $statusText = '🟡 Perhatian';
            }

            $heatmapBottleneck[] = [
                'key' => $stg['key'],
                'label' => $stg['label'],
                'actor' => $stg['actor'],
                'icon' => $stg['icon'],
                'count' => $count,
                'level' => $level,
                'status_text' => $statusText,
                'is_overdue' => $isOverdue,
                'overdue_days' => $overdueDays,
            ];
        }

        return view('dashboard', compact(
            'totalPengajuan',
            'draftCount',
            'menungguVerifikasi',
            'perluPerbaikan',
            'prosesPersetujuanPpk',
            'penerbitanSpp',
            'sppMenungguTtd',
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
            'todoSubtitle',
            'heatmapBottleneck',
            'hasBottleneckAlert',
            'bottleneckAlertMessage'
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