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

        $steps = [
            ['Pengajuan', 'bi-pencil-square', [B::S_DRAFT]],
            ['Verifikasi T1', 'bi-clipboard-check', [B::S_VERIF_T1]],
            ['PPK T1', 'bi-person-check', [B::S_PPK_T1]],
            ['SPM/SP2D T1', 'bi-bank', [B::S_SPM_T1, B::S_SP2D_T1]],
            ['Laporan LPKS', 'bi-journal-text', [B::S_LAPORAN]],
            ['Verifikasi T2', 'bi-clipboard2-check', [B::S_VERIF_T2]],
            ['PPK T2', 'bi-person-check-fill', [B::S_PPK_T2]],
            ['SPM/SP2D T2', 'bi-bank2', [B::S_SPM_T2, B::S_SP2D_T2]],
            ['Selesai', 'bi-patch-check-fill', [B::S_SELESAI]],
        ];
        $curIdx = 0;
        foreach ($steps as $i => $s) { if (in_array($b->status, $s[2])) $curIdx = $i; }
        if ($b->status === B::S_PERBAIKAN) $curIdx = $b->tgl_cair_t1 ? 4 : 0;
        $isT2 = in_array($b->status, [B::S_VERIF_T2]);
        $cekT1 = $b->checklist_termin1_json ?? [];
        $cekT2 = $b->checklist_termin2_json ?? [];
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
        <div>
            <a href="{{ route('banprog.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Daftar Banprog</a>
            <h4 class="fw-bold mb-0 mt-1">{{ $b->nama_lpks }}</h4>
            <span class="badge bg-light text-dark border">{{ $b->no_banprog }}</span>
            <span class="badge {{ $b->badge_class }} rounded-pill px-3 py-1">{{ $b->status }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('banprog.cetak', [$b->id, 'termin' => 1]) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill"><i class="bi bi-printer"></i> Checklist T1</a>
            @if($b->tgl_cair_t1)
                <a href="{{ route('banprog.cetak', [$b->id, 'termin' => 2]) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill"><i class="bi bi-printer"></i> Checklist T2</a>
            @endif
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

    {{-- STEPPER --}}
    <div class="card card-custom p-3 px-4 bg-white mb-4 overflow-auto">
        <div class="stepper-container mb-2" style="min-width: 820px">
            <div class="stepper-line"></div>
            <div class="stepper-line-progress" style="width: {{ $curIdx / (count($steps) - 1) * 90 }}%"></div>
            @foreach($steps as $i => [$label, $icon])
                @php
                    $cls = $i < $curIdx || $b->status === B::S_SELESAI ? 'completed' : ($i === $curIdx ? ($b->status === B::S_PERBAIKAN ? 'warning' : 'active') : '');
                @endphp
                <div class="stepper-item {{ $cls }}">
                    <div class="stepper-icon"><i class="bi {{ $cls === 'completed' ? 'bi-check-lg' : $icon }}"></i></div>
                    <div class="stepper-label">{{ $label }}</div>
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
                <div class="bp-section-title mb-3">Identitas LPKS</div>
                <div class="row g-3 small">
                    @foreach([
                        'Kabupaten / Kota' => $b->kabupaten_kota,
                        'Kejuruan' => $b->kejuruan,
                        'Kepala LPKS' => $b->nama_kepala_lpks,
                        'No. HP Kepala' => $b->no_hp_kepala_lpks,
                        'No. SK Penetapan' => $b->no_sk_penetapan ?: '—',
                        'Rekening' => trim(($b->bank_lpks ?? '') . ' ' . ($b->rekening_lpks ?? '')) ?: '—',
                        'Pelaksanaan' => $b->tgl_pelaksanaan_mulai ? $b->tgl_pelaksanaan_mulai->format('d/m/Y') . ' s.d. ' . optional($b->tgl_pelaksanaan_selesai)->format('d/m/Y') : '—',
                        'Bidang Pengelola' => ($b->bidang ?? '—') . ' (' . ($b->user->name ?? '-') . ')',
                    ] as $k => $v)
                        <div class="col-md-6"><div class="text-muted">{{ $k }}</div><div class="fw-semibold text-dark">{{ $v }}</div></div>
                    @endforeach
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="bp-termin h-100 {{ $b->tgl_cair_t1 ? 'done' : 'active' }}">
                        <div class="d-flex justify-content-between"><span class="fw-bold">Termin 1 · 70%</span>@if($b->tgl_cair_t1)<span class="badge bg-success">Cair</span>@endif</div>
                        <div class="fs-4 fw-bold my-1">{{ $rp($b->nilai_termin_1) }}</div>
                        <div class="small text-muted">SPM: {{ $b->no_spm_t1 ?? '—' }} · SP2D: {{ $b->no_sp2d_t1 ?? '—' }}<br>Tgl Cair: {{ optional($b->tgl_cair_t1)->format('d/m/Y') ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="bp-termin h-100 {{ $b->tgl_cair_t2 ? 'done' : ($b->tgl_cair_t1 ? 'active' : '') }}">
                        <div class="d-flex justify-content-between"><span class="fw-bold">Termin 2 · 30%</span>@if($b->tgl_cair_t2)<span class="badge bg-success">Cair</span>@endif</div>
                        <div class="fs-4 fw-bold my-1">{{ $rp($b->nilai_termin_2) }}</div>
                        <div class="small text-muted">SPM: {{ $b->no_spm_t2 ?? '—' }} · SP2D: {{ $b->no_sp2d_t2 ?? '—' }}<br>Tgl Cair: {{ optional($b->tgl_cair_t2)->format('d/m/Y') ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="card card-custom p-4 bg-white mb-4">
                <div class="bp-section-title mb-3">Dokumen</div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @if($b->link_drive_termin1)<a href="{{ $b->link_drive_termin1 }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-google"></i> Drive Dokumen Termin 1</a>@endif
                    @if($b->link_drive_laporan)<a href="{{ $b->link_drive_laporan }}" target="_blank" class="btn btn-sm btn-outline-success rounded-pill"><i class="bi bi-google"></i> Drive Laporan Termin 2</a>@endif
                </div>
                <div class="small fw-semibold mb-2">Checklist Termin 1 ({{ count($cekT1) }}/{{ count(B::CHECKLIST_TERMIN1) }})</div>
                <ul class="list-unstyled small mb-3">
                    @foreach(B::CHECKLIST_TERMIN1 as $no => $ur)
                        <li class="mb-1"><i class="bi {{ in_array($no, $cekT1) ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }} me-1"></i>{{ $no }}. {{ $ur }}</li>
                    @endforeach
                </ul>
                @if($b->tgl_cair_t1)
                    <div class="small fw-semibold mb-2">Checklist Laporan Termin 2 ({{ count($cekT2) }}/{{ count(B::CHECKLIST_TERMIN2) }})</div>
                    <ul class="list-unstyled small mb-0">
                        @foreach(B::CHECKLIST_TERMIN2 as $no => $ur)
                            <li class="mb-1"><i class="bi {{ in_array($no, $cekT2) ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }} me-1"></i>{{ $no }}. {{ $ur }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- KANAN: PANEL AKSI --}}
        <div class="col-lg-5">
            <div class="card card-custom p-4 bg-white mb-4 border-top border-4 border-warning">
                <div class="bp-section-title mb-3"><i class="bi bi-lightning-charge me-1"></i> Panel Aksi</div>
                @php $adaAksi = false; @endphp

                {{-- PEMOHON: Ajukan dari Draft / Perbaikan --}}
                @if(($isPemohon || $isAdmin) && in_array($b->status, [B::S_DRAFT, B::S_PERBAIKAN]))
                    @php $adaAksi = true; @endphp
                    <form action="{{ route('banprog.ajukan', $b->id) }}" method="POST">
                        @csrf
                        <label class="form-label small fw-semibold">Link Google Drive {{ $b->tgl_cair_t1 ? 'Laporan' : 'Dokumen' }} (perbarui bila perlu)</label>
                        <input type="url" name="link_drive" class="form-control form-control-sm mb-2" value="{{ $b->tgl_cair_t1 ? $b->link_drive_laporan : $b->link_drive_termin1 }}">
                        <textarea name="catatan" class="form-control form-control-sm mb-2" rows="2" placeholder="Catatan perbaikan (opsional)"></textarea>
                        <button class="btn btn-warning w-100 rounded-pill fw-semibold" id="btn-ajukan"><i class="bi bi-send"></i> {{ $b->status === B::S_DRAFT ? 'Ajukan Termin 1' : 'Ajukan Ulang' }}</button>
                    </form>
                @endif

                {{-- PEMOHON: Upload laporan --}}
                @if(($isPemohon || $isAdmin) && $b->status === B::S_LAPORAN)
                    @php $adaAksi = true; @endphp
                    <div class="alert alert-warning small py-2"><i class="bi bi-info-circle"></i> Termin 1 telah cair. Unggah laporan pelaksanaan untuk mengajukan Termin 2.</div>
                    <form action="{{ route('banprog.uploadLaporan', $b->id) }}" method="POST">
                        @csrf
                        <label class="form-label small fw-semibold">Link Google Drive Laporan *</label>
                        <input type="url" name="link_drive_laporan" class="form-control form-control-sm mb-2" required placeholder="https://drive.google.com/...">
                        <div class="d-grid gap-1 mb-2">
                            @foreach(B::CHECKLIST_TERMIN2 as $no => $ur)
                                <label class="bp-check-item small d-flex gap-2"><input type="checkbox" class="form-check-input mt-0" name="dok[{{ $no }}]" value="1"> {{ $no }}. {{ $ur }}</label>
                            @endforeach
                        </div>
                        <textarea name="catatan" class="form-control form-control-sm mb-2" rows="2" placeholder="Catatan (opsional)"></textarea>
                        <button class="btn btn-success w-100 rounded-pill fw-semibold" id="btn-upload-laporan"><i class="bi bi-cloud-upload"></i> Kirim Laporan & Ajukan Termin 2</button>
                    </form>
                @endif

                {{-- VERIFIKATOR --}}
                @if(in_array($role, ['Verifikator Keuangan', 'Admin Keuangan']) && in_array($b->status, [B::S_VERIF_T1, B::S_VERIF_T2]))
                    @php
                        $adaAksi = true;
                        $list = $isT2 ? B::CHECKLIST_TERMIN2 : B::CHECKLIST_TERMIN1;
                        $pre = $isT2 ? $cekT2 : $cekT1;
                    @endphp
                    <form action="{{ route('banprog.verifikasi', $b->id) }}" method="POST" id="form-verifikasi">
                        @csrf
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold">Checklist {{ $isT2 ? 'Laporan Termin 2' : 'Termin 1 (2 rangkap)' }}</span>
                            <button type="button" class="btn btn-link btn-sm p-0" onclick="document.querySelectorAll('.verif-cek').forEach(c=>c.checked=true)">Centang semua</button>
                        </div>
                        <div class="d-grid gap-1 mb-2">
                            @foreach($list as $no => $ur)
                                <label class="bp-check-item small d-flex gap-2 {{ !$isT2 && in_array($no, B::CHECKLIST_HIGHLIGHT) ? 'highlight' : '' }}">
                                    <input type="checkbox" class="form-check-input mt-0 verif-cek" name="cek[{{ $no }}]" value="1" {{ in_array($no, $pre) ? 'checked' : '' }}>
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

                {{-- PPK --}}
                @if(in_array($role, ['PPK', 'Admin Keuangan']) && in_array($b->status, [B::S_PPK_T1, B::S_PPK_T2]))
                    @php $adaAksi = true; $t = $b->status === B::S_PPK_T2 ? 2 : 1; @endphp
                    <div class="bg-light rounded-3 p-3 small mb-3">
                        <div class="fw-bold mb-1">Ringkasan Persetujuan Termin {{ $t }}</div>
                        LPKS: <strong>{{ $b->nama_lpks }}</strong> ({{ $b->kejuruan }})<br>
                        Nilai Termin {{ $t }}: <strong>{{ $rp($t === 1 ? $b->nilai_termin_1 : $b->nilai_termin_2) }}</strong><br>
                        Checklist: {{ count($t === 1 ? $cekT1 : $cekT2) }}/{{ count($t === 1 ? B::CHECKLIST_TERMIN1 : B::CHECKLIST_TERMIN2) }} lengkap
                    </div>
                    <form action="{{ route('banprog.ppkApproval', $b->id) }}" method="POST">
                        @csrf
                        <textarea name="catatan" class="form-control form-control-sm mb-2" rows="2" placeholder="Catatan (wajib jika ditolak)"></textarea>
                        <div class="d-flex gap-2">
                            <button name="keputusan" value="tolak" class="btn btn-outline-danger flex-fill rounded-pill"><i class="bi bi-x-circle"></i> Tolak</button>
                            <button name="keputusan" value="setuju" class="btn btn-primary flex-fill rounded-pill" id="btn-ppk-setuju"><i class="bi bi-check2-all"></i> Setujui Termin {{ $t }}</button>
                        </div>
                    </form>
                @endif

                {{-- OPERATOR PEMBAYARAN --}}
                @if(in_array($role, ['Operator Pembayaran', 'Admin Keuangan']) && in_array($b->status, [B::S_SPM_T1, B::S_SPM_T2]))
                    @php $adaAksi = true; $t = $b->status === B::S_SPM_T2 ? 2 : 1; @endphp
                    <form action="{{ route('banprog.inputSpm', $b->id) }}" method="POST">
                        @csrf
                        <div class="small fw-semibold mb-2">Input SPM SAKTI — Termin {{ $t }} ({{ $rp($t === 1 ? $b->nilai_termin_1 : $b->nilai_termin_2) }})</div>
                        <input name="no_spm" class="form-control form-control-sm mb-2" placeholder="Nomor SPM" required>
                        <input type="date" name="tgl_spm" class="form-control form-control-sm mb-2" value="{{ date('Y-m-d') }}" required>
                        <button class="btn btn-info text-white w-100 rounded-pill fw-semibold" id="btn-input-spm"><i class="bi bi-file-earmark-check"></i> Simpan SPM</button>
                    </form>
                @endif

                {{-- BENDAHARA --}}
                @if(in_array($role, ['Bendahara', 'Admin Keuangan']) && in_array($b->status, [B::S_SP2D_T1, B::S_SP2D_T2]))
                    @php $adaAksi = true; $t = $b->status === B::S_SP2D_T2 ? 2 : 1; @endphp
                    <form action="{{ route('banprog.pencairan', $b->id) }}" method="POST">
                        @csrf
                        <div class="small fw-semibold mb-2">Input SP2D & Pencairan Termin {{ $t }} ({{ $rp($t === 1 ? $b->nilai_termin_1 : $b->nilai_termin_2) }})</div>
                        <input name="no_sp2d" class="form-control form-control-sm mb-2" placeholder="Nomor SP2D" required>
                        <input type="date" name="tgl_cair" class="form-control form-control-sm mb-2" value="{{ date('Y-m-d') }}" required>
                        <button class="btn btn-success w-100 rounded-pill fw-semibold" id="btn-pencairan"><i class="bi bi-cash-stack"></i> Konfirmasi Pencairan Termin {{ $t }}</button>
                    </form>
                @endif

                @unless($adaAksi)
                    <div class="text-center text-muted small py-3">
                        <i class="bi {{ $b->status === B::S_SELESAI ? 'bi-patch-check-fill text-success' : 'bi-hourglass-split' }} fs-2 d-block mb-1"></i>
                        {{ $b->status === B::S_SELESAI ? 'Banprog telah tuntas 100%.' : 'Tidak ada aksi untuk Anda pada tahap ini.' }}
                    </div>
                @endunless
            </div>

            <div class="card card-custom p-4 bg-white">
                <div class="bp-section-title mb-3"><i class="bi bi-clock-history me-1"></i> Riwayat</div>
                <div class="bp-timeline small">
                    @forelse(array_reverse($b->histori_json ?? []) as $h)
                        <div class="t-item">
                            <div class="fw-semibold">{{ $h['aksi'] }}</div>
                            <div class="text-muted" style="font-size:11px">{{ $h['waktu'] }} · {{ $h['user'] }} ({{ $h['role'] }})</div>
                            @if(!empty($h['catatan']))<div class="fst-italic text-secondary">"{{ $h['catatan'] }}"</div>@endif
                        </div>
                    @empty
                        <div class="text-muted">Belum ada riwayat.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
