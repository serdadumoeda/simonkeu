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
                $query->whereNotIn('status', [B::S_DRAFT, B::S_VERIF]);
                break;
            case 'Operator Pembayaran':
                $query->whereIn('status', [B::S_SPP, B::S_SAKTI, B::S_SP2D, B::S_CAIR, B::S_SELESAI]);
                break;
            case 'Bendahara':
                $query->whereIn('status', [B::S_SP2D, B::S_CAIR, B::S_SELESAI]);
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
            'proses' => $all->whereNotIn('status', [B::S_DRAFT, B::S_SELESAI])->count(),
            'cair' => $all->whereIn('status', [B::S_CAIR, B::S_SELESAI])->count(),
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

    // 3. SIMPAN PENGAJUAN (Termin I atau Termin II terpisah, nominal manual)
    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless($this->isPemohonBanprog($user), 403);

        $data = $request->validate([
            'nama_lpks' => 'required|string|max:255',
            'kabupaten_kota' => 'required|string|max:255',
            'kejuruan' => 'required|string|max:255',
            'no_sk_penetapan' => 'nullable|string|max:255',
            'rekening_lpks' => 'nullable|string|max:100',
            'bank_lpks' => 'nullable|string|max:100',
            'kategori_pengajuan' => 'nullable|string|in:Termin I,Termin II',
            'nilai_banprog' => 'required|numeric|min:1',
            'link_drive_termin1' => 'required_without:link_drive_laporan|nullable|url',
            'link_drive_laporan' => 'nullable|url',
        ]);

        $total = (float) $data['nilai_banprog'];
        $kategori = $request->input('kategori_pengajuan') === B::KATEGORI_TERMIN2 ? B::KATEGORI_TERMIN2 : B::KATEGORI_TERMIN1;
        $isT2 = $kategori === B::KATEGORI_TERMIN2;

        $data['kategori_pengajuan'] = $kategori;
        $data['nilai_termin_1'] = $isT2 ? 0 : $total;
        $data['nilai_termin_2'] = $isT2 ? $total : 0;
        $data['no_banprog'] = $this->generateNo();
        $data['user_id'] = $user->id;
        $data['bidang'] = $user->bidang;
        $data['spj_status'] = 'Belum Upload';

        $checklist = array_map('intval', array_keys((array) $request->input('dok', [])));
        $ajukan = $request->input('action') !== 'draft';
        $data['status'] = $ajukan ? B::S_VERIF : B::S_DRAFT;

        if ($isT2) {
            $data['link_drive_laporan'] = $request->input('link_drive_laporan') ?: $request->input('link_drive_termin1');
            unset($data['link_drive_termin1']);
            $data['checklist_termin2_json'] = $checklist;
            if ($ajukan) $data['tgl_pengajuan_termin2'] = now();
        } else {
            $data['checklist_termin1_json'] = $checklist;
            if ($ajukan) $data['tgl_pengajuan_termin1'] = now();
        }

        $b = new B($data);
        $b->addHistori($ajukan ? "Mengajukan {$kategori}" : "Menyimpan Draft {$kategori}");
        $b->save();

        if ($ajukan) {
            $this->notifyRole('Verifikator Keuangan', 'Banprog Baru Menunggu Verifikasi', "Banprog {$b->no_banprog} ({$b->nama_lpks} — {$kategori}) menunggu verifikasi.");
        }

        return redirect()->route('banprog.show', $b->id)->with('success', $ajukan ? "Pengajuan Banprog {$kategori} berhasil diajukan." : 'Draft Banprog berhasil disimpan.');
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

    // 5. AJUKAN / AJUKAN ULANG (dari Draft / Perlu Perbaikan) — pemohon
    public function ajukan(Request $request, $id)
    {
        $user = Auth::user();
        $b = B::findOrFail($id);
        abort_unless($this->isPemohonBanprog($user) && $b->bidang === $user->bidang, 403);
        abort_unless(in_array($b->status, [B::S_DRAFT, B::S_PERBAIKAN]), 422, 'Status tidak valid.');

        $request->validate(['link_drive' => 'nullable|url']);

        if ($b->termin_aktif === 2) {
            if ($request->filled('link_drive')) $b->link_drive_laporan = $request->link_drive;
            $b->tgl_pengajuan_termin2 = $b->tgl_pengajuan_termin2 ?? now();
        } else {
            if ($request->filled('link_drive')) $b->link_drive_termin1 = $request->link_drive;
            $b->tgl_pengajuan_termin1 = $b->tgl_pengajuan_termin1 ?? now();
        }

        $b->status = B::S_VERIF;
        $b->addHistori('Mengajukan ulang', $request->catatan);
        $b->save();

        $this->notifyRole('Verifikator Keuangan', 'Banprog Menunggu Verifikasi', "Banprog {$b->no_banprog} ({$b->nama_lpks}) diajukan dan menunggu verifikasi.");
        return back()->with('success', 'Pengajuan berhasil dikirim ke Verifikator Keuangan.');
    }

    // 6. VERIFIKASI KEUANGAN — Verifikator Keuangan
    public function verifikasi(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['Verifikator Keuangan', 'Admin Keuangan']), 403);
        $b = B::findOrFail($id);
        abort_unless($b->status === B::S_VERIF, 422, 'Status tidak valid.');

        $isT2 = $b->termin_aktif === 2;
        $listDok = $isT2 ? B::CHECKLIST_TERMIN2 : B::CHECKLIST_TERMIN1;
        $checked = array_map('intval', array_keys((array) $request->input('cek', [])));

        if ($request->input('keputusan') === 'setuju') {
            if (count(array_intersect(array_keys($listDok), $checked)) < count($listDok)) {
                return back()->with('error', 'Seluruh checklist dokumen wajib dicentang sebelum disetujui.');
            }
            $b->{$isT2 ? 'checklist_termin2_json' : 'checklist_termin1_json'} = $checked;
            $b->status = B::S_PPK;
            $b->catatan_revisi = null;
            $b->addHistori("Verifikasi {$b->kategori_pengajuan} disetujui", $request->catatan);
            $b->save();
            $this->notifyRole('PPK', 'Banprog Menunggu Persetujuan PPK', "Banprog {$b->no_banprog} ({$b->nama_lpks} — {$b->kategori_pengajuan}) menunggu persetujuan Anda.");
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

    // 7. PERSETUJUAN PPK
    public function ppkApproval(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['PPK', 'Admin Keuangan']), 403);
        $b = B::findOrFail($id);
        abort_unless($b->status === B::S_PPK, 422, 'Status tidak valid.');

        if ($request->input('keputusan') === 'setuju') {
            $b->status = B::S_SPP;
            $b->addHistori("PPK menyetujui {$b->kategori_pengajuan}", $request->catatan);
            $b->save();
            $this->notifyRole('Operator Pembayaran', 'Banprog Siap Penerbitan SPP', "Banprog {$b->no_banprog} ({$b->nama_lpks}) disetujui PPK. Silakan terbitkan SPP.");
            return back()->with('success', 'Disetujui PPK, diteruskan ke Operator Pembayaran untuk Penerbitan SPP.');
        }

        $request->validate(['catatan' => 'required|string']);
        $b->status = B::S_PERBAIKAN;
        $b->catatan_revisi = $request->catatan;
        $b->addHistori('PPK menolak / minta perbaikan', $request->catatan);
        $b->save();
        $this->notifyUser($b->user_id, 'Banprog Ditolak PPK', "Banprog {$b->no_banprog}: {$request->catatan}");
        return back()->with('success', 'Pengajuan dikembalikan ke pemohon.');
    }

    // 8. PENERBITAN SPP — Operator Pembayaran
    public function penerbitanSpp(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['Operator Pembayaran', 'Admin Keuangan']), 403);
        $b = B::findOrFail($id);
        abort_unless($b->status === B::S_SPP, 422, 'Status tidak valid.');

        $request->validate(['no_spp' => 'required|string|max:100', 'tgl_spp' => 'required|date']);
        $b->no_spp = $request->no_spp;
        $b->tgl_spp = $request->tgl_spp;
        $b->status = B::S_SAKTI;
        $b->addHistori("Penerbitan SPP: {$request->no_spp}", $request->catatan);
        $b->save();

        $this->notifyRole('Operator Pembayaran', 'Banprog Siap Proses SAKTI/SPM', "Banprog {$b->no_banprog} ({$b->nama_lpks}) — SPP terbit, silakan input SPM.");
        return back()->with('success', 'SPP berhasil diterbitkan, lanjut ke tahap SAKTI/SPM.');
    }

    // 9. INPUT SPM (SAKTI/SPM PPSPM) — Operator Pembayaran
    public function inputSpm(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['Operator Pembayaran', 'Admin Keuangan']), 403);
        $b = B::findOrFail($id);
        abort_unless($b->status === B::S_SAKTI, 422, 'Status tidak valid.');
        $t = $b->termin_aktif;

        $request->validate(['no_spm' => 'required|string|max:100', 'tgl_spm' => 'required|date']);
        $b->{"no_spm_t$t"} = $request->no_spm;
        $b->{"tgl_spm_t$t"} = $request->tgl_spm;
        $b->status = B::S_SP2D;
        $b->addHistori("Input SPM SAKTI: {$request->no_spm}");
        $b->save();

        $this->notifyRole('Bendahara', 'Banprog Menunggu SP2D', "Banprog {$b->no_banprog} — SPM telah terbit, silakan input SP2D & pencairan.");
        return back()->with('success', 'SPM berhasil disimpan, menunggu pencairan SP2D oleh Bendahara.');
    }

    // 10. PENCAIRAN (INPUT SP2D) — Bendahara
    public function pencairan(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['Bendahara', 'Admin Keuangan']), 403);
        $b = B::findOrFail($id);
        abort_unless($b->status === B::S_SP2D, 422, 'Status tidak valid.');
        $t = $b->termin_aktif;

        $request->validate(['no_sp2d' => 'required|string|max:100', 'tgl_cair' => 'required|date']);
        $b->{"no_sp2d_t$t"} = $request->no_sp2d;
        $b->{"tgl_cair_t$t"} = $request->tgl_cair;
        $b->status = B::S_CAIR;
        $b->addHistori("Dicairkan (SP2D {$request->no_sp2d})");
        $b->save();

        $this->notifyUser($b->user_id, 'Banprog Dicairkan', "Dana Banprog {$b->no_banprog} ({$b->kategori_pengajuan}) telah dicairkan (SP2D {$request->no_sp2d}).");
        return back()->with('success', 'Pencairan SP2D berhasil dicatat, lanjut ke tahap Penyerahan.');
    }

    // 11. PENYERAHAN — Bendahara
    public function penyerahan(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['Bendahara', 'Admin Keuangan']), 403);
        $b = B::findOrFail($id);
        abort_unless($b->status === B::S_CAIR, 422, 'Status tidak valid.');

        $request->validate(['bukti_penyerahan' => 'nullable|string|max:500']);
        $b->bukti_penyerahan = $request->bukti_penyerahan;
        $b->status = B::S_SELESAI;
        $b->spj_status = 'Belum Upload';
        $b->spj_deadline = now()->addDays(14);
        $b->addHistori('Penyerahan selesai', $request->bukti_penyerahan);
        $b->save();

        $this->notifyUser($b->user_id, 'Banprog Selesai Dicairkan', "Penyerahan Banprog {$b->no_banprog} selesai. Menunggu upload SPM/SP2D untuk kelengkapan SPJ.");
        return back()->with('success', 'Penyerahan selesai dicatat. Masuk ke tahap penatausahaan SPJ.');
    }

    // 12. UPLOAD SPM/SP2D (SPJ TAHAP 1) — Verifikator Keuangan / Operator Pembayaran
    public function uploadSpjVerifikator(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['Verifikator Keuangan', 'Operator Pembayaran', 'Admin Keuangan']), 403);
        $b = B::findOrFail($id);
        abort_unless($b->status === B::S_SELESAI && ($b->spj_status ?? 'Belum Upload') === 'Belum Upload', 422, 'Status tidak valid.');

        $request->validate(['spj_verifikator_link' => 'required|url']);
        $b->spj_verifikator_link = $request->spj_verifikator_link;
        $b->spj_status = 'Menunggu Upload Pemohon';
        $b->addHistori('Upload SPM/SP2D (SPJ Keuangan)');
        $b->save();

        $this->notifyUser($b->user_id, 'Upload SPJ Pemohon Diperlukan', "SPM/SP2D Banprog {$b->no_banprog} sudah diunggah. Silakan unggah link SPJ Pemohon.");
        return back()->with('success', 'Link SPM/SP2D berhasil diunggah, diteruskan ke Pemohon.');
    }

    // 13. UPLOAD SPJ PEMOHON (SPJ TAHAP 2) — Pemohon (SriSulis / Herna)
    public function uploadSpjPemohon(Request $request, $id)
    {
        $user = Auth::user();
        $b = B::findOrFail($id);
        $allowed = ($this->isPemohonBanprog($user) && $b->bidang === $user->bidang) || $user->role === 'Admin Keuangan';
        abort_unless($allowed, 403);
        abort_unless($b->status === B::S_SELESAI && $b->spj_status === 'Menunggu Upload Pemohon', 422, 'Status tidak valid.');

        $request->validate(['spj_pemohon_link' => 'required|url']);
        $b->spj_pemohon_link = $request->spj_pemohon_link;
        $b->spj_status = 'Menunggu Verifikasi SPJ';
        $b->addHistori('Upload SPJ Pemohon');
        $b->save();

        $this->notifyRole('Verifikator Keuangan', 'SPJ Banprog Menunggu Verifikasi', "SPJ Pemohon untuk {$b->no_banprog} ({$b->nama_lpks}) menunggu verifikasi.");
        return back()->with('success', 'SPJ Pemohon berhasil dikirim ke Verifikator Keuangan.');
    }

    // 14. VERIFIKASI SPJ (SPJ TAHAP 3) — Verifikator Keuangan
    public function verifikasiSpj(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['Verifikator Keuangan', 'Admin Keuangan']), 403);
        $b = B::findOrFail($id);
        abort_unless($b->status === B::S_SELESAI && $b->spj_status === 'Menunggu Verifikasi SPJ', 422, 'Status tidak valid.');

        if ($request->input('keputusan') === 'setuju') {
            $b->spj_status = 'SPJ Lengkap';
            $b->catatan_spj = $request->catatan;
            $b->addHistori('Verifikasi SPJ Lengkap', $request->catatan);
            $b->save();
            $this->notifyUser($b->user_id, 'SPJ Banprog Lengkap', "SPJ Banprog {$b->no_banprog} telah diverifikasi dan dinyatakan LENGKAP.");
            return back()->with('success', 'SPJ dinyatakan lengkap.');
        }

        $request->validate(['catatan' => 'required|string'], ['catatan.required' => 'Catatan perbaikan SPJ wajib diisi.']);
        $b->spj_status = 'Menunggu Upload Pemohon';
        $b->catatan_spj = $request->catatan;
        $b->addHistori('SPJ dikembalikan untuk perbaikan', $request->catatan);
        $b->save();
        $this->notifyUser($b->user_id, 'SPJ Banprog Perlu Perbaikan', "SPJ {$b->no_banprog}: {$request->catatan}");
        return back()->with('success', 'SPJ dikembalikan ke pemohon untuk diperbaiki.');
    }

    // 15. CETAK CHECKLIST
    public function cetak($id, Request $request)
    {
        abort_unless($this->canAccessModule(Auth::user()), 403);
        $b = B::findOrFail($id);
        $termin = $request->has('termin')
            ? ((int) $request->get('termin') === 2 ? 2 : 1)
            : $b->termin_aktif;
        return view('banprog.cetak', compact('b', 'termin'));
    }

    // 16. HAPUS
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
