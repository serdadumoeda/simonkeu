<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengajuanLs;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use App\Models\User;

class PengajuanController extends Controller
{
    /// 1. DAFTAR PENGAJUAN (Mendukung Filter Role Khusus)
    public function index(Request $request)
    {
        // Mulai membuat query untuk mengambil data pengajuan
        $query = PengajuanLs::with('user')->orderBy('created_at', 'desc');

        $user = Auth::user(); // Mengambil data user yang sedang login

        // =========================================================
        // LOGIKA FILTER OTOMATIS BERDASARKAN ROLE (HAK AKSES)
        // =========================================================

        if ($user->role == 'Operator Bidang') {
            // Operator HANYA melihat pengajuan milik bidangnya sendiri
            $query->where('bidang', $user->bidang);

        } elseif ($user->role == 'Verifikator Keuangan') {
            // Verifikator melihat dokumen yang sudah diajukan (bukan Draft)
            // Prioritas mereka adalah yang berstatus 'Menunggu Verifikasi'
            $query->where('status', '!=', 'Draft');

        } elseif ($user->role == 'PPK') {
            // PPK HANYA melihat dokumen yang sudah lolos dari Verifikator dan seterusnya
            $query->whereIn('status', [
                'Disetujui PPK',
                'Diajukan ke SAKTI',
                'Belum Terbit SP2D',
                'Dicairkan',
                'Perlu Perbaikan',
                'Selesai'
            ]);

        } elseif ($user->role == 'Operator Pembayaran') {
            // Operator Pembayaran HANYA melihat dokumen yang sudah disetujui PPK
            $query->whereIn('status', [
                'Diajukan ke SAKTI',
                'Belum Terbit SP2D',
                'Dicairkan',
                'Selesai'
            ]);

        } elseif ($user->role == 'Bendahara') {
            // Bendahara HANYA melihat dokumen yang menunggu SP2D dan yang sudah Cair
            $query->whereIn('status', [
                'Belum Terbit SP2D',
                'Dicairkan',
                'Selesai'
            ]);
        }
        // Catatan: Jika yang login adalah 'Admin Keuangan', query tidak ditambahkan batasan apa-apa,
        // sehingga Admin bisa melihat SELURUH data.


        // =========================================================
        // FILTER TAMBAHAN DARI FORM PENCARIAN (DROPDOWN)
        // =========================================================
        if ($request->filled('bidang')) {
            $query->where('bidang', $request->bidang);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Ambil daftar bidang secara dinamis dari database untuk filter
        $daftarBidang = \App\Models\User::where('role', 'Operator Bidang')
            ->distinct()
            ->pluck('bidang')
            ->merge(PengajuanLs::distinct()->pluck('bidang'))
            ->filter(fn($val) => !empty($val) && $val !== 'None' && $val !== 'Keuangan')
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Eksekusi query dengan PAGINATION (10 data per halaman)
        $daftarPengajuan = $query->paginate(10);

        // Kirim data ke tampilan HTML (Blade)
        return view('pengajuan.index', compact('daftarPengajuan', 'daftarBidang'));
    }

    // 2. FORM BUAT PENGAJUAN
    public function create()
    {
        if (Auth::user()->role != 'Operator Bidang') {
            abort(403, 'Akses Ditolak: Hanya Operator Bidang yang dapat membuat pengajuan.');
        }

        $tglBulanTahun = date('dmY');
        $pengajuanTerakhir = PengajuanLs::where('no_pengajuan', 'LIKE', "KU-$tglBulanTahun-%")
            ->orderBy('no_pengajuan', 'desc')
            ->first();

        $urutan = 1;
        if ($pengajuanTerakhir) {
            $urutan = (int) substr($pengajuanTerakhir->no_pengajuan, -3) + 1;
        }
        $noPengajuanBaru = "KU-" . $tglBulanTahun . "-" . str_pad($urutan, 3, '0', STR_PAD_LEFT);

        return view('pengajuan.create', compact('noPengajuanBaru'));
    }

    // 3. SIMPAN PENGAJUAN BARU (Mendukung Tautan Google Drive)
    public function store(Request $request)
    {
        if (Auth::user()->role != 'Operator Bidang') {
            abort(403, 'Akses Ditolak: Hanya Operator Bidang yang dapat menyimpan pengajuan.');
        }

        $request->validate([
            'no_pengajuan' => 'required|string|unique:pengajuan_ls,no_pengajuan',
            'nama_kegiatan' => 'required|string',
            'no_akun' => 'required|string',
            'jenis_belanja' => 'required|string',
            'nilai_bruto' => 'required|numeric',
            'nilai_neto' => 'required|numeric',
            'link_google_drive' => 'required|url',
            'kategori_pengajuan' => 'required|string|in:GU/UP/TUP,LS Kontrak,LS Non Kontrak',
        ]);

        $pengajuan = PengajuanLs::create([
            'no_pengajuan' => $request->no_pengajuan,
            'tgl_pengajuan' => now(),
            'user_id' => Auth::id(),
            'bidang' => Auth::user()->bidang,
            'nama_kegiatan' => $request->nama_kegiatan,
            'no_akun' => $request->no_akun,
            'jenis_belanja' => $request->jenis_belanja,
            'nilai_bruto' => $request->nilai_bruto,
            'nilai_neto' => $request->nilai_neto,
            'uraian_pembayaran' => $request->uraian_pembayaran,
            'link_google_drive' => $request->link_google_drive,
            'status' => $request->action == 'draft' ? 'Draft' : 'Menunggu Verifikasi',
            'kategori_pengajuan' => $request->kategori_pengajuan,
        ]);

        // Buat notifikasi jika diajukan ke Keuangan (Bukan Draft)
        if ($pengajuan->status == 'Menunggu Verifikasi') {
            $verifikators = User::where('role', 'Verifikator Keuangan')->get();
            foreach ($verifikators as $v) {
                Notification::create([
                    'user_id' => $v->id,
                    'title' => 'Pengajuan Baru Menunggu Verifikasi',
                    'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' (' . $pengajuan->nama_kegiatan . ') menunggu verifikasi Anda.',
                    'is_read' => false,
                ]);
            }
        }

        return redirect()->route('pengajuan.index')->with('success', 'Pengajuan berhasil diproses.');
    }

    // 4. DETAIL PENGAJUAN (Untuk Verifikasi/Approval)
    public function show($id)
    {
        $pengajuan = PengajuanLs::findOrFail($id);
        $user = Auth::user();

        // Cek otorisasi berdasarkan role (sesuai filter pada index)
        if ($user->role == 'Operator Bidang') {
            if ($pengajuan->bidang != $user->bidang) {
                abort(403, 'Akses Ditolak: Anda tidak berhak melihat pengajuan dari bidang lain.');
            }
        } elseif ($user->role == 'Verifikator Keuangan') {
            if ($pengajuan->status == 'Draft') {
                abort(403, 'Akses Ditolak: Verifikator tidak dapat melihat dokumen berstatus Draft.');
            }
        } elseif ($user->role == 'PPK') {
            if (!in_array($pengajuan->status, ['Disetujui PPK', 'Diajukan ke SAKTI', 'Belum Terbit SP2D', 'Dicairkan', 'Perlu Perbaikan'])) {
                abort(403, 'Akses Ditolak: Dokumen belum diproses oleh Verifikator Keuangan.');
            }
        } elseif ($user->role == 'Operator Pembayaran') {
            if (!in_array($pengajuan->status, ['Diajukan ke SAKTI', 'Belum Terbit SP2D', 'Dicairkan'])) {
                abort(403, 'Akses Ditolak: Dokumen belum disetujui oleh PPK.');
            }
        } elseif ($user->role == 'Bendahara') {
            if (!in_array($pengajuan->status, ['Belum Terbit SP2D', 'Dicairkan'])) {
                abort(403, 'Akses Ditolak: Dokumen belum diproses ke tahap pembayaran.');
            }
        }

        return view('pengajuan.show', compact('pengajuan'));
    }

    // 5. PROSES VERIFIKASI (Oleh Verifikator Keuangan)
    public function verifikasi(Request $request, $id)
    {
        if (Auth::user()->role != 'Verifikator Keuangan') {
            abort(403, 'Akses Ditolak: Hanya Verifikator Keuangan yang dapat melakukan verifikasi.');
        }

        $pengajuan = PengajuanLs::findOrFail($id);
        $pengajuan->verifikator_id = Auth::id();

        if ($request->action == 'setuju') {
            $pengajuan->status = 'Disetujui PPK'; // Melangkah ke alur PPK
            
            // Kirim notifikasi ke semua PPK
            $ppks = User::where('role', 'PPK')->get();
            foreach ($ppks as $ppk) {
                Notification::create([
                    'user_id' => $ppk->id,
                    'title' => 'Persetujuan Dokumen Baru',
                    'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' telah diverifikasi Keuangan dan menunggu persetujuan Anda.',
                    'is_read' => false,
                ]);
            }
        } elseif ($request->action == 'perbaiki') {
            $pengajuan->status = 'Perlu Perbaikan';
            $pengajuan->catatan_koreksi = $request->catatan_koreksi;
            
            // Kirim notifikasi ke pemohon (Operator Bidang)
            Notification::create([
                'user_id' => $pengajuan->user_id,
                'title' => 'Revisi Pengajuan Berkas',
                'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' perlu diperbaiki: ' . $request->catatan_koreksi,
                'is_read' => false,
            ]);
        } else {
            $pengajuan->status = 'Draft'; // Ditolak total kembali jadi draft
            $pengajuan->catatan_koreksi = $request->catatan_koreksi;

            // Kirim notifikasi ke pemohon
            Notification::create([
                'user_id' => $pengajuan->user_id,
                'title' => 'Pengajuan Berkas Ditolak',
                'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' ditolak total dan dikembalikan ke Draft.',
                'is_read' => false,
            ]);
        }

        $pengajuan->save();
        return redirect()->route('pengajuan.index')->with('success', 'Status pengajuan berhasil diperbarui oleh Verifikator.');
    }

    // 6. PROSES APPROVAL PPK
    public function ppkApproval(Request $request, $id)
    {
        if (Auth::user()->role != 'PPK') {
            abort(403, 'Akses Ditolak: Hanya PPK yang dapat memberikan persetujuan.');
        }

        $pengajuan = PengajuanLs::findOrFail($id);
        $pengajuan->ppk_id = Auth::id();

        if ($request->action == 'setuju') {
            $pengajuan->status = 'Diajukan ke SAKTI';
            
            // Kirim notifikasi ke Operator Pembayaran
            $operators = User::where('role', 'Operator Pembayaran')->get();
            foreach ($operators as $op) {
                Notification::create([
                    'user_id' => $op->id,
                    'title' => 'Pengajuan SPM SAKTI',
                    'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' telah disetujui PPK, silakan ajukan SPM di Aplikasi SAKTI.',
                    'is_read' => false,
                ]);
            }
        } else {
            $pengajuan->status = 'Perlu Perbaikan';
            $pengajuan->catatan_koreksi = $request->catatan_koreksi;
            
            // Kirim notifikasi ke pemohon
            Notification::create([
                'user_id' => $pengajuan->user_id,
                'title' => 'Revisi Berkas oleh PPK',
                'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' perlu diperbaiki berdasarkan keputusan PPK: ' . $request->catatan_koreksi,
                'is_read' => false,
            ]);
        }

        $pengajuan->save();
        return redirect()->route('pengajuan.index')->with('success', 'Keputusan PPK berhasil disimpan.');
    }

    // 7. INPUT REALISASI & PENCAIRAN (Operator Pembayaran & Bendahara)
    public function realisasi(Request $request, $id)
    {
        $pengajuan = PengajuanLs::findOrFail($id);
        $user = Auth::user();

        if ($user->role == 'Operator Pembayaran') {
            $request->validate([
                'no_spm' => 'required',
            ]);
            $pengajuan->no_spm = $request->no_spm;
            $pengajuan->tgl_spm = date('Y-m-d');
            $pengajuan->operator_pembayaran_id = $user->id;
            $pengajuan->status = 'Belum Terbit SP2D';
            
            // Kirim notifikasi ke Bendahara
            $bendaharas = User::where('role', 'Bendahara')->get();
            foreach ($bendaharas as $b) {
                Notification::create([
                    'user_id' => $b->id,
                    'title' => 'Pencairan SP2D Baru',
                    'message' => 'Nomor SPM untuk ' . $pengajuan->no_pengajuan . ' telah terbit, mohon konfirmasi pencairan jika SP2D terbit.',
                    'is_read' => false,
                ]);
            }
        } elseif ($user->role == 'Bendahara') {
            if ($request->has('bukti_penyerahan')) {
                $request->validate(['bukti_penyerahan' => 'required|url']);
                $pengajuan->bukti_penyerahan = $request->bukti_penyerahan;
                $pengajuan->status = 'Selesai';
                
                // Kirim notifikasi ke pemohon
                Notification::create([
                    'user_id' => $pengajuan->user_id,
                    'title' => 'Uang Diserahkan & Proses Selesai',
                    'message' => 'Bendahara telah menyerahkan uang untuk pengajuan ' . $pengajuan->no_pengajuan . '. Silakan periksa bukti penyerahan Google Drive.',
                    'is_read' => false,
                ]);
            } else {
                $request->validate(['no_sp2d' => 'required', 'tgl_cair' => 'required']);
                $pengajuan->no_sp2d = $request->no_sp2d;
                $pengajuan->tgl_cair = $request->tgl_cair;
                $pengajuan->bendahara_id = $user->id;
                $pengajuan->status = 'Dicairkan';
                
                // Kirim notifikasi ke pemohon
                Notification::create([
                    'user_id' => $pengajuan->user_id,
                    'title' => 'Dana Berhasil Cair',
                    'message' => 'Selamat! Dana pengajuan berkas ' . $pengajuan->no_pengajuan . ' telah dicairkan oleh Bendahara. Menunggu proses penyerahan uang.',
                    'is_read' => false,
                ]);
            }
        } else {
            abort(403, 'Akses Ditolak: Anda tidak memiliki hak akses untuk memproses realisasi.');
        }

        $pengajuan->save();
        return redirect()->route('pengajuan.index')->with('success', 'Data realisasi berhasil diperbarui.');
    }

    // FITUR EKSPOR KE EXCEL
    public function exportExcel()
    {
        // Ambil semua data (bisa disesuaikan jika ingin difilter per bulan)
        $daftarPengajuan = PengajuanLs::orderBy('created_at', 'desc')->get();

        // Mengirimkan instruksi ke browser agar mendownload file sebagai Excel
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Rekap_Pengajuan_simonKeu.xls");

        // Kirim data ke tampilan khusus Excel
        return view('pengajuan.excel', compact('daftarPengajuan'));
    }

    public function cetak($id)
    {
        $pengajuan = PengajuanLs::findOrFail($id);
        return view('pengajuan.cetak', compact('pengajuan'));
    }
}