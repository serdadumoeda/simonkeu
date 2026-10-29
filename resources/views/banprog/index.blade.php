{{-- File: resources/views/banprog/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Banprog LPKS')

@section('content')
    @include('banprog._style')

    <div class="bp-hero mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 position-relative" style="z-index:1">
            <div>
                <span class="badge bg-warning text-dark rounded-pill px-3 mb-2"><i class="bi bi-buildings me-1"></i> Skema Banprog</span>
                <h4 class="fw-bold mb-1 text-white">Bantuan Program LPKS Binaan</h4>
                <p class="mb-0 small text-white-50">Pencairan 2 termin: Termin 1 (70%) → Laporan Pelaksanaan → Termin 2 (30%)</p>
            </div>
            @if(Auth::user()->role == 'Operator Bidang' && Auth::user()->akses_banprog)
                <a href="{{ route('banprog.create') }}" id="btn-buat-banprog" class="btn btn-warning fw-semibold rounded-pill px-4 shadow">
                    <i class="bi bi-plus-circle me-1"></i> Buat Pengajuan Banprog
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['Total Pengajuan', $stats['total'], 'bi-folder2-open', '#1e3c72'],
                ['Dalam Proses', $stats['proses'], 'bi-hourglass-split', '#0d6efd'],
                ['Menunggu Laporan', $stats['laporan'], 'bi-journal-text', '#fd7e14'],
                ['Selesai', $stats['selesai'], 'bi-patch-check-fill', '#198754'],
            ];
        @endphp
        @foreach($cards as [$label, $val, $icon, $color])
            <div class="col-6 col-lg-3">
                <div class="card card-custom bp-stat p-3 h-100" style="--c: {{ $color }}">
                    <div class="d-flex align-items-center">
                        <div class="bp-stat-icon me-3"><i class="bi {{ $icon }}"></i></div>
                        <div>
                            <div class="text-muted small">{{ $label }}</div>
                            <div class="fs-4 fw-bold">{{ $val }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card card-custom p-4 bg-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <h6 class="fw-bold mb-0"><i class="bi bi-list-task text-primary me-1"></i> Daftar Pengajuan Banprog</h6>
            <span class="small text-muted">Total nilai: <strong class="text-dark">Rp {{ number_format($stats['nilai'], 0, ',', '.') }}</strong></span>
        </div>

        <form method="GET" class="row g-2 mb-3 bg-light p-3 rounded-3">
            <div class="col-md-6">
                <input type="text" name="search" id="search-banprog" value="{{ request('search') }}" class="form-control form-control-sm border-0 shadow-sm" placeholder="Cari no. banprog, nama LPKS, kab/kota, kejuruan...">
            </div>
            <div class="col-md-4">
                <select name="status" id="filter-status-banprog" class="form-select form-select-sm border-0 shadow-sm">
                    <option value="">-- Semua Status --</option>
                    @foreach($statusList as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-dark btn-sm w-100"><i class="bi bi-funnel"></i> Saring</button>
                @if(request()->hasAny(['search','status']))
                    <a href="{{ route('banprog.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">No. / LPKS</th>
                        <th>Kejuruan</th>
                        <th class="text-end">Nilai Banprog</th>
                        <th style="min-width:200px">Status & Progres</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daftar as $b)
                        <tr>
                            <td class="ps-3 py-3">
                                <span class="badge bg-light text-secondary border mb-1">{{ $b->no_banprog }}</span>
                                <strong class="d-block text-dark">{{ $b->nama_lpks }}</strong>
                                <span class="small text-muted"><i class="bi bi-geo-alt"></i> {{ $b->kabupaten_kota }} · {{ $b->bidang }}</span>
                            </td>
                            <td class="small">{{ $b->kejuruan }}</td>
                            <td class="text-end small">
                                <strong>Rp {{ number_format($b->nilai_banprog, 0, ',', '.') }}</strong>
                                <div class="text-muted" style="font-size:11px">
                                    T1 {{ $b->tgl_cair_t1 ? '✓' : '' }} {{ number_format($b->nilai_termin_1, 0, ',', '.') }} ·
                                    T2 {{ $b->tgl_cair_t2 ? '✓' : '' }} {{ number_format($b->nilai_termin_2, 0, ',', '.') }}
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $b->badge_class }} rounded-pill px-2 py-1 mb-1">{{ $b->status }}</span>
                                <div class="progress bp-progress"><div class="progress-bar" style="width: {{ $b->progress }}%"></div></div>
                            </td>
                            <td class="text-center pe-3">
                                <a href="{{ route('banprog.show', $b->id) }}" class="btn btn-sm btn-custom-primary rounded-pill px-3"><i class="bi bi-eye"></i> Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-2 d-block opacity-50"></i>Belum ada pengajuan Banprog.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $daftar->links() }}</div>
    </div>
@endsection
