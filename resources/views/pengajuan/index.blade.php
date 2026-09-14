{{-- File: resources/views/pengajuan/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Daftar Pengajuan')

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card card-custom p-4 bg-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold text-dark mb-0">Daftar Pengajuan Pembayaran</h4>
                <p class="text-muted mb-0 small">Kelola dan lacak posisi berkas pengajuan SPJ Anda</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left-short"></i> Dashboard
                </a>
                <a href="{{ route('pengajuan.excel', request()->query()) }}" class="btn btn-success btn-sm rounded-pill px-3">
                    <i class="bi bi-file-earmark-excel"></i> Ekspor ke Excel
                </a>
                @if(Auth::user()->role == 'Admin Keuangan' || Auth::user()->role == 'Operator Pembayaran')
                    <a href="{{ route('pengajuan.createLampau') }}" class="btn btn-warning btn-sm text-dark fw-semibold rounded-pill px-3 shadow-sm">
                        <i class="bi bi-clock-history"></i> Rekam Data Lampau
                    </a>
                @endif
                @if(Auth::user()->role == 'Operator Bidang')
                    <a href="{{ route('pengajuan.create') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="bi bi-plus-lg"></i> Tambah Pengajuan
                    </a>
                @endif
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" class="row g-3 mb-4 bg-light p-3 rounded border border-light-subtle shadow-sm mx-0">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Tahun Anggaran</label>
                <select name="tahun" class="form-select form-select-sm border-0 shadow-sm fw-semibold text-primary">
                    @foreach($daftarTahun as $t)
                        <option value="{{ $t }}" {{ ($tahunAktif ?? date('Y')) == $t ? 'selected' : '' }}>
                            Tahun {{ $t }} {{ $t == date('Y') ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                    <option value="semua" {{ ($tahunAktif ?? '') == 'semua' ? 'selected' : '' }}>-- Semua Tahun --</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-secondary">Filter Bidang</label>
                <select name="bidang" class="form-select form-select-sm border-0 shadow-sm">
                    <option value="">-- Semua Bidang --</option>
                    @foreach($daftarBidang as $b)
                        <option value="{{ $b }}" {{ request('bidang') == $b ? 'selected' : '' }}>{{ $b }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary">Filter Status</label>
                <select name="status" class="form-select form-select-sm border-0 shadow-sm">
                    <option value="">-- Semua Status --</option>
                    @foreach(['Draft', 'Menunggu Verifikasi', 'Perlu Perbaikan', 'Proses Persetujuan PPK', 'Diajukan ke SAKTI', 'Belum Terbit SP2D', 'Dicairkan', 'Selesai'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-dark btn-sm w-100 rounded-pill shadow-sm">
                    <i class="bi bi-funnel"></i> Saring Data
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark rounded-top">
                    <tr>
                        <th class="ps-3">No Pengajuan</th>
                        <th>Tanggal</th>
                        <th>Bidang</th>
                        <th>Nama Kegiatan</th>
                        <th>Nilai Neto</th>
                        <th>No SPM</th>
                        <th>Status & Progress</th>
                        <th class="text-center">SPJ</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daftarPengajuan as $p)
                        @php
                            $progressPct = $p->overall_progress_percent;
                            $progressColor = $p->overall_progress_color;
                        @endphp
                        <tr>
                            <td class="ps-3"><strong>{{ $p->no_pengajuan }}</strong></td>
                            <td>{{ \Carbon\Carbon::parse($p->tgl_pengajuan)->format('d/m/Y') }}</td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded">{{ $p->bidang }}</span></td>
                            <td><div class="text-truncate" style="max-width: 180px;" title="{{ $p->nama_kegiatan }}">{{ $p->nama_kegiatan }}</div></td>
                            <td class="fw-bold text-success">Rp {{ number_format($p->nilai_neto, 0, ',', '.') }}</td>
                            <td>
                                @if($p->no_spm)
                                    <span class="badge bg-dark text-white px-2.5 py-1 rounded-pill small fw-semibold" title="Nomor SPM Terbit: {{ $p->no_spm }}">
                                        ⚫ {{ $p->no_spm }}
                                    </span>
                                @else
                                    <span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-2.5 py-1 rounded-pill small fw-semibold">
                                        🟡 Menunggu Upload
                                    </span>
                                @endif
                            </td>
                            <td style="min-width: 170px;">
                                <!-- Progress Bar di atas status tracking -->
                                <div class="d-flex align-items-center gap-2 mb-1.5" style="max-width: 160px;">
                                    <div class="progress flex-grow-1" style="height: 6px; border-radius: 3px;">
                                        <div class="progress-bar {{ $progressColor }}" role="progressbar" style="width: {{ $progressPct }}%;" aria-valuenow="{{ $progressPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="small text-muted fw-semibold" style="font-size: 10px;">{{ $progressPct }}%</span>
                                </div>
                                <!-- Status Badge di bawah progress bar -->
                                <div>
                                    @if($p->status == 'Draft')
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-50 px-2 py-1 rounded-pill"><i class="bi bi-pencil-square"></i> Draft</span>
                                    @elseif($p->status == 'Menunggu Verifikasi')
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-50 px-2 py-1 rounded-pill"><i class="bi bi-clock"></i> Verifikasi Keuangan</span>
                                    @elseif($p->status == 'Perlu Perbaikan')
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-50 px-2 py-1 rounded-pill"><i class="bi bi-exclamation-octagon"></i> Perlu Perbaikan</span>
                                    @elseif($p->status == 'Proses Persetujuan PPK')
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-50 px-2 py-1 rounded-pill"><i class="bi bi-person-check"></i> Proses Persetujuan PPK</span>
                                    @elseif($p->status == 'Diajukan ke SAKTI')
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-50 px-2 py-1 rounded-pill"><i class="bi bi-send-check"></i> Proses SAKTI</span>
                                    @elseif($p->status == 'Belum Terbit SP2D')
                                        <span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-50 px-2 py-1 rounded-pill"><i class="bi bi-hourglass-split"></i> Menunggu SP2D</span>
                                    @elseif($p->status == 'Dicairkan')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-50 px-2 py-1 rounded-pill"><i class="bi bi-cash-stack"></i> Sudah Cair</span>
                                    @elseif($p->status == 'Selesai')
                                        @if(($p->spj_status ?? 'Belum Upload') == 'SPJ Lengkap')
                                            <span class="badge bg-success text-white px-2 py-1 rounded-pill"><i class="bi bi-check-all"></i> Lengkap & Verified</span>
                                        @elseif(($p->spj_status ?? 'Belum Upload') == 'Menunggu Verifikasi SPJ')
                                            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50 px-2 py-1 rounded-pill"><i class="bi bi-clock"></i> Verifikasi SPJ</span>
                                        @elseif(($p->spj_status ?? 'Belum Upload') == 'Menunggu Upload Pemohon')
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-50 px-2 py-1 rounded-pill"><i class="bi bi-upload"></i> Upload SPJ Pemohon</span>
                                        @else
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-50 px-2 py-1 rounded-pill"><i class="bi bi-cloud-upload"></i> Upload SPM/SP2D</span>
                                            @if($p->verifikator_spm_deadline)
                                                @php
                                                    $isOverdueVerif = \Carbon\Carbon::now()->greaterThan(\Carbon\Carbon::parse($p->verifikator_spm_deadline));
                                                @endphp
                                                @if($isOverdueVerif)
                                                    <span class="badge bg-danger text-white px-2 py-0.5 rounded mt-1 d-block small" style="font-size: 10px;" title="Verifikator belum upload dokumen SPM/SP2D (Lewat 2 Hari)">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Terlambat SPM
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-2 py-0.5 rounded mt-1 d-block small" style="font-size: 10px;" title="Tenggat Verifikator upload dokumen SPM/SP2D (2 Hari)">
                                                        <i class="bi bi-clock-history me-1"></i> Deadline SPM 2 Hari
                                                    </span>
                                                @endif
                                            @endif
                                        @endif
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-50 px-2 py-1 rounded-pill">{{ $p->status }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                @if(($p->spj_status ?? 'Belum Upload') == 'SPJ Lengkap')
                                    <span class="text-success fs-5" title="SPJ Lengkap & Terverifikasi"><i class="bi bi-check-circle-fill"></i></span>
                                @elseif(($p->spj_status ?? 'Belum Upload') == 'Menunggu Verifikasi SPJ')
                                    <span class="text-warning fs-6" title="SPJ Menunggu Verifikasi"><i class="bi bi-clock-fill"></i></span>
                                    @if($p->spj_verifikator_deadline)
                                        @php
                                            $isOverdueVerifSPJ = \Carbon\Carbon::now()->greaterThan(\Carbon\Carbon::parse($p->spj_verifikator_deadline));
                                        @endphp
                                        @if($isOverdueVerifSPJ)
                                            <span class="badge bg-danger text-white px-2 py-0.5 rounded mt-1 d-block small" style="font-size: 10px;" title="Terlambat Verifikasi SPJ (Melebihi 2 Hari)">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Terlambat Verif SPJ
                                            </span>
                                        @else
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-50 px-2 py-0.5 rounded mt-1 d-block small" style="font-size: 10px;" title="Dalam Tenggat Verifikasi 2 Hari">
                                                <i class="bi bi-clock me-1"></i> Verif SPJ (<=2 Hari)
                                            </span>
                                        @endif
                                    @endif
                                @elseif(($p->spj_status ?? 'Belum Upload') == 'Menunggu Upload Pemohon')
                                    <span class="text-info fs-6" title="Menunggu Upload SPJ Pemohon"><i class="bi bi-upload"></i></span>
                                    @if($p->spj_deadline)
                                        @php
                                            $isOverduePemohon = \Carbon\Carbon::now()->greaterThan(\Carbon\Carbon::parse($p->spj_deadline));
                                        @endphp
                                        @if($isOverduePemohon)
                                            <span class="badge bg-danger text-white px-2 py-0.5 rounded mt-1 d-block small" style="font-size: 10px;" title="Terlambat Upload SPJ (Melebihi 5 Hari)">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Terlambat SPJ
                                            </span>
                                        @else
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-50 px-2 py-0.5 rounded mt-1 d-block small" style="font-size: 10px;" title="Dalam Tenggat 5 Hari">
                                                <i class="bi bi-clock me-1"></i> SPJ (<=5 Hari)
                                            </span>
                                        @endif
                                    @endif
                                @else
                                    <span class="text-muted small" title="Belum Upload SPJ">—</span>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('pengajuan.show', $p->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                    @if(Auth::user()->role == 'Admin Keuangan')
                                        <form action="{{ route('pengajuan.adminDelete', $p->id) }}" method="POST" onsubmit="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus pengajuan {{ $p->no_pengajuan }}? Tindakan ini tidak dapat dibatalkan!');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Hapus Pengajuan">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> Tidak ada data pengajuan pembayaran.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        <div class="mt-4 d-flex justify-content-center">
            {{ $daftarPengajuan->appends(request()->query())->links() }}
        </div>
    </div>
@endsection