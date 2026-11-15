{{-- File: resources/views/banprog/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Banprog ' . $b->no_banprog)

@section('content')
    @include('banprog._style')
    @php
        use App\Models\PengajuanBanprog as B;
        $u = Auth::user();
        $role = $u->role;
        $isAdmin = $role === 'Admin Keuangan';
        $isPemohon = $role === 'Operator Bidang' && $u->akses_banprog && $b->bidang === $u->bidang;
        $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $t = $b->termin_aktif;
        $spjStatus = $b->spj_status ?? 'Belum Upload';

        $steps = [
            ['Pemohon', 'bi-pencil-square', $b->status === B::S_DRAFT ? 'active' : 'completed', $b->user->name ?? 'Operator Bidang'],
            ['Verifikasi Keuangan', 'bi-clipboard-check', match(true) {
                in_array($b->status, [B::S_PPK, B::S_SPP, B::S_SAKTI, B::S_SP2D, B::S_CAIR, B::S_SELESAI]) => 'completed',
                $b->status === B::S_VERIF => 'active',
                $b->status === B::S_PERBAIKAN => 'warning',
                default => '',
            }, 'Verifikator Keuangan'],
            ['Persetujuan PPK', 'bi-person-check', match(true) {
                in_array($b->status, [B::S_SPP, B::S_SAKTI, B::S_SP2D, B::S_CAIR, B::S_SELESAI]) => 'completed',
                $b->status === B::S_PPK => 'active',
                default => '',
            }, 'PPK'],
            ['Penerbitan SPP', 'bi-file-earmark-text', match(true) {
                in_array($b->status, [B::S_SAKTI, B::S_SP2D, B::S_CAIR, B::S_SELESAI]) => 'completed',
                $b->status === B::S_SPP => 'active',
                default => '',
            }, 'Operator Pembayaran'],
            ['SAKTI/SPM (PPSPM)', 'bi-send-check', match(true) {
                in_array($b->status, [B::S_SP2D, B::S_CAIR, B::S_SELESAI]) => 'completed',
                $b->status === B::S_SAKTI => 'active',
                default => '',
            }, 'Operator Pembayaran'],
            ['Pencairan', 'bi-bank', match(true) {
                in_array($b->status, [B::S_CAIR, B::S_SELESAI]) => 'completed',
                $b->status === B::S_SP2D => 'active',
                default => '',
            }, 'Bendahara'],
            ['Penyerahan', 'bi-box-seam', match(true) {
                $b->status === B::S_SELESAI => 'completed',
                $b->status === B::S_CAIR => 'active',
                default => '',
            }, 'Bendahara'],
            ['Upload SPM/SP2D', 'bi-cloud-upload', match(true) {
                in_array($spjStatus, ['Menunggu Upload Pemohon', 'Menunggu Verifikasi SPJ', 'SPJ Lengkap']) => 'completed',
                $b->status === B::S_SELESAI && $spjStatus === 'Belum Upload' => 'active',
                default => '',
            }, 'Verifikator / Op. Bayar'],
            ['SPJ Pemohon', 'bi-file-earmark-arrow-up', match(true) {
                in_array($spjStatus, ['Menunggu Verifikasi SPJ', 'SPJ Lengkap']) => 'completed',
                $b->status === B::S_SELESAI && $spjStatus === 'Menunggu Upload Pemohon' => 'active',
                default => '',
            }, $b->user->name ?? 'Pemohon'],
            ['Verifikasi SPJ', 'bi-patch-check-fill', match(true) {
                $spjStatus === 'SPJ Lengkap' => 'completed',
                $b->status === B::S_SELESAI && $spjStatus === 'Menunggu Verifikasi SPJ' => 'active',
                default => '',
            }, 'Verifikator Keuangan'],
        ];

        $completedCount = collect($steps)->filter(fn($s) => $s[2] === 'completed')->count();
        $progressPct = round($completedCount / (count($steps) - 1) * 90);

        $isT2 = $t === 2;
        $cekAktif = ($isT2 ? $b->checklist_termin2_json : $b->checklist_termin1_json) ?? [];
        $listDokAktif = $isT2 ? B::CHECKLIST_TERMIN2 : B::CHECKLIST_TERMIN1;
        $driveLink = $isT2 ? ($b->link_drive_laporan ?: $b->link_drive_termin1) : $b->link_drive_termin1;
        $noSpm = $isT2 ? $b->no_spm_t2 : $b->no_spm_t1;
        $tglSpm = $isT2 ? $b->tgl_spm_t2 : $b->tgl_spm_t1;
        $noSp2d = $isT2 ? $b->no_sp2d_t2 : $b->no_sp2d_t1;
        $tglCair = $isT2 ? $b->tgl_cair_t2 : $b->tgl_cair_t1;
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <a href="{{ route('banprog.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Daftar Banprog</a>
            <h4 class="fw-bold mb-0 mt-1">{{ $b->nama_lpks }}</h4>
            <span class="badge bg-light text-dark border">{{ $b->no_banprog }}</span>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1">{{ $b->kategori_pengajuan ?? 'Termin I' }}</span>
            <span class="badge {{ $b->badge_class }} rounded-pill px-3 py-1">{{ $b->status }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('banprog.cetak', [$b->id, 'termin' => 1]) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill"><i class="bi bi-printer"></i> Checklist T1</a>
            <a href="{{ route('banprog.cetak', [$b->id, 'termin' => 2]) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill"><i class="bi bi-printer"></i> Checklist T2</a>
            @if($isAdmin || ($isPemohon && $b->status === B::S_DRAFT))
                <form action="{{ route('banprog.destroy', $b->id) }}" method="POST" onsubmit="return confirm('Hapus pengajuan Banprog ini?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm rounded-pill"><i class="bi bi-trash3"></i></button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    {{-- STEPPER (10 LANGKAH SAMA DENGAN PENGAJUAN LS) --}}
    <div class="card card-custom p-3 px-4 bg-white mb-4 overflow-auto">
        <div class="small fw-semibold text-primary mb-2"><i class="bi bi-geo-alt-fill"></i> Posisi Berkas Banprog Saat Ini</div>
        <div class="stepper-container mb-2" style="min-width: 960px">
            <div class="stepper-line"></div>
            <div class="stepper-line-progress" style="width: {{ min(90, $progressPct) }}%"></div>
            @foreach($steps as [$label, $icon, $cls, $roleLabel])
                <div class="stepper-item {{ $cls }}">
                    <div class="stepper-icon"><i class="bi {{ $cls === 'completed' ? 'bi-check-lg' : $icon }}"></i></div>
                    <div class="stepper-label">{{ $label }}</div>
                    <div class="stepper-sublabel text-truncate" style="max-width: 110px;" title="{{ $roleLabel }}">{{ $roleLabel }}</div>
                </div>
            @endforeach
        </div>
    </div>

    @if($b->status === B::S_PERBAIKAN && $b->catatan_revisi)
        <div class="alert alert-danger border-0 shadow-sm"><strong><i class="bi bi-exclamation-octagon me-1"></i> Perlu Perbaikan:</strong> {{ $b->catatan_revisi }}</div>
    @endif

    <div class="row g-4">
        {{-- KIRI: DATA --}}
        <div class="col-lg-7">
            <div class="card card-custom p-4 bg-white mb-4">
                <div class="bp-section-title mb-3">Identitas LPKS / BLKK</div>
                @php
                    $identitas = [
                        'Kabupaten / Kota' => $b->kabupaten_kota,
                        'Kejuruan' => $b->kejuruan,
                        'No. SK Penetapan' => $b->no_sk_penetapan ?: '—',
                        'Rekening' => trim(($b->bank_lpks ?? '') . ' ' . ($b->rekening_lpks ?? '')) ?: '—',
                        'Bidang Pengelola' => ($b->bidang ?? '—') . ' (' . ($b->user->name ?? '-') . ')',
                    ];
                @endphp
                <div class="row g-3 small">
                    @foreach($identitas as $k => $v)
                        <div class="col-md-6"><div class="text-muted">{{ $k }}</div><div class="fw-semibold text-dark">{{ $v }}</div></div>
                    @endforeach
                </div>
            </div>

            <div class="card card-custom p-4 bg-white mb-4">
                <div class="bp-termin {{ $tglCair ? 'done' : 'active' }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Pengajuan {{ $b->kategori_pengajuan ?? 'Termin I' }}</span>
                        @if($tglCair)<span class="badge bg-success">Dicairkan</span>@endif
                    </div>
                    <div class="fs-3 fw-bold text-primary my-1">{{ $rp($b->nilai_banprog) }}</div>
                    <div class="small text-muted">
                        SPP: {{ $b->no_spp ?? '—' }} ({{ optional($b->tgl_spp)->format('d/m/Y') ?? '—' }})<br>
                        SPM: {{ $noSpm ?? '—' }} ({{ optional($tglSpm)->format('d/m/Y') ?? '—' }}) ·
                        SP2D: {{ $noSp2d ?? '—' }} ({{ optional($tglCair)->format('d/m/Y') ?? '—' }})
                        @if($b->bukti_penyerahan)
                            <br>Penyerahan: <strong>{{ $b->bukti_penyerahan }}</strong>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card card-custom p-4 bg-white mb-4">
                <div class="bp-section-title mb-3">Dokumen & Checklist ({{ $b->kategori_pengajuan ?? 'Termin I' }})</div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @if($driveLink)<a href="{{ $driveLink }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-google"></i> Drive Dokumen {{ $b->kategori_pengajuan ?? 'Termin I' }}</a>@endif
                    <a href="{{ route('banprog.cetak', [$b->id, 'termin' => 1]) }}" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill"><i class="bi bi-printer"></i> Checklist Termin 1</a>
                    <a href="{{ route('banprog.cetak', [$b->id, 'termin' => 2]) }}" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill"><i class="bi bi-printer"></i> Checklist Termin 2</a>
                    @if($b->spj_verifikator_link)<a href="{{ $b->spj_verifikator_link }}" target="_blank" class="btn btn-sm btn-outline-info rounded-pill"><i class="bi bi-file-earmark-pdf"></i> Link SPM/SP2D</a>@endif
                    @if($b->spj_pemohon_link)<a href="{{ $b->spj_pemohon_link }}" target="_blank" class="btn btn-sm btn-outline-success rounded-pill"><i class="bi bi-folder-check"></i> Link SPJ Pemohon</a>@endif
                </div>
                <div class="small fw-semibold mb-2">Checklist Dokumen ({{ count($cekAktif) }}/{{ count($listDokAktif) }})</div>
                <ul class="list-unstyled small mb-0">
                    @foreach($listDokAktif as $no => $ur)
                        <li class="mb-1"><i class="bi {{ in_array($no, $cekAktif) ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }} me-1"></i>{{ $no }}. {{ $ur }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- KANAN: PANEL AKSI --}}
        <div class="col-lg-5">
            <div class="card card-custom p-4 bg-white mb-4 border-top border-4 border-warning">
                <div class="bp-section-title mb-3"><i class="bi bi-lightning-charge me-1"></i> Panel Aksi</div>
                @php $adaAksi = false; @endphp

                {{-- 1. PEMOHON: Ajukan dari Draft / Perbaikan --}}
                @if(($isPemohon || $isAdmin) && in_array($b->status, [B::S_DRAFT, B::S_PERBAIKAN]))
                    @php $adaAksi = true; @endphp
                    <form action="{{ route('banprog.ajukan', $b->id) }}" method="POST">
                        @csrf
                        <label class="form-label small fw-semibold">Link Google Drive Dokumen (perbarui bila perlu)</label>
                        <input type="url" name="link_drive" class="form-control form-control-sm mb-2" value="{{ $driveLink }}">
                        <textarea name="catatan" class="form-control form-control-sm mb-2" rows="2" placeholder="Catatan perbaikan (opsional)"></textarea>
                        <button class="btn btn-warning w-100 rounded-pill fw-semibold" id="btn-ajukan"><i class="bi bi-send"></i> {{ $b->status === B::S_DRAFT ? 'Ajukan ' . ($b->kategori_pengajuan ?? 'Termin I') : 'Ajukan Ulang' }}</button>
                    </form>
                @endif

                {{-- 2. VERIFIKATOR KEUANGAN --}}
                @if(in_array($role, ['Verifikator Keuangan', 'Admin Keuangan']) && $b->status === B::S_VERIF)
                    @php $adaAksi = true; @endphp
                    <form action="{{ route('banprog.verifikasi', $b->id) }}" method="POST" id="form-verifikasi">
                        @csrf
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold">Checklist {{ $b->kategori_pengajuan ?? 'Termin I' }}</span>
                            <button type="button" class="btn btn-link btn-sm p-0" onclick="document.querySelectorAll('.verif-cek').forEach(c=>c.checked=true)">Centang semua</button>
                        </div>
                        <div class="d-grid gap-1 mb-2">
                            @foreach($listDokAktif as $no => $ur)
                                <label class="bp-check-item small d-flex gap-2 {{ !$isT2 && in_array($no, B::CHECKLIST_HIGHLIGHT) ? 'highlight' : '' }}">
                                    <input type="checkbox" class="form-check-input mt-0 verif-cek" name="cek[{{ $no }}]" value="1" {{ in_array($no, $cekAktif) ? 'checked' : '' }}>
                                    <span><span class="badge bg-primary bg-opacity-10 text-primary">{{ $no }}</span> {{ $ur }}</span>
                                </label>
                            @endforeach
                        </div>
                        <textarea name="catatan" class="form-control form-control-sm mb-2" rows="2" placeholder="Catatan (wajib jika dikembalikan)"></textarea>
                        <div class="d-flex gap-2">
                            <button name="keputusan" value="revisi" class="btn btn-outline-danger flex-fill rounded-pill" id="btn-verif-revisi"><i class="bi bi-arrow-return-left"></i> Kembalikan</button>
                            <button name="keputusan" value="setuju" class="btn btn-success flex-fill rounded-pill" id="btn-verif-setuju"><i class="bi bi-check2-circle"></i> Setujui → PPK</button>
                        </div>
                    </form>
                @endif

                {{-- 3. PPK --}}
                @if(in_array($role, ['PPK', 'Admin Keuangan']) && $b->status === B::S_PPK)
                    @php $adaAksi = true; @endphp
                    <div class="bg-light rounded-3 p-3 small mb-3">
                        <div class="fw-bold mb-1">Ringkasan Persetujuan ({{ $b->kategori_pengajuan ?? 'Termin I' }})</div>
                        LPKS / BLKK: <strong>{{ $b->nama_lpks }}</strong> ({{ $b->kejuruan }})<br>
                        Nominal: <strong>{{ $rp($b->nilai_banprog) }}</strong><br>
                        Checklist: {{ count($cekAktif) }}/{{ count($listDokAktif) }} lengkap
                    </div>
                    <form action="{{ route('banprog.ppkApproval', $b->id) }}" method="POST">
                        @csrf
                        <textarea name="catatan" class="form-control form-control-sm mb-2" rows="2" placeholder="Catatan (wajib jika ditolak)"></textarea>
                        <div class="d-flex gap-2">
                            <button name="keputusan" value="tolak" class="btn btn-outline-danger flex-fill rounded-pill"><i class="bi bi-x-circle"></i> Tolak</button>
                            <button name="keputusan" value="setuju" class="btn btn-primary flex-fill rounded-pill" id="btn-ppk-setuju"><i class="bi bi-check2-all"></i> Setujui → SPP</button>
                        </div>
                    </form>
                @endif

                {{-- 4. OPERATOR PEMBAYARAN: PENERBITAN SPP --}}
                @if(in_array($role, ['Operator Pembayaran', 'Admin Keuangan']) && $b->status === B::S_SPP)
                    @php $adaAksi = true; @endphp
                    <form action="{{ route('banprog.penerbitanSpp', $b->id) }}" method="POST">
                        @csrf
                        <div class="small fw-semibold mb-2">Penerbitan SPP — {{ $rp($b->nilai_banprog) }}</div>
                        <input name="no_spp" class="form-control form-control-sm mb-2" placeholder="Nomor SPP" required>
                        <input type="date" name="tgl_spp" class="form-control form-control-sm mb-2" value="{{ date('Y-m-d') }}" required>
                        <textarea name="catatan" class="form-control form-control-sm mb-2" rows="2" placeholder="Catatan (opsional)"></textarea>
                        <button class="btn btn-primary w-100 rounded-pill fw-semibold" id="btn-input-spp"><i class="bi bi-file-earmark-text"></i> Terbitkan SPP</button>
                    </form>
                @endif

                {{-- 5. OPERATOR PEMBAYARAN: SAKTI/SPM --}}
                @if(in_array($role, ['Operator Pembayaran', 'Admin Keuangan']) && $b->status === B::S_SAKTI)
                    @php $adaAksi = true; @endphp
                    <form action="{{ route('banprog.inputSpm', $b->id) }}" method="POST">
                        @csrf
                        <div class="small fw-semibold mb-2">Input SPM SAKTI — {{ $rp($b->nilai_banprog) }}</div>
                        <input name="no_spm" class="form-control form-control-sm mb-2" placeholder="Nomor SPM" required>
                        <input type="date" name="tgl_spm" class="form-control form-control-sm mb-2" value="{{ date('Y-m-d') }}" required>
                        <button class="btn btn-info text-white w-100 rounded-pill fw-semibold" id="btn-input-spm"><i class="bi bi-file-earmark-check"></i> Simpan SPM</button>
                    </form>
                @endif

                {{-- 6. BENDAHARA: PENCAIRAN SP2D --}}
                @if(in_array($role, ['Bendahara', 'Admin Keuangan']) && $b->status === B::S_SP2D)
                    @php $adaAksi = true; @endphp
                    <form action="{{ route('banprog.pencairan', $b->id) }}" method="POST">
                        @csrf
                        <div class="small fw-semibold mb-2">Input SP2D & Pencairan — {{ $rp($b->nilai_banprog) }}</div>
                        <input name="no_sp2d" class="form-control form-control-sm mb-2" placeholder="Nomor SP2D" required>
                        <input type="date" name="tgl_cair" class="form-control form-control-sm mb-2" value="{{ date('Y-m-d') }}" required>
                        <button class="btn btn-success w-100 rounded-pill fw-semibold" id="btn-input-sp2d"><i class="bi bi-cash-stack"></i> Catat Pencairan</button>
                    </form>
                @endif

                {{-- 7. BENDAHARA: PENYERAHAN --}}
                @if(in_array($role, ['Bendahara', 'Admin Keuangan']) && $b->status === B::S_CAIR)
                    @php $adaAksi = true; @endphp
                    <form action="{{ route('banprog.penyerahan', $b->id) }}" method="POST">
                        @csrf
                        <div class="small fw-semibold mb-2">Konfirmasi Penyerahan Dana</div>
                        <input name="bukti_penyerahan" class="form-control form-control-sm mb-2" placeholder="Keterangan / Link Bukti Penyerahan (opsional)">
                        <button class="btn btn-success w-100 rounded-pill fw-semibold" id="btn-penyerahan"><i class="bi bi-box-seam"></i> Selesaikan Penyerahan</button>
                    </form>
                @endif

                {{-- 8. SPJ TAHAP 1: UPLOAD SPM/SP2D (Verifikator / Operator Pembayaran) --}}
                @if(in_array($role, ['Verifikator Keuangan', 'Operator Pembayaran', 'Admin Keuangan']) && $b->status === B::S_SELESAI && $spjStatus === 'Belum Upload')
                    @php $adaAksi = true; @endphp
                    <form action="{{ route('banprog.uploadSpjVerifikator', $b->id) }}" method="POST">
                        @csrf
                        <div class="small fw-semibold mb-2">Upload Link SPM/SP2D (Tahap SPJ)</div>
                        <input type="url" name="spj_verifikator_link" class="form-control form-control-sm mb-2" placeholder="https://drive.google.com/..." required>
                        <button class="btn btn-info text-white w-100 rounded-pill fw-semibold" id="btn-spj-verifikator"><i class="bi bi-cloud-upload"></i> Upload SPM/SP2D</button>
                    </form>
                @endif

                {{-- 9. SPJ TAHAP 2: UPLOAD SPJ PEMOHON --}}
                @if(($isPemohon || $isAdmin) && $b->status === B::S_SELESAI && $spjStatus === 'Menunggu Upload Pemohon')
                    @php $adaAksi = true; @endphp
                    @if($b->catatan_spj)
                        <div class="alert alert-warning small py-2 mb-2"><strong>Catatan SPJ:</strong> {{ $b->catatan_spj }}</div>
                    @endif
                    <form action="{{ route('banprog.uploadSpjPemohon', $b->id) }}" method="POST">
                        @csrf
                        <div class="small fw-semibold mb-2">Upload Link SPJ Pemohon</div>
                        <input type="url" name="spj_pemohon_link" class="form-control form-control-sm mb-2" placeholder="https://drive.google.com/..." value="{{ $b->spj_pemohon_link }}" required>
                        <button class="btn btn-success w-100 rounded-pill fw-semibold" id="btn-spj-pemohon"><i class="bi bi-file-earmark-arrow-up"></i> Kirim SPJ Pemohon</button>
                    </form>
                @endif

                {{-- 10. SPJ TAHAP 3: VERIFIKASI SPJ --}}
                @if(in_array($role, ['Verifikator Keuangan', 'Admin Keuangan']) && $b->status === B::S_SELESAI && $spjStatus === 'Menunggu Verifikasi SPJ')
                    @php $adaAksi = true; @endphp
                    <form action="{{ route('banprog.verifikasiSpj', $b->id) }}" method="POST">
                        @csrf
                        <div class="small fw-semibold mb-2">Verifikasi Kelengkapan SPJ</div>
                        <textarea name="catatan" class="form-control form-control-sm mb-2" rows="2" placeholder="Catatan (wajib jika dikembalikan)"></textarea>
                        <div class="d-flex gap-2">
                            <button name="keputusan" value="revisi" class="btn btn-outline-danger flex-fill rounded-pill"><i class="bi bi-arrow-return-left"></i> Kembalikan</button>
                            <button name="keputusan" value="setuju" class="btn btn-success flex-fill rounded-pill" id="btn-spj-setuju"><i class="bi bi-patch-check"></i> SPJ Lengkap</button>
                        </div>
                    </form>
                @endif

                @if(!$adaAksi)
                    <div class="text-muted small text-center py-3">
                        <i class="bi bi-hourglass-split d-block fs-3 mb-1"></i>
                        @if($b->status === B::S_SELESAI && $spjStatus === 'SPJ Lengkap')
                            Seluruh proses pencairan & SPJ telah selesai.
                        @else
                            Tidak ada aksi untuk peran Anda pada tahap <strong>{{ $b->status }}</strong>.
                        @endif
                    </div>
                @endif
            </div>

            {{-- HISTORI --}}
            <div class="card card-custom p-4 bg-white">
                <div class="bp-section-title mb-3"><i class="bi bi-clock-history me-1"></i> Riwayat Alur</div>
                <div class="small">
                    @foreach(array_reverse($b->histori_json ?? []) as $h)
                        <div class="border-start border-2 border-primary ps-3 pb-3 position-relative">
                            <div class="fw-semibold text-dark">{{ $h['aksi'] }}</div>
                            <div class="text-muted" style="font-size:11px">{{ $h['waktu'] }} · {{ $h['user'] ?? '-' }} ({{ $h['role'] ?? '-' }})</div>
                            @if(!empty($h['catatan']))<div class="mt-1 bg-light rounded p-2 text-dark">{{ $h['catatan'] }}</div>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
