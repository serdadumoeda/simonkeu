<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengajuanLs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use App\Models\User;

class PengajuanController extends Controller
{
    /// 1. DAFTAR PENGAJUAN (Mendukung Filter Role Khusus)
    public function index(Request $request)
    {
        // Mulai membuat query untuk mengambil data pengajuan (diurutkan kronologis tanggal pengajuan)
        $query = PengajuanLs::with('user')
            ->orderBy('tgl_pengajuan', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        $user = Auth::user(); // Mengambil data user yang sedang login

        // =========================================================
        // LOGIKA FILTER OTOMATIS BERDASARKAN ROLE (HAK AKSES)
        // =========================================================

        if ($user->role == 'Operator Bidang') {
            // Jika UPTD, filter per user_id agar data UPTD A tidak terlihat oleh UPTD B
            if ($user->bidang === 'UPTD') {
                $query->where('user_id', $user->id);
            } else {
                $query->where('bidang', $user->bidang);
            }

        } elseif ($user->role == 'Verifikator Keuangan') {
            // Verifikator melihat dokumen yang sudah diajukan (bukan Draft)
            $query->where('status', '!=', 'Draft');

        } elseif ($user->role == 'PPK') {
            // PPK HANYA melihat dokumen yang sudah lolos dari Verifikator dan seterusnya
            $query->whereIn('status', [
                'Proses Persetujuan PPK',
                'Penerbitan SPP',
                'SPP Menunggu TTD UPTD',
                'Diajukan ke SAKTI',
                'Belum Terbit SP2D',
                'Dicairkan',
                'Perlu Perbaikan',
                'Selesai'
            ]);

        } elseif ($user->role == 'Operator Pembayaran') {
            // Operator Pembayaran HANYA melihat dokumen yang sudah disetujui PPK
            $query->whereIn('status', [
                'Penerbitan SPP',
                'SPP Menunggu TTD UPTD',
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
        // Filter Tahun Anggaran (Default ke Tahun Berjalan)
        $tahunAktif = $request->get('tahun', date('Y'));
        if ($tahunAktif !== 'semua' && !empty($tahunAktif)) {
            $query->whereYear('tgl_pengajuan', $tahunAktif);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('no_pengajuan', 'like', "%{$search}%")
                  ->orWhere('nama_kegiatan', 'like', "%{$search}%")
                  ->orWhere('no_akun', 'like', "%{$search}%")
                  ->orWhere('jenis_belanja', 'like', "%{$search}%")
                  ->orWhere('no_spm', 'like', "%{$search}%")
                  ->orWhere('no_sp2d', 'like', "%{$search}%")
                  ->orWhere('no_spp', 'like', "%{$search}%")
                  ->orWhere('uraian_pembayaran', 'like', "%{$search}%");
            });
        }

        if ($request->filled('bidang')) {
            $query->where('bidang', $request->bidang);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Ambil daftar tahun unik secara dinamis dari database untuk filter
        $tahunListDb = PengajuanLs::whereNotNull('tgl_pengajuan')
            ->pluck('tgl_pengajuan')
            ->map(function($d) {
                return \Carbon\Carbon::parse($d)->format('Y');
            })
            ->unique()
            ->toArray();

        if (!in_array(date('Y'), $tahunListDb)) {
            $tahunListDb[] = date('Y');
        }
        rsort($tahunListDb);
        $daftarTahun = array_values($tahunListDb);

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

        // Eksekusi query dengan PAGINATION (10 data per halaman) & simpan query string
        $daftarPengajuan = $query->paginate(10)->withQueryString();

        // Kirim data ke tampilan HTML (Blade)
        return view('pengajuan.index', compact('daftarPengajuan', 'daftarBidang', 'daftarTahun', 'tahunAktif'));
    }

    // 2. FORM BUAT PENGAJUAN
    public function create()
    {
        $user = Auth::user();

        if ($user->role != 'Operator Bidang') {
            abort(403, 'Akses Ditolak: Hanya Pemohon (Operator Bidang / UPTD) yang dapat membuat pengajuan.');
        }

        $tglBulanTahun = date('dmY');
        $urutan = 1;
        try {
            $pengajuanTerakhir = PengajuanLs::where('no_pengajuan', 'LIKE', "KU-$tglBulanTahun-%")
                ->orderBy('no_pengajuan', 'desc')
                ->first();

            if ($pengajuanTerakhir && !empty($pengajuanTerakhir->no_pengajuan)) {
                $parts = explode('-', $pengajuanTerakhir->no_pengajuan);
                $lastNum = end($parts);
                if (is_numeric($lastNum)) {
                    $urutan = (int) $lastNum + 1;
                } else {
                    $urutan = (int) substr($pengajuanTerakhir->no_pengajuan, -3) + 1;
                }
            }
        } catch (\Throwable $e) {
            $urutan = 1;
        }
        $noPengajuanBaru = "KU-" . $tglBulanTahun . "-" . str_pad($urutan, 3, '0', STR_PAD_LEFT);

        return view('pengajuan.create', compact('noPengajuanBaru'));
    }

    // 3. SIMPAN PENGAJUAN BARU (Mendukung Tautan Google Drive)
    public function store(Request $request)
    {
        $user = Auth::user();
        $userBidang = (string) ($user->bidang ?? '');

        if ($user->role != 'Operator Bidang') {
            abort(403, 'Akses Ditolak: Hanya Pemohon (Operator Bidang / UPTD) yang dapat menyimpan pengajuan.');
        }

        $request->validate([
            'no_pengajuan' => 'required|string|unique:pengajuan_ls,no_pengajuan',
            'nama_kegiatan' => 'required|string',
            'no_akun' => 'required|string',
            'jenis_belanja' => 'required|string',
            'nilai_bruto' => 'required|numeric',
            'potongan_pajak' => 'nullable|numeric',
            'nilai_neto' => 'required|numeric',
            'link_google_drive' => 'required|url',
            'kategori_pengajuan' => 'required|string|in:GU/UP/TUP,LS Kontrak,LS Non Kontrak,LS banyak penerima,LS Bendahara',
        ]);

        $potonganPajak = $request->filled('potongan_pajak') ? $request->potongan_pajak : 0;
        $nilaiNeto = $request->filled('nilai_neto') ? $request->nilai_neto : max(0, $request->nilai_bruto - $potonganPajak);

        $dataDukungJson = null;
        if ($request->has('data_dukung') && is_array($request->data_dukung)) {
            $dataDukungList = [];
            foreach ($request->data_dukung as $docName => $link) {
                if (!empty($docName)) {
                    $dataDukungList[] = [
                        'nama_dokumen' => $docName,
                        'link_drive' => $link ?? ''
                    ];
                }
            }
            $dataDukungJson = json_encode($dataDukungList);
        }

        $initialStatus = 'Draft';
        if ($request->action != 'draft') {
            $initialStatus = 'Menunggu Verifikasi';
        }

        $pengajuan = PengajuanLs::create([
            'no_pengajuan' => $request->no_pengajuan,
            'tgl_pengajuan' => now(),
            'user_id' => Auth::id(),
            'bidang' => $userBidang,
            'nama_kegiatan' => $request->nama_kegiatan,
            'no_akun' => $request->no_akun,
            'jenis_belanja' => $request->jenis_belanja,
            'nilai_bruto' => $request->nilai_bruto,
            'potongan_pajak' => $potonganPajak,
            'nilai_neto' => $nilaiNeto,
            'uraian_pembayaran' => $request->uraian_pembayaran,
            'link_google_drive' => $request->link_google_drive,
            'data_dukung_json' => $dataDukungJson,
            'status' => $initialStatus,
            'kategori_pengajuan' => $request->kategori_pengajuan,
        ]);

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

            // Notifikasi Ringkasan ke Kepala Balai
            $kepalaBalais = User::where('role', 'Kepala Balai')->get();
            foreach ($kepalaBalais as $kb) {
                try {
                    Notification::create([
                        'user_id' => $kb->id,
                        'title' => 'Pengajuan Baru Masuk',
                        'message' => 'Pengajuan baru ' . $pengajuan->no_pengajuan . ' dari ' . $pengajuan->bidang . ' (' . $pengajuan->nama_kegiatan . ') telah dibuat.',
                        'is_read' => false,
                    ]);
                } catch (\Throwable $e) {}
            }
        }

        return redirect()->route('pengajuan.index')->with('success', 'Pengajuan berhasil diproses.');
    }

    // 3.B PEREKAMAN DATA PENGAJUAN LAMPAU (SPM & SP2D CAIR LALU)
    public function createLampau()
    {
        $user = Auth::user();
        if ($user->role != 'Admin Keuangan' && $user->role != 'Operator Pembayaran') {
            abort(403, 'Akses Ditolak: Fitur Perekaman Data Lampau hanya dapat diakses oleh Admin Keuangan dan Operator Pembayaran.');
        }

        $daftarBidang = User::whereNotNull('bidang')
            ->where('bidang', '!=', 'None')
            ->where('bidang', '!=', 'Keuangan')
            ->pluck('bidang')
            ->unique()
            ->values()
            ->toArray();

        $defaultBidang = ['Purworejo', 'Wonogiri', 'Kebumen', 'Karanganyar', 'Technopark', 'Boyolali', 'Sukoharjo', 'BLKPP', 'Sragen', 'Kulon Progo', 'Gunung Kidul', 'Ponorogo', 'Madiun', 'Pacitan'];
        $daftarBidang = array_unique(array_merge($daftarBidang, $defaultBidang));
        sort($daftarBidang);

        // Generate No Pengajuan candidate (menggunakan format dmY 8-digit)
        $tglBulanTahun = date('dmY');
        try {
            $pengajuanTerakhir = PengajuanLs::where('no_pengajuan', 'LIKE', "KU-$tglBulanTahun-%")
                ->orderBy('no_pengajuan', 'desc')
                ->first();
            $urutan = 1;
            if ($pengajuanTerakhir && $pengajuanTerakhir->no_pengajuan) {
                $parts = explode('-', $pengajuanTerakhir->no_pengajuan);
                $lastNum = end($parts);
                if (is_numeric($lastNum)) {
                    $urutan = (int) $lastNum + 1;
                } else {
                    $urutan = (int) substr($pengajuanTerakhir->no_pengajuan, -3) + 1;
                }
            }
        } catch (\Throwable $e) {
            $urutan = 1;
        }
        $noPengajuanBaru = "KU-" . $tglBulanTahun . "-" . str_pad($urutan, 3, '0', STR_PAD_LEFT);

        return view('pengajuan.create_lampau', compact('daftarBidang', 'noPengajuanBaru'));
    }

    public function storeLampau(Request $request)
    {
        $user = Auth::user();
        if ($user->role != 'Admin Keuangan' && $user->role != 'Operator Pembayaran') {
            abort(403, 'Akses Ditolak: Perekaman Data Lampau hanya dapat diakses oleh Admin Keuangan dan Operator Pembayaran.');
        }

        $request->validate([
            'no_pengajuan' => 'required|string|unique:pengajuan_ls,no_pengajuan',
            'tgl_pengajuan' => 'required|date',
            'bidang' => 'required|string',
            'kategori_pengajuan' => 'required|string|in:GU/UP/TUP,LS Kontrak,LS Non Kontrak,LS banyak penerima,LS Bendahara',
            'nama_kegiatan' => 'required|string',
            'no_akun' => 'required|string',
            'jenis_belanja' => 'required|string',
            'nilai_bruto' => 'required|numeric',
            'potongan_pajak' => 'nullable|numeric',
            'nilai_neto' => 'required|numeric',
            'uraian_pembayaran' => 'nullable|string',
            'no_spm' => 'required|string',
            'tgl_spm' => 'required|date',
            'no_sp2d' => 'required|string',
            'tgl_cair' => 'required|date',
            'status' => 'required|string|in:Dicairkan,Selesai',
            'link_google_drive' => 'required|url',
            'bukti_penyerahan' => 'nullable|url',
            'spj_status' => 'nullable|string',
        ], [
            'no_pengajuan.required' => 'Nomor Pengajuan wajib diisi.',
            'no_pengajuan.unique' => 'Nomor Pengajuan sudah pernah digunakan, gunakan nomor lain.',
            'tgl_pengajuan.required' => 'Tanggal Pengajuan wajib diisi.',
            'bidang.required' => 'Bidang / UPTD wajib dipilih.',
            'kategori_pengajuan.required' => 'Kategori Pengajuan wajib dipilih.',
            'nama_kegiatan.required' => 'Nama Kegiatan wajib diisi.',
            'no_akun.required' => 'Nomor Akun wajib diisi.',
            'jenis_belanja.required' => 'Jenis Belanja wajib dipilih.',
            'nilai_bruto.required' => 'Nilai Bruto wajib diisi.',
            'nilai_neto.required' => 'Nilai Neto wajib diisi.',
            'no_spm.required' => 'Nomor SPM wajib diisi.',
            'tgl_spm.required' => 'Tanggal SPM wajib diisi.',
            'no_sp2d.required' => 'Nomor SP2D wajib diisi.',
            'tgl_cair.required' => 'Tanggal Cair SP2D wajib diisi.',
            'link_google_drive.required' => 'Link Google Drive SPJ wajib diisi.',
            'link_google_drive.url' => 'Format Link Google Drive tidak valid (harus diawali http/https).',
            'bukti_penyerahan.url' => 'Format Link Bukti Penyerahan tidak valid.',
        ]);

        $potonganPajak = $request->filled('potongan_pajak') ? $request->potongan_pajak : 0;
        $nilaiNeto = $request->filled('nilai_neto') ? $request->nilai_neto : max(0, $request->nilai_bruto - $potonganPajak);

        $dataDukungJson = null;
        if ($request->has('data_dukung') && is_array($request->data_dukung)) {
            $dataDukungList = [];
            foreach ($request->data_dukung as $docName => $link) {
                if (!empty($docName)) {
                    $dataDukungList[] = [
                        'nama_dokumen' => $docName,
                        'link_drive' => $link ?? ''
                    ];
                }
            }
            $dataDukungJson = json_encode($dataDukungList);
        }

        $spjDeadline = null;
        if ($request->tgl_cair) {
            $spjDeadline = \Carbon\Carbon::parse($request->tgl_cair)->addDays(30)->format('Y-m-d');
        }

        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE pengajuan_ls DROP CONSTRAINT IF EXISTS pengajuan_ls_status_check");
        } catch (\Throwable $e) {}

        try {
            $tglPengajuanCarbon = \Carbon\Carbon::parse($request->tgl_pengajuan)->setTimeFrom(now());

            $pengajuan = PengajuanLs::create([
                'no_pengajuan' => $request->no_pengajuan,
                'tgl_pengajuan' => $request->tgl_pengajuan,
                'created_at' => $tglPengajuanCarbon,
                'updated_at' => $tglPengajuanCarbon,
                'user_id' => Auth::id(),
                'operator_pembayaran_id' => Auth::id(),
                'bendahara_id' => Auth::id(),
                'bidang' => $request->bidang,
                'nama_kegiatan' => $request->nama_kegiatan,
                'no_akun' => $request->no_akun,
                'jenis_belanja' => $request->jenis_belanja,
                'nilai_bruto' => $request->nilai_bruto,
                'potongan_pajak' => $potonganPajak,
                'nilai_neto' => $nilaiNeto,
                'uraian_pembayaran' => $request->uraian_pembayaran ?? '',
                'link_google_drive' => $request->link_google_drive,
                'bukti_penyerahan' => $request->bukti_penyerahan,
                'no_spm' => $request->no_spm,
                'tgl_spm' => $request->tgl_spm,
                'no_sp2d' => $request->no_sp2d,
                'tgl_cair' => $request->tgl_cair,
                'status' => $request->status,
                'kategori_pengajuan' => $request->kategori_pengajuan,
                'data_dukung_json' => $dataDukungJson,
                'spj_status' => $request->spj_status ?? ($request->status == 'Selesai' ? 'SPJ Lengkap' : 'Belum Upload'),
                'spj_deadline' => $spjDeadline,
            ]);

            return redirect()->route('pengajuan.index')->with('success', 'Data pengajuan lampau ' . $pengajuan->no_pengajuan . ' berhasil direkam dengan status ' . $pengajuan->status . '.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('storeLampau error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token']),
            ]);
            return redirect()->back()->withInput()->withErrors([
                'database' => 'Gagal menyimpan data: ' . $e->getMessage()
            ]);
        }
    }

    // 4. DETAIL PENGAJUAN (Untuk Verifikasi/Approval)
    public function show($id)
    {
        $pengajuan = PengajuanLs::findOrFail($id);
        $user = Auth::user();

        // Admin Keuangan dan Kepala Balai selalu bisa melihat semua pengajuan
        if ($user->role == 'Admin Keuangan' || $user->role == 'Kepala Balai') {
            return view('pengajuan.show', compact('pengajuan'));
        }

        // Pemilik dokumen selalu bisa melihat dokumen miliknya sendiri
        if ($pengajuan->user_id == $user->id) {
            return view('pengajuan.show', compact('pengajuan'));
        }

        // Cek otorisasi berdasarkan role
        if ($user->role == 'Operator Bidang') {
            $isUptd = $user->bidang === 'UPTD' || str_contains(strtoupper($user->bidang), 'UPTD');
            if ($isUptd) {
                // UPTD hanya bisa melihat pengajuan miliknya sendiri (sudah dicek user_id di atas)
                abort(403, 'Akses Ditolak: Anda hanya bisa melihat pengajuan milik Anda sendiri.');
            }
            // Non-UPTD Operator Bidang hanya melihat pengajuan dari bidangnya sendiri
            if ($pengajuan->bidang != $user->bidang) {
                abort(403, 'Akses Ditolak: Anda tidak berhak melihat pengajuan dari bidang lain.');
            }
        }
        // Verifikator Keuangan, PPK, Operator Pembayaran, Bendahara — bisa melihat pengajuan sesuai tahapan workflow
        // (panel aksi di view blade sudah mengecek status yang relevan untuk ditampilkan)

        return view('pengajuan.show', compact('pengajuan'));
    }

    // 4.4 PROSES AJUKAN ULANG BERKAS REVISI (Oleh Operator / Pemohon)
    public function resubmit(Request $request, $id)
    {
        $pengajuan = PengajuanLs::findOrFail($id);
        $user = Auth::user();

        if ($pengajuan->user_id != $user->id) {
            abort(403, 'Akses Ditolak: Hanya pemohon dokumen yang dapat mengajukan ulang berkas.');
        }

        if ($pengajuan->status != 'Perlu Perbaikan') {
            return redirect()->back()->with('error', 'Berkas tidak dalam status Perlu Perbaikan.');
        }

        if ($request->filled('link_google_drive')) {
            $pengajuan->link_google_drive = $request->link_google_drive;
        }

        $pengajuan->status = 'Menunggu Verifikasi';
        $pengajuan->catatan_koreksi = null;
        $pengajuan->save();

        $verifikators = User::where('role', 'Verifikator Keuangan')->get();
        foreach ($verifikators as $v) {
            Notification::create([
                'user_id' => $v->id,
                'title' => 'Pengajuan Revisi Siap Diverifikasi',
                'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' telah diperbaiki oleh pemohon dan siap diverifikasi ulang oleh Keuangan.',
                'is_read' => false,
            ]);
        }

        return redirect()->route('pengajuan.show', $id)->with('success', 'Berkas pengajuan berhasil diperbaiki dan diajukan ulang.');
    }

    // 4.5 PROSES VERIFIKASI INTERNAL (ALIASED KE VERIFIKASI KEUANGAN)
    public function verifikasiPicUptd(Request $request, $id)
    {
        return $this->verifikasi($request, $id);
    }

    // 5. PROSES VERIFIKASI (Oleh Verifikator Keuangan)
    public function verifikasi(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role != 'Verifikator Keuangan' && $user->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak: Hanya Verifikator Keuangan yang dapat melakukan verifikasi.');
        }

        try {
            return DB::transaction(function () use ($request, $id, $user) {
                try {
                    DB::statement("ALTER TABLE pengajuan_ls DROP CONSTRAINT IF EXISTS pengajuan_ls_status_check");
                } catch (\Throwable $e) {}

                $pengajuan = PengajuanLs::findOrFail($id);
                $pengajuan->verifikator_id = $user->id;

                $catatan = $request->catatan_koreksi ?? $request->catatan;
                if (in_array($request->action, ['perbaiki', 'tolak']) && empty($catatan)) {
                    return back()->with('error', 'Catatan / Alasan revisi wajib diisi saat meminta perbaikan atau menolak pengajuan.');
                }

                if ($request->action == 'setuju') {
                    $pengajuan->status = 'Proses Persetujuan PPK';
                    $pengajuan->addHistoriCatatan('Verifikasi Keuangan', 'Disetujui', $catatan, $user);
                    
                    $ppks = User::where('role', 'PPK')->get();
                    foreach ($ppks as $ppk) {
                        try {
                            Notification::create([
                                'user_id' => $ppk->id,
                                'title' => 'Persetujuan Dokumen Baru',
                                'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' telah diverifikasi Keuangan dan menunggu persetujuan Anda.',
                                'is_read' => false,
                            ]);
                        } catch (\Throwable $e) {}
                    }

                    // Notifikasi ke Pemohon
                    try {
                        Notification::create([
                            'user_id' => $pengajuan->user_id,
                            'title' => 'Verifikasi Keuangan Disetujui',
                            'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' Anda telah lolos verifikasi Keuangan dan diteruskan ke PPK.',
                            'is_read' => false,
                        ]);
                    } catch (\Throwable $e) {}
                } elseif ($request->action == 'perbaiki') {
                    $pengajuan->status = 'Perlu Perbaikan';
                    $pengajuan->catatan_koreksi = $catatan;
                    $pengajuan->addHistoriCatatan('Verifikasi Keuangan', 'Perlu Perbaikan', $catatan, $user);
                    
                    try {
                        Notification::create([
                            'user_id' => $pengajuan->user_id,
                            'title' => 'Revisi Pengajuan Berkas',
                            'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' perlu diperbaiki: ' . ($catatan ?? ''),
                            'is_read' => false,
                        ]);
                    } catch (\Throwable $e) {}
                } else {
                    $pengajuan->status = 'Draft';
                    $pengajuan->catatan_koreksi = $catatan;
                    $pengajuan->addHistoriCatatan('Verifikasi Keuangan', 'Ditolak Total (Draft)', $catatan, $user);

                    try {
                        Notification::create([
                            'user_id' => $pengajuan->user_id,
                            'title' => 'Pengajuan Berkas Ditolak',
                            'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' ditolak total dan dikembalikan ke Draft.',
                            'is_read' => false,
                        ]);
                    } catch (\Throwable $e) {}
                }

                $pengajuan->save();
                return redirect()->route('pengajuan.index')->with('success', 'Status pengajuan berhasil diperbarui oleh Verifikator.');
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses verifikasi: ' . $e->getMessage());
        }
    }

    // 6. PROSES APPROVAL PPK
    public function ppkApproval(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role != 'PPK' && $user->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak: Hanya PPK yang dapat memberikan persetujuan.');
        }

        try {
            return DB::transaction(function () use ($request, $id, $user) {
                try {
                    DB::statement("ALTER TABLE pengajuan_ls DROP CONSTRAINT IF EXISTS pengajuan_ls_status_check");
                } catch (\Throwable $e) {}

                $pengajuan = PengajuanLs::findOrFail($id);
                $pengajuan->ppk_id = $user->id;

                $catatan = $request->catatan_koreksi ?? $request->catatan;

                if ($request->action == 'setuju') {
                    // Cek apakah pemohon dari UPTD
                    $pemohon = User::find($pengajuan->user_id);
                    $isUptd = $pemohon && ($pemohon->bidang === 'UPTD' || str_contains(strtoupper($pemohon->bidang), 'UPTD'));

                    $pengajuan->addHistoriCatatan('Persetujuan PPK', 'Disetujui', $catatan, $user);

                    if ($isUptd) {
                        // UPTD: masuk ke alur SPP multi-tahap
                        $pengajuan->status = 'Penerbitan SPP';
                        
                        $operators = User::where('role', 'Operator Pembayaran')->get();
                        foreach ($operators as $op) {
                            try {
                                Notification::create([
                                    'user_id' => $op->id,
                                    'title' => 'Penerbitan SPP Baru (UPTD)',
                                    'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' dari UPTD telah disetujui PPK, silakan terbitkan SPP dan unggah link dokumen SPP.',
                                    'is_read' => false,
                                ]);
                            } catch (\Throwable $e) {}
                        }

                        // Notifikasi ke Pemohon
                        try {
                            Notification::create([
                                'user_id' => $pengajuan->user_id,
                                'title' => 'Disetujui oleh PPK',
                                'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' Anda telah disetujui PPK dan akan diproses penerbitan SPP oleh Operator Pembayaran.',
                                'is_read' => false,
                            ]);
                        } catch (\Throwable $e) {}
                    } else {
                        // Non-UPTD: langsung ke SAKTI (skip SPP)
                        $pengajuan->status = 'Diajukan ke SAKTI';
                        
                        $operators = User::where('role', 'Operator Pembayaran')->get();
                        foreach ($operators as $op) {
                            try {
                                Notification::create([
                                    'user_id' => $op->id,
                                    'title' => 'Proses SPM SAKTI Baru',
                                    'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' telah disetujui PPK, silakan proses SPM di Aplikasi SAKTI (PPSPM).',
                                    'is_read' => false,
                                ]);
                            } catch (\Throwable $e) {}
                        }

                        // Notifikasi ke Pemohon
                        try {
                            Notification::create([
                                'user_id' => $pengajuan->user_id,
                                'title' => 'Disetujui oleh PPK',
                                'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' Anda telah disetujui PPK dan akan diproses SPM di SAKTI.',
                                'is_read' => false,
                            ]);
                        } catch (\Throwable $e) {}
                    }
                } else {
                    if (empty($catatan)) {
                        return back()->with('error', 'Catatan / Alasan revisi wajib diisi saat meminta perbaikan.');
                    }
                    $pengajuan->status = 'Perlu Perbaikan';
                    $pengajuan->catatan_koreksi = $catatan;
                    $pengajuan->addHistoriCatatan('Persetujuan PPK', 'Perlu Perbaikan', $catatan, $user);
                    
                    try {
                        Notification::create([
                            'user_id' => $pengajuan->user_id,
                            'title' => 'Revisi Berkas oleh PPK',
                            'message' => 'Berkas ' . $pengajuan->no_pengajuan . ' perlu diperbaiki berdasarkan keputusan PPK: ' . ($catatan ?? ''),
                            'is_read' => false,
                        ]);
                    } catch (\Throwable $e) {}
                }

                $pengajuan->save();
                return redirect()->route('pengajuan.index')->with('success', 'Keputusan PPK berhasil disimpan.');
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses persetujuan PPK: ' . $e->getMessage());
        }
    }

    // 6.B PROSES PENERBITAN SPP (Operator Pembayaran / LINA) — Upload Link SPP
    public function penerbitanSpp(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role != 'Operator Pembayaran' && $user->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak: Hanya Operator Pembayaran yang dapat menerbitkan SPP.');
        }

        try {
            return DB::transaction(function () use ($request, $id, $user) {
                try {
                    DB::statement("ALTER TABLE pengajuan_ls DROP CONSTRAINT IF EXISTS pengajuan_ls_status_check");
                } catch (\Throwable $e) {}

                $pengajuan = PengajuanLs::findOrFail($id);

                if ($pengajuan->status != 'Penerbitan SPP') {
                    return back()->with('error', 'Berkas tidak dalam status Penerbitan SPP.');
                }

                $request->validate([
                    'no_spp' => 'required|string',
                    'spp_link' => 'required|url',
                ]);

                $pengajuan->no_spp = $request->no_spp;
                $pengajuan->tgl_spp = now();
                $pengajuan->spp_operator_id = $user->id;
                $pengajuan->spp_link = $request->spp_link;
                $pengajuan->status = 'SPP Menunggu TTD UPTD';
                $pengajuan->addHistoriCatatan('Penerbitan SPP', 'SPP Diterbitkan', $request->catatan ?? ('No. SPP: ' . $request->no_spp), $user);
                $pengajuan->save();

                // Notifikasi ke Pemohon UPTD untuk download, TTD, dan upload kembali
                try {
                    Notification::create([
                        'user_id' => $pengajuan->user_id,
                        'title' => '📄 SPP Diterbitkan — Silakan Tanda Tangani',
                        'message' => 'Dokumen SPP nomor ' . $pengajuan->no_spp . ' untuk berkas ' . $pengajuan->no_pengajuan . ' telah diterbitkan. Silakan download, tanda tangani (tanpa cap basah), dan unggah kembali SPP bertandatangan.',
                        'is_read' => false,
                    ]);
                } catch (\Throwable $e) {}

                return redirect()->route('pengajuan.show', $id)->with('success', 'SPP berhasil diterbitkan. Menunggu tanda tangan dari UPTD.');
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menerbitkan SPP: ' . $e->getMessage());
        }
    }

    // 6.C UPLOAD SPP BERTANDATANGAN OLEH UPTD
    public function uploadSppUptd(Request $request, $id)
    {
        $user = Auth::user();
        $pengajuan = PengajuanLs::findOrFail($id);

        // Hanya pemohon asli (UPTD) atau Admin yang boleh upload SPP bertandatangan
        if ($pengajuan->user_id != $user->id && $user->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak: Hanya pemohon UPTD yang dapat mengunggah SPP bertandatangan.');
        }

        if ($pengajuan->status != 'SPP Menunggu TTD UPTD') {
            return back()->with('error', 'Berkas tidak dalam status menunggu tanda tangan SPP.');
        }

        try {
            return DB::transaction(function () use ($request, $id, $pengajuan, $user) {
                try {
                    DB::statement("ALTER TABLE pengajuan_ls DROP CONSTRAINT IF EXISTS pengajuan_ls_status_check");
                } catch (\Throwable $e) {}

                $request->validate([
                    'spp_signed_link' => 'required|url',
                ]);

                $pengajuan->spp_signed_link = $request->spp_signed_link;
                $pengajuan->spp_signed_at = now();
                $pengajuan->addHistoriCatatan('Penandatanganan SPP (UPTD)', 'Dokumen Diunggah', $request->catatan ?? 'SPP bertandatangan diunggah oleh UPTD', $user);
                $pengajuan->save();

                // Notifikasi ke Operator Pembayaran (LINA) untuk validasi
                $operators = User::where('role', 'Operator Pembayaran')->get();
                foreach ($operators as $op) {
                    try {
                        Notification::create([
                            'user_id' => $op->id,
                            'title' => '✅ SPP Bertandatangan Diunggah UPTD',
                            'message' => 'SPP bertandatangan untuk berkas ' . $pengajuan->no_pengajuan . ' telah diunggah oleh UPTD. Silakan validasi dan lanjutkan ke proses SAKTI (SPM).',
                            'is_read' => false,
                        ]);
                    } catch (\Throwable $e) {}
                }

                return redirect()->route('pengajuan.show', $id)->with('success', 'SPP bertandatangan berhasil diunggah. Menunggu validasi dari Operator Pembayaran.');
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengunggah SPP bertandatangan: ' . $e->getMessage());
        }
    }

    // 6.D VALIDASI SPP BERTANDATANGAN OLEH LINA DAN LANJUTKAN KE SAKTI
    public function validasiSppUptd(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role != 'Operator Pembayaran' && $user->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak: Hanya Operator Pembayaran yang dapat memvalidasi SPP.');
        }

        try {
            return DB::transaction(function () use ($request, $id, $user) {
                try {
                    DB::statement("ALTER TABLE pengajuan_ls DROP CONSTRAINT IF EXISTS pengajuan_ls_status_check");
                } catch (\Throwable $e) {}

                $pengajuan = PengajuanLs::findOrFail($id);

                if ($pengajuan->status != 'SPP Menunggu TTD UPTD' || !$pengajuan->spp_signed_link) {
                    return back()->with('error', 'SPP bertandatangan belum diunggah oleh UPTD.');
                }

                $catatan = $request->catatan;

                if ($request->action == 'perbaiki') {
                    if (empty($catatan)) {
                        return back()->with('error', 'Catatan / Alasan penolakan SPP bertandatangan wajib diisi.');
                    }
                    $pengajuan->spp_signed_link = null; // minta upload ulang
                    $pengajuan->addHistoriCatatan('Validasi SPP UPTD', 'Perlu Perbaikan', $catatan, $user);
                    $pengajuan->save();

                    try {
                        Notification::create([
                            'user_id' => $pengajuan->user_id,
                            'title' => '⚠️ SPP Bertandatangan Perlu Diperbaiki',
                            'message' => 'SPP bertandatangan untuk berkas ' . $pengajuan->no_pengajuan . ' ditolak oleh Operator Pembayaran. Catatan: "' . $catatan . '". Silakan unggah kembali dokumen SPP yang benar.',
                            'is_read' => false,
                        ]);
                    } catch (\Throwable $e) {}

                    return redirect()->route('pengajuan.show', $id)->with('success', 'SPP bertandatangan dikembalikan ke UPTD untuk diperbaiki.');
                } else {
                    $pengajuan->status = 'Diajukan ke SAKTI';
                    $pengajuan->addHistoriCatatan('Validasi SPP UPTD', 'Disetujui / Valid', $catatan, $user);
                    $pengajuan->save();

                    // Notifikasi ke Operator Pembayaran (PPSPM) untuk proses SPM
                    $operators = User::where('role', 'Operator Pembayaran')->get();
                    foreach ($operators as $op) {
                        try {
                            Notification::create([
                                'user_id' => $op->id,
                                'title' => 'SPP Valid — Proses SPM SAKTI',
                                'message' => 'SPP ' . $pengajuan->no_spp . ' untuk berkas ' . $pengajuan->no_pengajuan . ' telah divalidasi. Silakan proses SPM di Aplikasi SAKTI (PPSPM).',
                                'is_read' => false,
                            ]);
                        } catch (\Throwable $e) {}
                    }

                    // Notifikasi ke Pemohon UPTD
                    try {
                        Notification::create([
                            'user_id' => $pengajuan->user_id,
                            'title' => 'SPP Bertandatangan Divalidasi',
                            'message' => 'SPP bertandatangan untuk berkas ' . $pengajuan->no_pengajuan . ' telah divalidasi. Berkas akan dilanjutkan ke proses SAKTI (SPM).',
                            'is_read' => false,
                        ]);
                    } catch (\Throwable $e) {}

                    return redirect()->route('pengajuan.show', $id)->with('success', 'SPP bertandatangan divalidasi. Berkas dilanjutkan ke proses SAKTI (SPM/PPSPM).');
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memvalidasi SPP: ' . $e->getMessage());
        }
    }

    // 7. INPUT REALISASI & PENCAIRAN (Operator Pembayaran & Bendahara)
    public function realisasi(Request $request, $id)
    {
        $pengajuan = PengajuanLs::findOrFail($id);
        $user = Auth::user();

        try {
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE pengajuan_ls DROP CONSTRAINT IF EXISTS pengajuan_ls_status_check");
            } catch (\Throwable $e) {}

            $catatan = $request->catatan;

            if ($user->role == 'Operator Pembayaran' || $user->role == 'Admin Keuangan') {
                if ($request->has('no_spm')) {
                    $request->validate([
                        'no_spm' => 'required',
                    ]);
                    $pengajuan->no_spm = $request->no_spm;
                    $pengajuan->tgl_spm = date('Y-m-d');
                    $pengajuan->operator_pembayaran_id = $user->id;
                    $pengajuan->status = 'Belum Terbit SP2D';
                    $pengajuan->addHistoriCatatan('Proses SAKTI / SPM (PPSPM)', 'SPM Diterbitkan', $catatan ?? ('No. SPM: ' . $request->no_spm), $user);
                    
                    $bendaharas = User::where('role', 'Bendahara')->get();
                    foreach ($bendaharas as $b) {
                        try {
                            Notification::create([
                                'user_id' => $b->id,
                                'title' => 'Pencairan SP2D Baru',
                                'message' => 'Nomor SPM untuk ' . $pengajuan->no_pengajuan . ' telah terbit, mohon konfirmasi pencairan jika SP2D terbit.',
                                'is_read' => false,
                            ]);
                        } catch (\Throwable $e) {}
                    }

                    // Notifikasi ke Pemohon
                    try {
                        Notification::create([
                            'user_id' => $pengajuan->user_id,
                            'title' => 'Nomor SPM Diterbitkan',
                            'message' => 'Nomor SPM ' . $pengajuan->no_spm . ' untuk berkas ' . $pengajuan->no_pengajuan . ' Anda telah diterbitkan oleh SAKTI.',
                            'is_read' => false,
                        ]);
                    } catch (\Throwable $e) {}
                }
            }
            
            if ($user->role == 'Bendahara' || $user->role == 'Admin Keuangan') {
                if ($request->has('bukti_penyerahan')) {
                    $request->validate(['bukti_penyerahan' => 'required|url']);
                    $pengajuan->bukti_penyerahan = $request->bukti_penyerahan;
                    $pengajuan->status = 'Selesai';

                    // Otomatis set batas waktu upload SPJ Pemohon (30 hari)
                    if ($pengajuan->tgl_cair) {
                        $pengajuan->spj_deadline = \Carbon\Carbon::parse($pengajuan->tgl_cair)->addDays(30)->format('Y-m-d');
                    } else {
                        $pengajuan->spj_deadline = now()->addDays(30)->format('Y-m-d');
                    }

                    // Otomatis set batas waktu 2 hari untuk Verifikator Keuangan upload SPM/SP2D
                    $pengajuan->verifikator_spm_deadline = now()->addDays(2);
                    $pengajuan->addHistoriCatatan('Penyerahan Uang (Bendahara)', 'Uang Diserahkan', $catatan ?? 'Bukti penyerahan uang diunggah', $user);
                    
                    // 1. Notifikasi ke Pemohon
                    try {
                        Notification::create([
                            'user_id' => $pengajuan->user_id,
                            'title' => 'Uang Diserahkan & Proses Selesai',
                            'message' => 'Bendahara telah menyerahkan uang untuk pengajuan ' . $pengajuan->no_pengajuan . '. Silakan periksa bukti penyerahan Google Drive. Batas waktu upload SPJ: ' . $pengajuan->spj_deadline,
                            'is_read' => false,
                        ]);
                    } catch (\Throwable $e) {}

                    // 2. Notifikasi ke Verifikator Keuangan (Batas 2 Hari)
                    $verifikators = User::where('role', 'Verifikator Keuangan')->get();
                    foreach ($verifikators as $v) {
                        try {
                            Notification::create([
                                'user_id' => $v->id,
                                'title' => '⏱️ Tenggat 2 Hari Upload SPM/SP2D/SPP',
                                'message' => 'Bendahara telah menyerahkan uang untuk pengajuan ' . $pengajuan->no_pengajuan . '. Mohon unggah dokumen SPM/SP2D/SPP dalam jangka waktu 2 hari (Batas: ' . \Carbon\Carbon::parse($pengajuan->verifikator_spm_deadline)->format('d/m/Y H:i') . ').',
                                'is_read' => false,
                            ]);
                        } catch (\Throwable $e) {}
                    }
                } elseif ($request->has('no_sp2d')) {
                    $request->validate([
                        'no_sp2d' => 'required',
                        'tgl_cair' => 'required',
                        'spj_sp2d_link' => 'required|url',
                    ]);
                    $pengajuan->no_sp2d = $request->no_sp2d;
                    $pengajuan->tgl_cair = $request->tgl_cair;
                    $pengajuan->spj_sp2d_link = $request->spj_sp2d_link;
                    $pengajuan->bendahara_id = $user->id;
                    $pengajuan->status = 'Dicairkan';
                    $pengajuan->addHistoriCatatan('Pencairan SP2D (Bendahara)', 'SP2D Diterbitkan', $catatan ?? ('No. SP2D: ' . $request->no_sp2d), $user);
                    
                    try {
                        Notification::create([
                            'user_id' => $pengajuan->user_id,
                            'title' => 'Dana Berhasil Cair',
                            'message' => 'Selamat! Dana pengajuan berkas ' . $pengajuan->no_pengajuan . ' telah dicairkan oleh Bendahara. Menunggu proses penyerahan uang.',
                            'is_read' => false,
                        ]);
                    } catch (\Throwable $e) {}
                }
            }

            $pengajuan->save();
            return redirect()->route('pengajuan.index')->with('success', 'Data realisasi berhasil diperbarui.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses realisasi: ' . $e->getMessage());
        }
    }

    // FITUR EKSPOR KE EXCEL
    public function exportExcel(Request $request)
    {
        $query = PengajuanLs::orderBy('tgl_pengajuan', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
        $user = Auth::user();

        // =========================================================
        // FILTER HAK AKSES: Hanya export data sesuai wewenang role
        // =========================================================
        if ($user->role == 'Operator Bidang') {
            if ($user->bidang === 'UPTD' || str_contains(strtoupper($user->bidang), 'UPTD')) {
                $query->where('user_id', $user->id);
            } else {
                $query->where('bidang', $user->bidang);
            }
        }
        // Admin Keuangan & Kepala Balai: tanpa filter (export semua)
        // Role sentral (Verifikator, PPK, OP, Bendahara): tidak dikunci per tahapan

        $tahunAktif = $request->get('tahun', date('Y'));
        if ($tahunAktif !== 'semua' && !empty($tahunAktif)) {
            $query->whereYear('tgl_pengajuan', $tahunAktif);
        }

        if ($request->filled('bidang')) {
            $query->where('bidang', $request->bidang);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $daftarPengajuan = $query->get();

        // Mengirimkan instruksi ke browser agar mendownload file sebagai Excel
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Rekap_Pengajuan_simonKeu.xls");

        // Kirim data ke tampilan khusus Excel
        return view('pengajuan.excel', compact('daftarPengajuan'));
    }

    public function cetak($id)
    {
        $pengajuan = PengajuanLs::findOrFail($id);
        $user = Auth::user();

        // Admin Keuangan dan Kepala Balai bisa cetak semua
        if ($user->role == 'Admin Keuangan' || $user->role == 'Kepala Balai') {
            return view('pengajuan.cetak', compact('pengajuan'));
        }

        // Pemilik dokumen selalu bisa cetak dokumennya
        if ($pengajuan->user_id == $user->id) {
            return view('pengajuan.cetak', compact('pengajuan'));
        }

        // Operator Bidang: hanya cetak dokumen bidangnya
        if ($user->role == 'Operator Bidang') {
            $isUptd = $user->bidang === 'UPTD' || str_contains(strtoupper($user->bidang), 'UPTD');
            if ($isUptd) {
                abort(403, 'Akses Ditolak: Anda hanya bisa mencetak pengajuan milik Anda sendiri.');
            }
            if ($pengajuan->bidang != $user->bidang) {
                abort(403, 'Akses Ditolak: Anda tidak berhak mencetak pengajuan dari bidang lain.');
            }
        }
        // Role sentral: tidak dikunci

        return view('pengajuan.cetak', compact('pengajuan'));
    }

    public function cetakBukti($id)
    {
        $pengajuan = PengajuanLs::with(['user', 'bendahara'])->findOrFail($id);
        return view('pengajuan.cetak_bukti', compact('pengajuan'));
    }

    // =========================================================
    // POIN 1: PENATAUSAHAAN SPJ (3 Status Baru)
    // =========================================================

    // 8. UPLOAD SPJ OLEH VERIFIKATOR (SP2D, SPM, SPP)
    public function uploadSpjVerifikator(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role != 'Verifikator Keuangan' && $user->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak: Hanya Verifikator Keuangan yang dapat mengupload SPJ.');
        }

        $pengajuan = PengajuanLs::findOrFail($id);

        if (!in_array($pengajuan->status, ['Selesai'])) {
            return back()->with('error', 'Upload SPJ hanya dapat dilakukan setelah status Selesai.');
        }

        try {
            $request->validate([
                'spj_sp2d_link' => 'required|url',
                'spj_spm_link' => 'required|url',
                'spj_spp_link' => 'required|url',
            ]);

            $pengajuan->spj_sp2d_link = $request->spj_sp2d_link;
            $pengajuan->spj_spm_link = $request->spj_spm_link;
            $pengajuan->spj_spp_link = $request->spj_spp_link;

            $pengajuan->spj_status = 'Menunggu Upload Pemohon';
            // Set batas waktu 5 hari tepat sejak Verifikator Keuangan mengunggah SPM/SP2D
            $pengajuan->spj_deadline = now()->addDays(5)->format('Y-m-d H:i:s');
            $pengajuan->addHistoriCatatan('Upload SPM/SP2D (Verifikator)', 'Dokumen Diunggah', $request->catatan ?? 'Dokumen pendukung SPJ diunggah', $user);
            $pengajuan->save();

            // Notifikasi ke pemohon (Batas 5 hari)
            try {
                Notification::create([
                    'user_id' => $pengajuan->user_id,
                    'title' => '⏱️ Tenggat 5 Hari Upload SPJ Lengkap',
                    'message' => 'Verifikator Keuangan telah mengunggah dokumen SPM/SP2D/SPP untuk berkas ' . $pengajuan->no_pengajuan . '. Silakan unggah dokumen SPJ Lengkap Anda dalam jangka waktu 5 hari (Batas: ' . \Carbon\Carbon::parse($pengajuan->spj_deadline)->format('d/m/Y H:i') . ').',
                    'is_read' => false,
                ]);
            } catch (\Throwable $e) {}

            return redirect()->route('pengajuan.show', $id)->with('success', 'Dokumen SP2D/SPM/SPP berhasil diupload. Batas waktu upload SPJ lengkap Pemohon ditetapkan 5 hari.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengupload SPJ: ' . $e->getMessage());
        }
    }

    // 9. UPLOAD SPJ LENGKAP OLEH PEMOHON/UPTD/BIDANG
    public function uploadSpjPemohon(Request $request, $id)
    {
        $user = Auth::user();
        $pengajuan = PengajuanLs::findOrFail($id);

        // Hanya pemohon asli atau Admin yang boleh upload SPJ
        if ($pengajuan->user_id != $user->id && $user->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak: Hanya pemohon dokumen yang dapat mengupload SPJ lengkap.');
        }

        if ($pengajuan->spj_status != 'Menunggu Upload Pemohon') {
            return back()->with('error', 'Upload SPJ pemohon hanya bisa dilakukan jika verifikator sudah mengupload dokumen SP2D/SPM/SPP.');
        }

        try {
            $request->validate(['spj_lengkap_link' => 'required|url']);

            $pengajuan->spj_lengkap_link = $request->spj_lengkap_link;
            $pengajuan->spj_status = 'Menunggu Verifikasi SPJ';
            // Set batas waktu 2 hari tepat sejak Pemohon mengunggah SPJ Lengkap
            $pengajuan->spj_verifikator_deadline = now()->addDays(2)->format('Y-m-d H:i:s');
            $pengajuan->addHistoriCatatan('Upload SPJ Lengkap (Pemohon)', 'Dokumen Diunggah', $request->catatan ?? 'SPJ Lengkap diunggah oleh Pemohon', $user);
            $pengajuan->save();

            // Notifikasi ke Verifikator Keuangan (Batas 2 hari)
            $verifikators = User::where('role', 'Verifikator Keuangan')->get();
            foreach ($verifikators as $v) {
                try {
                    Notification::create([
                        'user_id' => $v->id,
                        'title' => '⏱️ Tenggat 2 Hari Verifikasi SPJ Lengkap',
                        'message' => 'Pemohon telah mengunggah berkas SPJ Lengkap untuk pengajuan ' . $pengajuan->no_pengajuan . '. Mohon lakukan verifikasi kelengkapan SPJ dalam jangka waktu 2 hari (Batas: ' . \Carbon\Carbon::parse($pengajuan->spj_verifikator_deadline)->format('d/m/Y H:i') . ').',
                        'is_read' => false,
                    ]);
                } catch (\Throwable $e) {}
            }

            return redirect()->route('pengajuan.show', $id)->with('success', 'SPJ lengkap berhasil diupload. Batas waktu verifikasi oleh Verifikator Keuangan ditetapkan 2 hari.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengupload SPJ lengkap: ' . $e->getMessage());
        }
    }

    // 10. VERIFIKASI SPJ LENGKAP OLEH VERIFIKATOR
    public function verifikasiSpj(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role != 'Verifikator Keuangan' && $user->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak: Hanya Verifikator Keuangan yang dapat memverifikasi SPJ.');
        }

        $pengajuan = PengajuanLs::findOrFail($id);

        if ($pengajuan->spj_status != 'Menunggu Verifikasi SPJ') {
            return back()->with('error', 'SPJ belum diupload lengkap oleh pemohon.');
        }

        $catatanSpj = $request->catatan_spj ?? $request->catatan;

        if ($request->action == 'perbaiki' && empty($catatanSpj)) {
            return back()->with('error', 'Catatan / Alasan wajib diisi saat menolak atau meminta perbaikan SPJ.');
        }

        try {
            if ($request->action == 'setuju') {
                $pengajuan->spj_status = 'SPJ Lengkap';
                $pengajuan->spj_verified_at = now();
                $pengajuan->spj_verified_by = $user->id;
                $pengajuan->catatan_spj = $catatanSpj;
                $pengajuan->addHistoriCatatan('Verifikasi SPJ Lengkap', 'Disetujui & Verified 100%', $catatanSpj, $user);
                $pengajuan->save();

                try {
                    Notification::create([
                        'user_id' => $pengajuan->user_id,
                        'title' => 'SPJ Telah Diverifikasi ✅',
                        'message' => 'SPJ untuk pengajuan ' . $pengajuan->no_pengajuan . ' telah diverifikasi dan dinyatakan lengkap oleh Verifikator Keuangan.' . ($catatanSpj ? ' Catatan: "' . $catatanSpj . '"' : ''),
                        'is_read' => false,
                    ]);
                } catch (\Throwable $e) {}

                // Notifikasi ke Kepala Balai (Monitoring Dokumen Selesai 100%)
                $kepalaBalais = User::where('role', 'Kepala Balai')->get();
                foreach ($kepalaBalais as $kb) {
                    try {
                        Notification::create([
                            'user_id' => $kb->id,
                            'title' => '🟢 SPJ Selesai & Lengkap 100%',
                            'message' => 'Dokumen SPJ ' . $pengajuan->no_pengajuan . ' dari ' . $pengajuan->bidang . ' telah diverifikasi lengkap 100%.',
                            'is_read' => false,
                        ]);
                    } catch (\Throwable $e) {}
                }

                return redirect()->route('pengajuan.show', $id)->with('success', 'SPJ berhasil diverifikasi dan dinyatakan lengkap. ✅');
            } else {
                // Kembalikan ke pemohon untuk upload ulang
                $pengajuan->spj_status = 'Menunggu Upload Pemohon';
                $pengajuan->spj_lengkap_link = null;
                $pengajuan->catatan_spj = $catatanSpj;
                $pengajuan->addHistoriCatatan('Verifikasi SPJ Lengkap', 'Ditolak / Perlu Perbaikan', $catatanSpj, $user);
                $pengajuan->save();

                try {
                    Notification::create([
                        'user_id' => $pengajuan->user_id,
                        'title' => 'SPJ Perlu Diperbaiki',
                        'message' => 'SPJ untuk pengajuan ' . $pengajuan->no_pengajuan . ' ditolak oleh Verifikator: "' . ($catatanSpj ?? '') . '". Silakan upload ulang SPJ yang benar.',
                        'is_read' => false,
                    ]);
                } catch (\Throwable $e) {}

                return redirect()->route('pengajuan.show', $id)->with('success', 'SPJ dikembalikan ke pemohon untuk perbaikan.');
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memverifikasi SPJ: ' . $e->getMessage());
        }
    }

    // =========================================================
    // POIN 2: FITUR ADMIN - EDIT TANGGAL & HAPUS PENGAJUAN
    // =========================================================

    // 11. ADMIN EDIT TANGGAL PENGAJUAN
    public function adminEditDate(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak: Hanya Admin Keuangan yang dapat mengedit tanggal pengajuan.');
        }

        $pengajuan = PengajuanLs::findOrFail($id);

        $request->validate([
            'tgl_pengajuan' => 'required|date',
        ]);

        try {
            $pengajuan->tgl_pengajuan = $request->tgl_pengajuan;
            $pengajuan->save();

            return redirect()->route('pengajuan.show', $id)->with('success', 'Tanggal pengajuan berhasil diperbarui ke ' . $request->tgl_pengajuan . '.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memperbarui tanggal: ' . $e->getMessage());
        }
    }

    // 12. ADMIN HAPUS PENGAJUAN
    public function adminDelete($id)
    {
        $user = Auth::user();
        if ($user->role != 'Admin Keuangan') {
            abort(403, 'Akses Ditolak: Hanya Admin Keuangan yang dapat menghapus pengajuan.');
        }

        try {
            $pengajuan = PengajuanLs::findOrFail($id);
            $noPengajuan = $pengajuan->no_pengajuan;
            $pengajuan->delete();

            return redirect()->route('pengajuan.index')->with('success', 'Pengajuan ' . $noPengajuan . ' berhasil dihapus dari sistem.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus pengajuan: ' . $e->getMessage());
        }
    }
}