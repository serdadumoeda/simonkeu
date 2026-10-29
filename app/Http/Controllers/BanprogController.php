<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\PengajuanBanprog as B;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BanprogController extends Controller
{
    private function isPemohonBanprog($user): bool
    {
        return $user->role === 'Operator Bidang' && (bool) $user->akses_banprog;
    }

    private function canAccessModule($user): bool
    {
        return $user->role !== 'Operator Bidang' || $this->isPemohonBanprog($user);
    }

    private function notifyRole(string $role, string $title, string $message): void
    {
        foreach (User::where('role', $role)->get() as $u) {
            try {
                Notification::create(['user_id' => $u->id, 'title' => $title, 'message' => $message, 'is_read' => false]);
            } catch (\Throwable $e) {}
        }
    }

    private function notifyUser(?int $userId, string $title, string $message): void
    {
        if (!$userId) return;
        try {
            Notification::create(['user_id' => $userId, 'title' => $title, 'message' => $message, 'is_read' => false]);
        } catch (\Throwable $e) {}
    }

    // 1. DAFTAR BANPROG
    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($this->canAccessModule($user), 403, 'Akses Ditolak: Akun Anda belum memiliki akses modul Banprog.');

        $query = B::with('user')->orderBy('created_at', 'desc');

        switch ($user->role) {
            case 'Operator Bidang':
                $query->where('bidang', $user->bidang);
                break;
            case 'Verifikator Keuangan':
                $query->where('status', '!=', B::S_DRAFT);
                break;
            case 'PPK':
                $query->whereNotIn('status', [B::S_DRAFT, B::S_VERIF_T1]);
                break;
            case 'Operator Pembayaran':
            case 'Bendahara':
                $query->whereIn('status', [B::S_SPM_T1, B::S_SP2D_T1, B::S_LAPORAN, B::S_VERIF_T2, B::S_PPK_T2, B::S_SPM_T2, B::S_SP2D_T2, B::S_SELESAI]);
                break;
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('no_banprog', 'like', "%$s%")
                  ->orWhere('nama_lpks', 'like', "%$s%")
                  ->orWhere('kabupaten_kota', 'like', "%$s%")
                  ->orWhere('kejuruan', 'like', "%$s%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $all = (clone $query)->get();
        $stats = [
            'total' => $all->count(),
            'proses' => $all->whereNotIn('status', [B::S_DRAFT, B::S_SELESAI, B::S_LAPORAN])->count(),
            'laporan' => $all->where('status', B::S_LAPORAN)->count(),
            'selesai' => $all->where('status', B::S_SELESAI)->count(),
            'nilai' => $all->sum('nilai_banprog'),
        ];

        $daftar = $query->paginate(10)->withQueryString();
        $statusList = array_merge(B::STATUS_ORDER, [B::S_PERBAIKAN]);

        return view('banprog.index', compact('daftar', 'stats', 'statusList'));
    }

    // 2. FORM BUAT
    public function create()
    {
        abort_unless($this->isPemohonBanprog(Auth::user()), 403, 'Hanya Operator Bidang dengan akses Banprog yang dapat membuat pengajuan.');
        $noBaru = $this->generateNo();
        return view('banprog.create', compact('noBaru'));
    }

    private function generateNo(): string
    {
        $prefix = 'BP-' . date('dmY') . '-';
        $last = B::where('no_banprog', 'like', $prefix . '%')->orderBy('no_banprog', 'desc')->value('no_banprog');
        $n = $last ? ((int) substr($last, -3)) + 1 : 1;
        return $prefix . str_pad($n, 3, '0', STR_PAD_LEFT);
    }

    // 3. SIMPAN
    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless($this->isPemohonBanprog($user), 403);

        $data = $request->validate([
            'nama_lpks' => 'required|string|max:255',
            'kabupaten_kota' => 'required|string|max:255',
            'nama_kepala_lpks' => 'required|string|max:255',
            'no_hp_kepala_lpks' => 'required|string|max:30',
            'kejuruan' => 'required|string|max:255',
            'no_sk_penetapan' => 'nullable|string|max:255',
            'rekening_lpks' => 'nullable|string|max:100',
            'bank_lpks' => 'nullable|string|max:100',
            'tgl_pelaksanaan_mulai' => 'nullable|date',
            'tgl_pelaksanaan_selesai' => 'nullable|date|after_or_equal:tgl_pelaksanaan_mulai',
            'nilai_banprog' => 'required|numeric|min:1',
            'link_drive_termin1' => 'required|url',
        ]);

        $total = (float) $data['nilai_banprog'];
        $data['nilai_termin_1'] = round($total * 0.7, 2);
        $data['nilai_termin_2'] = $total - $data['nilai_termin_1'];
        $data['no_banprog'] = $this->generateNo();
        $data['user_id'] = $user->id;
        $data['bidang'] = $user->bidang;
        $data['checklist_termin1_json'] = array_map('intval', array_keys((array) $request->input('dok', [])));

        $ajukan = $request->input('action') !== 'draft';
        $data['status'] = $ajukan ? B::S_VERIF_T1 : B::S_DRAFT;
        if ($ajukan) $data['tgl_pengajuan_termin1'] = now();

        $b = new B($data);
        $b->addHistori($ajukan ? 'Mengajukan Termin 1' : 'Menyimpan Draft');
        $b->save();

        if ($ajukan) {
            $this->notifyRole('Verifikator Keuangan', 'Banprog Baru Menunggu Verifikasi', "Banprog {$b->no_banprog} ({$b->nama_lpks}) menunggu verifikasi Termin 1.");
        }

        return redirect()->route('banprog.show', $b->id)->with('success', $ajukan ? 'Pengajuan Banprog Termin 1 berhasil diajukan.' : 'Draft Banprog berhasil disimpan.');
    }

    // 4. DETAIL
    public function show($id)
    {
        $user = Auth::user();
        abort_unless($this->canAccessModule($user), 403);
        $b = B::with('user')->findOrFail($id);
        if ($user->role === 'Operator Bidang' && $b->bidang !== $user->bidang) abort(403);
        return view('banprog.show', compact('b'));
    }

    // 5. AJUKAN (dari Draft / Perlu Perbaikan) — pemohon
    public function ajukan(Request $request, $id)
    {
        $user = Auth::user();
        $b = B::findOrFail($id);
        abort_unless($this->isPemohonBanprog($user) && $b->bidang === $user->bidang, 403);
        abort_unless(in_array($b->status, [B::S_DRAFT, B::S_PERBAIKAN]), 422, 'Status tidak valid.');

        $request->validate(['link_drive' => 'nullable|url']);

        if ($b->tgl_cair_t1) {
            // revisi di tahap laporan termin 2
            if ($request->filled('link_drive')) $b->link_drive_laporan = $request->link_drive;
            $b->status = B::S_VERIF_T2;
            $b->tgl_pengajuan_termin2 = now();
        } else {
            if ($request->filled('link_drive')) $b->link_drive_termin1 = $request->link_drive;
            $b->status = B::S_VERIF_T1;
            $b->tgl_pengajuan_termin1 = now();
        }
        $b->addHistori('Mengajukan ulang', $request->catatan);
        $b->save();

        $this->notifyRole('Verifikator Keuangan', 'Banprog Menunggu Verifikasi', "Banprog {$b->no_banprog} ({$b->nama_lpks}) diajukan dan menunggu verifikasi.");
        return back()->with('success', 'Pengajuan berhasil dikirim ke Verifikator Keuangan.');
    }

    // 6. VERIFIKASI (T1 & T2) — Verifikator Keuangan
    public function verifikasi(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['Verifikator Keuangan', 'Admin Keuangan']), 403);
        $b = B::findOrFail($id);
        abort_unless(in_array($b->status, [B::S_VERIF_T1, B::S_VERIF_T2]), 422, 'Status tidak valid.');

        $isT2 = $b->status === B::S_VERIF_T2;
        $listDok = $isT2 ? B::CHECKLIST_TERMIN2 : B::CHECKLIST_TERMIN1;
        $checked = array_map('intval', array_keys((array) $request->input('cek', [])));

        if ($request->input('keputusan') === 'setuju') {
            if (count(array_intersect(array_keys($listDok), $checked)) < count($listDok)) {
                return back()->with('error', 'Seluruh checklist dokumen wajib dicentang sebelum disetujui.');
            }
            $b->{$isT2 ? 'checklist_termin2_json' : 'checklist_termin1_json'} = $checked;
            $b->status = $isT2 ? B::S_PPK_T2 : B::S_PPK_T1;
            $b->catatan_revisi = null;
            $b->addHistori('Verifikasi ' . ($isT2 ? 'Laporan T2' : 'Termin 1') . ' disetujui', $request->catatan);
            $b->save();
            $this->notifyRole('PPK', 'Banprog Menunggu Persetujuan PPK', "Banprog {$b->no_banprog} ({$b->nama_lpks}) — Termin " . ($isT2 ? 2 : 1) . " menunggu persetujuan Anda.");
            return back()->with('success', 'Verifikasi disetujui, diteruskan ke PPK.');
        }

        $request->validate(['catatan' => 'required|string'], ['catatan.required' => 'Catatan perbaikan wajib diisi.']);
        $b->{$isT2 ? 'checklist_termin2_json' : 'checklist_termin1_json'} = $checked;
        $b->status = B::S_PERBAIKAN;
        $b->catatan_revisi = $request->catatan;
        $b->addHistori('Dikembalikan untuk perbaikan', $request->catatan);
        $b->save();
        $this->notifyUser($b->user_id, 'Banprog Perlu Perbaikan', "Banprog {$b->no_banprog}: {$request->catatan}");
        return back()->with('success', 'Pengajuan dikembalikan ke pemohon untuk perbaikan.');
    }

    // 7. PERSETUJUAN PPK (T1 & T2)
    public function ppkApproval(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['PPK', 'Admin Keuangan']), 403);
        $b = B::findOrFail($id);
        abort_unless(in_array($b->status, [B::S_PPK_T1, B::S_PPK_T2]), 422, 'Status tidak valid.');
        $isT2 = $b->status === B::S_PPK_T2;

        if ($request->input('keputusan') === 'setuju') {
            $b->status = $isT2 ? B::S_SPM_T2 : B::S_SPM_T1;
            $b->addHistori('PPK menyetujui Termin ' . ($isT2 ? 2 : 1), $request->catatan);
            $b->save();
            $this->notifyRole('Operator Pembayaran', 'Banprog Siap Proses SPM', "Banprog {$b->no_banprog} ({$b->nama_lpks}) — silakan input SPM Termin " . ($isT2 ? 2 : 1) . ".");
            return back()->with('success', 'Disetujui PPK, diteruskan ke Operator Pembayaran.');
        }

        $request->validate(['catatan' => 'required|string']);
        $b->status = B::S_PERBAIKAN;
        $b->catatan_revisi = $request->catatan;
        $b->addHistori('PPK menolak / minta perbaikan', $request->catatan);
        $b->save();
        $this->notifyUser($b->user_id, 'Banprog Ditolak PPK', "Banprog {$b->no_banprog}: {$request->catatan}");
        return back()->with('success', 'Pengajuan dikembalikan ke pemohon.');
    }

    // 8. INPUT SPM (T1 & T2) — Operator Pembayaran
    public function inputSpm(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['Operator Pembayaran', 'Admin Keuangan']), 403);
        $b = B::findOrFail($id);
        abort_unless(in_array($b->status, [B::S_SPM_T1, B::S_SPM_T2]), 422, 'Status tidak valid.');
        $t = $b->status === B::S_SPM_T2 ? 2 : 1;

        $request->validate(['no_spm' => 'required|string|max:100', 'tgl_spm' => 'required|date']);
        $b->{"no_spm_t$t"} = $request->no_spm;
        $b->{"tgl_spm_t$t"} = $request->tgl_spm;
        $b->status = $t === 2 ? B::S_SP2D_T2 : B::S_SP2D_T1;
        $b->addHistori("Input SPM Termin $t: {$request->no_spm}");
        $b->save();
        $this->notifyRole('Bendahara', 'Banprog Menunggu SP2D', "Banprog {$b->no_banprog} — SPM Termin $t telah terbit, silakan input SP2D & pencairan.");
        return back()->with('success', "SPM Termin $t berhasil disimpan.");
    }

    // 9. INPUT SP2D / PENCAIRAN (T1 & T2) — Bendahara
    public function pencairan(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['Bendahara', 'Admin Keuangan']), 403);
        $b = B::findOrFail($id);
        abort_unless(in_array($b->status, [B::S_SP2D_T1, B::S_SP2D_T2]), 422, 'Status tidak valid.');
        $t = $b->status === B::S_SP2D_T2 ? 2 : 1;

        $request->validate(['no_sp2d' => 'required|string|max:100', 'tgl_cair' => 'required|date']);
        $b->{"no_sp2d_t$t"} = $request->no_sp2d;
        $b->{"tgl_cair_t$t"} = $request->tgl_cair;
        $b->status = $t === 2 ? B::S_SELESAI : B::S_LAPORAN;
        $b->addHistori("Termin $t dicairkan (SP2D {$request->no_sp2d})");
        $b->save();

        $msg = $t === 1
            ? "Termin 1 (70%) Banprog {$b->no_banprog} telah cair. Silakan upload laporan pelaksanaan untuk pengajuan Termin 2."
            : "Termin 2 (30%) Banprog {$b->no_banprog} telah cair. Banprog SELESAI.";
        $this->notifyUser($b->user_id, "Banprog Termin $t Cair", $msg);
        return back()->with('success', "Pencairan Termin $t berhasil dicatat.");
    }

    // 10. UPLOAD LAPORAN (syarat Termin 2) — pemohon
    public function uploadLaporan(Request $request, $id)
    {
        $user = Auth::user();
        $b = B::findOrFail($id);
        abort_unless($this->isPemohonBanprog($user) && $b->bidang === $user->bidang, 403);
        abort_unless($b->status === B::S_LAPORAN, 422, 'Status tidak valid.');

        $request->validate(['link_drive_laporan' => 'required|url']);
        $b->link_drive_laporan = $request->link_drive_laporan;
        $b->checklist_termin2_json = array_map('intval', array_keys((array) $request->input('dok', [])));
        $b->status = B::S_VERIF_T2;
        $b->tgl_pengajuan_termin2 = now();
        $b->addHistori('Upload laporan & mengajukan Termin 2', $request->catatan);
        $b->save();
        $this->notifyRole('Verifikator Keuangan', 'Laporan Banprog Masuk', "Laporan Banprog {$b->no_banprog} ({$b->nama_lpks}) menunggu verifikasi Termin 2.");
        return back()->with('success', 'Laporan berhasil dikirim untuk verifikasi Termin 2.');
    }

    // 11. CETAK CHECKLIST
    public function cetak($id, Request $request)
    {
        abort_unless($this->canAccessModule(Auth::user()), 403);
        $b = B::findOrFail($id);
        $termin = (int) $request->get('termin', 1) === 2 ? 2 : 1;
        return view('banprog.cetak', compact('b', 'termin'));
    }

    // 12. HAPUS
    public function destroy($id)
    {
        $user = Auth::user();
        $b = B::findOrFail($id);
        $ok = $user->role === 'Admin Keuangan'
            || ($this->isPemohonBanprog($user) && $b->bidang === $user->bidang && $b->status === B::S_DRAFT);
        abort_unless($ok, 403);
        $b->delete();
        return redirect()->route('banprog.index')->with('success', 'Pengajuan Banprog dihapus.');
    }
}
