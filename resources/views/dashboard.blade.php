{{-- File: resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', Auth::user()->role == 'Kepala Balai' ? 'Dashboard Monitoring Kepala Balai' : 'Dashboard Monitoring')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Custom UI/UX Precision & Rhythm Rules */
        .dark-select option {
            background-color: #1e293b !important;
            color: #ffffff !important;
        }
        .step-badge {
            font-size: 10px;
            letter-spacing: 0.04em;
            padding: 3px 9px;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }
        .kpi-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            padding: 1.25rem 1.25rem !important;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }
        .icon-box {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            flex-shrink: 0;
        }
        .sla-guide-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .sla-guide-card {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 16px;
            transition: all 0.25s ease;
            min-width: 180px;
        }
        .sla-guide-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .sla-guide-card.sla-tepat {
            border-left: 4px solid #10b981;
            background: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 100%);
        }
        .sla-guide-card.sla-proses {
            border-left: 4px solid #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #f8fafc 100%);
        }
        .sla-guide-card.sla-terlambat {
            border-left: 4px solid #ef4444;
            background: linear-gradient(135deg, #fef2f2 0%, #f8fafc 100%);
        }
        .sla-guide-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .sla-guide-icon.icon-tepat {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
        }
        .sla-guide-icon.icon-proses {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
        }
        .sla-guide-icon.icon-terlambat {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
        }
        .sla-guide-label {
            font-weight: 700;
            font-size: 12px;
            line-height: 1.2;
        }
        .sla-guide-desc {
            font-size: 10.5px;
            color: #64748b;
            line-height: 1.3;
        }
    </style>

    <!-- 1. EXECUTIVE HERO HEADER WITH SLA OVERALL SCORE & FILTERS -->
    <div class="card border-0 shadow-sm mb-4 text-white overflow-hidden" style="border-radius: 18px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-xl-6 col-lg-5">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <h4 class="fw-bold text-white mb-0">
                            @if(Auth::user()->role == 'Kepala Balai')
                                <i class="bi bi-award-fill text-warning me-2"></i>Dashboard Kepala Balai
                            @else
                                <i class="bi bi-speedometer2 text-warning me-2"></i>Dashboard Monitoring Keuangan
                            @endif
                        </h4>
                    </div>
                    <p class="text-white-50 small mb-3" style="font-size: 12.5px; max-width: 580px;">
                        Pemantauan Ketepatan Waktu Layanan (SLA) Penerbitan SPM, Penatausahaan SPJ, & Performa Stakeholders / UPTD
                    </p>

                    <!-- SLA Overall Performance Pill Badge -->
                    <div class="d-inline-flex align-items-center gap-3 px-3 py-2 rounded-pill" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15);">
                        <span class="text-white-50 small fw-medium" style="font-size: 11.5px;">
                            Skor Kepatuhan SLA Keseluruhan:
                            <i class="bi bi-info-circle text-warning ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Indeks standar waktu layanan gabungan: SLA Pencairan Keuangan (Max 7 Hari / 1 Minggu dari Pemohon s/d Bendahara Cair), SPM Keuangan (2hr), SPJ Pemohon (5hr), & Verifikasi SPJ (2hr)"></i>
                        </span>
                        <span class="fs-5 fw-extrabold @if($overallSlaScore >= 90) text-success @elseif($overallSlaScore >= 75) text-info @elseif($overallSlaScore >= 60) text-warning @else text-danger @endif me-1">{{ $overallSlaScore }}%</span>
                        <div class="vr bg-white bg-opacity-25" style="height: 16px;"></div>
                        @if($overallSlaScore >= 90)
                            <span class="badge bg-success text-white rounded-pill px-2.5 py-1 small fw-bold" style="font-size: 10.5px;">
                                <i class="bi bi-shield-check me-1"></i> Sangat Baik (On Track)
                            </span>
                        @elseif($overallSlaScore >= 75)
                            <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 small fw-bold" style="font-size: 10.5px;">
                                <i class="bi bi-check-circle me-1"></i> Baik (Memuaskan)
                            </span>
                        @elseif($overallSlaScore >= 60)
                            <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 small fw-bold" style="font-size: 10.5px;">
                                <i class="bi bi-exclamation-circle me-1"></i> Cukup (Perlu Ditingkatkan)
                            </span>
                        @else
                            <span class="badge bg-danger text-white rounded-pill px-2.5 py-1 small fw-bold" style="font-size: 10.5px;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Perlu Perhatian (Kritis)
                            </span>
                        @endif
                    </div>
                </div>

                <div class="col-xl-6 col-lg-7">
                    <!-- Toolbar Filters -->
                    <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                        <!-- Filter Tahun -->
                        <div class="input-group input-group-sm shadow-sm rounded-pill border-0 bg-white bg-opacity-10 overflow-hidden" style="width: 105px; flex: 0 0 auto; backdrop-filter: blur(4px);" title="Pilih Tahun Anggaran">
                            <span class="input-group-text bg-transparent text-white-50 border-0 px-2 fw-bold small" style="font-size: 11px;">TA</span>
                            <select name="tahun" class="form-select form-select-sm border-0 bg-transparent fw-bold text-warning px-2 dark-select" onchange="this.form.submit()">
                                @foreach($daftarTahun as $t)
                                    <option value="{{ $t }}" {{ ($tahunAktif ?? date('Y')) == $t ? 'selected' : '' }}>
                                        {{ $t }}
                                    </option>
                                @endforeach
                                <option value="semua" {{ ($tahunAktif ?? '') == 'semua' ? 'selected' : '' }}>Semua</option>
                            </select>
                        </div>

                        <!-- Filter Bidang / UPTD (tersembunyi untuk Operator Bidang karena sudah auto-filter) -->
                        @if(Auth::user()->role != 'Operator Bidang')
                        <div class="input-group input-group-sm shadow-sm rounded-pill border-0 bg-white bg-opacity-10 overflow-hidden" style="min-width: 190px; flex: 1 1 auto; max-width: 240px; backdrop-filter: blur(4px);" title="Pilih Unit Kerja / UPTD">
                            <span class="input-group-text bg-transparent text-white-50 border-0 px-2 fw-bold small"><i class="bi bi-building"></i></span>
                            <select name="bidang" class="form-select form-select-sm border-0 bg-transparent fw-bold text-white px-2 text-truncate dark-select" onchange="this.form.submit()">
                                <option value="">Semua Bidang & UPTD</option>
                                @foreach($daftarBidang as $b)
                                    @php
                                        $isUptdOption = str_contains(strtoupper($b), 'UPTD');
                                    @endphp
                                    <option value="{{ $b }}" {{ ($filterBidang ?? '') == $b ? 'selected' : '' }}>
                                        {{ $isUptdOption ? '🏢 UPTD: ' : '🏛️ ' }}{{ $b }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <!-- Filter Status SLA -->
                        <div class="input-group input-group-sm shadow-sm rounded-pill border-0 bg-white bg-opacity-10 overflow-hidden" style="width: 145px; flex: 0 0 auto; backdrop-filter: blur(4px);" title="Filter Ketepatan Waktu">
                            <span class="input-group-text bg-transparent text-white-50 border-0 px-2 fw-bold small"><i class="bi bi-stopwatch"></i></span>
                            <select name="ketepatan" class="form-select form-select-sm border-0 bg-transparent fw-bold text-white px-2 dark-select" onchange="this.form.submit()">
                                <option value="">Semua Status SLA</option>
                                <option value="tepat_waktu" {{ ($filterKetepatan ?? '') == 'tepat_waktu' ? 'selected' : '' }}>🟢 Tepat Waktu</option>
                                <option value="terlambat" {{ ($filterKetepatan ?? '') == 'terlambat' ? 'selected' : '' }}>🔴 Terlambat</option>
                            </select>
                        </div>

                        <a href="{{ route('pengajuan.index', ['tahun' => $tahunAktif]) }}" class="btn btn-warning btn-sm rounded-pill px-3 shadow-sm fw-bold text-dark" title="Lihat daftar seluruh dokumen pengajuan">
                            <i class="bi bi-list-task me-1"></i> Data
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 1.4 PETA KEMACETAN LAYANAN (BOTTLENECK HEATMAP WIDGET) -->
    @if(isset($heatmapBottleneck) && count($heatmapBottleneck) > 0)
        <div class="card card-custom border-0 shadow-sm mb-4 bg-white" style="border-radius: 18px; border-left: 5px solid {{ $hasBottleneckAlert ? '#dc3545' : '#0d6efd' }} !important;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold text-dark mb-0">
                                🔥 Peta Kemacetan Layanan <span class="text-muted fw-normal fs-6">(Bottleneck Heatmap)</span>
                            </h5>
                            @if($hasBottleneckAlert)
                                <span class="badge bg-danger text-white rounded-pill px-2.5 py-1 small animate__animated animate__pulse animate__infinite">
                                    <i class="bi bi-exclamation-octagon-fill me-1"></i> Hambatan Terdeteksi
                                </span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1 small fw-bold">
                                    <i class="bi bi-check-circle-fill me-1"></i> Layanan Lancar
                                </span>
                            @endif
                        </div>
                        <p class="text-muted small mb-0" style="font-size: 12px;">Pemantauan beban antrean dokumen pending di setiap meja kerja (workflow stages)</p>
                    </div>

                    @if($hasBottleneckAlert && !empty($bottleneckAlertMessage))
                        <div class="alert alert-danger mb-0 py-1.5 px-3 rounded-pill small fw-semibold">
                            {{ $bottleneckAlertMessage }}
                        </div>
                    @endif
                </div>

                <div class="row g-3">
                    @foreach($heatmapBottleneck as $stg)
                        <div class="col-md-4 col-lg-2">
                            <div class="p-3 rounded-4 border shadow-sm h-100 position-relative bg-light bg-opacity-50 border-{{ $stg['level'] == 'danger' ? 'danger' : ($stg['level'] == 'warning' ? 'warning' : 'light-subtle') }}" style="border-top: 4px solid {{ $stg['level'] == 'danger' ? '#dc3545' : ($stg['level'] == 'warning' ? '#ffc107' : '#198754') }} !important;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-{{ $stg['level'] == 'danger' ? 'danger' : ($stg['level'] == 'warning' ? 'warning text-dark' : 'success') }} rounded-pill px-2 py-0.5 small fw-bold" style="font-size: 10px;">
                                        {{ $stg['status_text'] }}
                                    </span>
                                    <i class="bi {{ $stg['icon'] }} fs-5 text-{{ $stg['level'] == 'danger' ? 'danger' : ($stg['level'] == 'warning' ? 'warning' : 'primary') }}"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-0" style="font-size: 12.5px;">{{ $stg['label'] }}</h6>
                                <span class="text-muted extra-small d-block mb-2" style="font-size: 10.5px;">PJ: {{ $stg['actor'] }}</span>
                                <div class="d-flex align-items-baseline justify-content-between">
                                    <span class="fs-4 fw-extrabold text-{{ $stg['level'] == 'danger' ? 'danger' : ($stg['level'] == 'warning' ? 'dark' : 'success') }}">
                                        {{ $stg['count'] }}
                                    </span>
                                    <span class="text-muted small" style="font-size: 11px;">berkas</span>
                                </div>
                                @if($stg['is_overdue'])
                                    <div class="mt-1 pt-1 border-top border-light-subtle text-danger small fw-semibold" style="font-size: 10px;">
                                        ⚠️ Terlama: {{ $stg['overdue_days'] }} hari
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- 1.5 ROLE-BASED TO-DO LIST (ACTION INBOX FOR NON-EXECUTIVE ROLES) -->
    @if(Auth::user()->role != 'Kepala Balai' && Auth::user()->role != 'Superadmin' && !empty($todoTitle))
        <div class="card card-custom border-0 shadow-sm mb-4 bg-white" style="border-radius: 18px; border-top: 4px solid #2563eb !important;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-box text-primary me-2" style="background: rgba(37, 99, 235, 0.1);">
                            <i class="bi bi-card-checklist fs-4"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h5 class="fw-bold text-dark mb-0">
                                    📌 {{ $todoTitle }}
                                </h5>
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2.5 py-0.5 small fw-bold" style="font-size: 11px;">
                                    {{ count($todoItems) }} Total Tugas
                                </span>
                            </div>
                            <span class="text-muted small" style="font-size: 12px;">{{ $todoSubtitle }}</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if(count($todoItems) > 3)
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold shadow-sm" id="btnToggleAllTodo" onclick="toggleTodoListExpand()">
                                <i class="bi bi-grid-fill me-1"></i> Lihat Semua ({{ count($todoItems) }} Tugas)
                            </button>
                        @endif

                        @if(Auth::user()->role == 'Operator Bidang')
                            <a href="{{ route('pengajuan.create') }}" class="btn btn-primary btn-sm rounded-pill px-3 py-1.5 fw-bold shadow-sm">
                                <i class="bi bi-plus-circle me-1"></i> Buat Pengajuan Baru
                            </a>
                        @endif
                    </div>
                </div>

                @if(count($todoItems) > 0)
                    <div class="row g-3">
                        @foreach($todoItems as $index => $todo)
                            @php
                                $isHidden = $index >= 3;
                            @endphp
                            <div class="col-md-6 col-lg-4 {{ $isHidden ? 'extra-todo-card d-none' : '' }}">
                                <div class="card border border-light-subtle shadow-sm h-100 p-3 rounded-4 kpi-card" style="background: #ffffff; border-radius: 14px;">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge {{ $todo['badge_class'] }} px-2 py-1 rounded-pill small fw-bold" style="font-size: 10.5px;">
                                            {{ $todo['badge'] }}
                                        </span>
                                        <span class="text-muted small" style="font-size: 11px;">
                                            <i class="bi bi-clock me-1"></i>{{ $todo['date'] }}
                                        </span>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 13.5px;">
                                        <i class="bi {{ $todo['icon'] }} text-{{ $todo['type'] }} me-1"></i> {{ $todo['title'] }}
                                    </h6>
                                    <p class="text-secondary small mb-3 flex-grow-1" style="font-size: 12px; line-height: 1.4;">
                                        {{ $todo['desc'] }}
                                    </p>
                                    <a href="{{ $todo['action_url'] }}" class="btn btn-sm btn-outline-{{ $todo['type'] }} w-100 rounded-pill fw-bold" style="font-size: 11.5px;">
                                        {{ $todo['action_label'] }} <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if(count($todoItems) > 3)
                        <div class="text-center pt-3 border-top mt-3">
                            <button type="button" class="btn btn-light text-primary btn-sm rounded-pill px-4 fw-bold border shadow-sm" onclick="toggleTodoListExpand()">
                                <span id="txtToggleTodoLabel"><i class="bi bi-chevron-down me-1"></i> Tampilkan {{ count($todoItems) - 3 }} Tugas Lainnya</span>
                            </button>
                        </div>
                    @endif
                @else
                    <div class="text-center py-4 bg-light rounded-4 border border-dashed">
                        <i class="bi bi-check-circle text-success display-6 mb-2 d-block"></i>
                        <h6 class="fw-bold text-dark mb-1">Tidak Ada Antrean Tugas (Semua Selesai)</h6>
                        <p class="text-muted small mb-0" style="font-size: 12px;">Saat ini tidak ada dokumen yang memerlukan tindakan dari peran Anda.</p>
                    </div>
                @endif
            </div>
        </div>

        <script>
            function toggleTodoListExpand() {
                const extraCards = document.querySelectorAll('.extra-todo-card');
                const btnHeader = document.getElementById('btnToggleAllTodo');
                const txtLabel = document.getElementById('txtToggleTodoLabel');
                
                let isNowExpanded = false;
                extraCards.forEach(card => {
                    if (card.classList.contains('d-none')) {
                        card.classList.remove('d-none');
                        isNowExpanded = true;
                    } else {
                        card.classList.add('d-none');
                        isNowExpanded = false;
                    }
                });

                if (isNowExpanded) {
                    if (btnHeader) btnHeader.innerHTML = '<i class="bi bi-chevron-up me-1"></i> Sembunyikan (3 Teratas)';
                    if (txtLabel) txtLabel.innerHTML = '<i class="bi bi-chevron-up me-1"></i> Sembunyikan Tugas Tambahan';
                } else {
                    if (btnHeader) btnHeader.innerHTML = '<i class="bi bi-grid-fill me-1"></i> Lihat Semua ({{ count($todoItems) }} Tugas)';
                    if (txtLabel) txtLabel.innerHTML = '<i class="bi bi-chevron-down me-1"></i> Tampilkan {{ count($todoItems) - 3 }} Tugas Lainnya';
                }
            }
        </script>
    @endif

    <!-- 2. 4 EXECUTIVE KPI METRIC CARDS GRID (PERFECT SPACING & RHYTHM) -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Pengajuan & Anggaran Neto -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-custom bg-white shadow-sm border-0 h-100 d-flex flex-column justify-content-between kpi-card" style="border-radius: 16px; border-left: 4px solid #2563eb !important;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-uppercase text-secondary fw-bold" style="font-size: 11px; letter-spacing: 0.03em;">
                            <i class="bi bi-folder2-open text-primary me-1.5"></i> Total Pengajuan
                        </span>
                        <div class="icon-box text-primary" style="background: rgba(37, 99, 235, 0.1);">
                            <i class="bi bi-cash-stack fs-5"></i>
                        </div>
                    </div>

                    <div class="my-2">
                        <div class="d-flex align-items-baseline gap-2 mb-1">
                            <h2 class="fw-extrabold text-dark mb-0" style="font-weight: 800; font-size: 2.1rem; line-height: 1;">
                                {{ $totalPengajuan }}
                            </h2>
                            <span class="small fw-semibold text-muted">Dokumen SPJ</span>
                        </div>
                        <div class="text-muted small" style="font-size: 11px;">Tahun Anggaran {{ $tahunAktif == 'semua' ? 'Semua' : $tahunAktif }}</div>
                    </div>
                </div>

                <div class="p-2.5 px-3 rounded-3 d-flex align-items-center justify-content-between mt-3" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                    <span class="small fw-semibold text-success" style="font-size: 11.5px;"><i class="bi bi-wallet2 me-1.5"></i> Total Neto:</span>
                    <strong class="text-success fw-bold" style="font-size: 12.5px;">Rp {{ number_format($totalNilaiBulanIni, 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>

        <!-- Card 2: Langkah 1 - SPM 2 Hari (Keuangan) -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-custom bg-white shadow-sm border-0 h-100 d-flex flex-column justify-content-between kpi-card" style="border-radius: 16px; border-left: 4px solid #f59e0b !important;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="step-badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50">Langkah 1</span>
                            <span class="text-uppercase text-secondary fw-bold" style="font-size: 11px; letter-spacing: 0.02em;">
                                SPM (Keuangan)
                            </span>
                        </div>
                        <div class="icon-box text-warning" style="background: rgba(245, 158, 11, 0.12);" title="Batas Waktu Upload SPM: Maksimal 2 Hari Kerja Sejak Permohonan Masuk">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                    </div>

                    <div class="my-2">
                        <div class="d-flex align-items-baseline gap-2 mb-1">
                            <h2 class="fw-extrabold text-dark mb-0" style="font-weight: 800; font-size: 2.1rem; line-height: 1;">
                                {{ $spmTepatWaktuCount }}
                            </h2>
                            <span class="small fw-semibold text-muted">Tepat Waktu</span>
                        </div>
                        <div class="text-muted small" style="font-size: 11px;">
                            <i class="bi bi-info-circle me-1"></i>Target Durasi: Max 2 Hari Kerja
                        </div>
                    </div>
                </div>

                <div class="p-2 px-3 rounded-3 d-flex align-items-center justify-content-between mt-3" style="background: #f8fafc; border: 1px solid #f1f5f9;">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill small fw-semibold" style="font-size: 10.5px;">
                        🟢 {{ $spmTepatWaktuCount }} Tepat Waktu
                    </span>
                    @if($spmTerlambatCount > 0)
                        <span class="badge bg-danger text-white px-2.5 py-1 rounded-pill small fw-bold" style="font-size: 10.5px;">
                            🔴 {{ $spmTerlambatCount }} Terlambat
                        </span>
                    @else
                        <span class="badge bg-success text-white px-2.5 py-1 rounded-pill small fw-bold" style="font-size: 10.5px;">
                            <i class="bi bi-shield-check me-1"></i> 100% SLA
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Card 3: Langkah 2 - SPJ 5 Hari (Pemohon/UPTD) -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-custom bg-white shadow-sm border-0 h-100 d-flex flex-column justify-content-between kpi-card" style="border-radius: 16px; border-left: 4px solid #10b981 !important;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="step-badge bg-success bg-opacity-10 text-success border border-success border-opacity-50">Langkah 2</span>
                            <span class="text-uppercase text-secondary fw-bold" style="font-size: 11px; letter-spacing: 0.02em;" title="SPJ Pemohon / UPTD">
                                SPJ Pemohon / UPTD
                            </span>
                        </div>
                        <div class="icon-box text-success" style="background: rgba(16, 185, 129, 0.12);" title="Batas Waktu Upload SPJ: Maksimal 5 Hari Kerja Sejak SPM Terbit">
                            <i class="bi bi-upload fs-5"></i>
                        </div>
                    </div>

                    <div class="my-2">
                        <div class="d-flex align-items-baseline gap-2 mb-1">
                            <h2 class="fw-extrabold text-dark mb-0" style="font-weight: 800; font-size: 2.1rem; line-height: 1;">
                                {{ $spjPemohonTepatWaktuCount }}
                            </h2>
                            <span class="small fw-semibold text-muted">Tepat Waktu</span>
                        </div>
                        <div class="text-muted small" style="font-size: 11px;">
                            <i class="bi bi-info-circle me-1"></i>Target Durasi: Max 5 Hari Kerja
                        </div>
                    </div>
                </div>

                <div class="p-2 px-3 rounded-3 d-flex align-items-center justify-content-between mt-3" style="background: #f8fafc; border: 1px solid #f1f5f9;">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill small fw-semibold" style="font-size: 10.5px;">
                        🟢 {{ $spjPemohonTepatWaktuCount }} Tepat Waktu
                    </span>
                    @if($spjPemohonTerlambatCount > 0)
                        <span class="badge bg-danger text-white px-2.5 py-1 rounded-pill small fw-bold" style="font-size: 10.5px;">
                            🔴 {{ $spjPemohonTerlambatCount }} Terlambat
                        </span>
                    @else
                        <span class="badge bg-success text-white px-2.5 py-1 rounded-pill small fw-bold" style="font-size: 10.5px;">
                            <i class="bi bi-shield-check me-1"></i> 100% SLA
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Card 4: Langkah 3 - Verifikasi SPJ 2 Hari -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-custom bg-white shadow-sm border-0 h-100 d-flex flex-column justify-content-between kpi-card" style="border-radius: 16px; border-left: 4px solid #06b6d4 !important;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="step-badge bg-info bg-opacity-10 text-info border border-info border-opacity-50">Langkah 3</span>
                            <span class="text-uppercase text-secondary fw-bold" style="font-size: 11px; letter-spacing: 0.02em;">
                                Verifikasi SPJ
                            </span>
                        </div>
                        <div class="icon-box text-info" style="background: rgba(6, 182, 212, 0.12);" title="Batas Waktu Verifikasi SPJ: Maksimal 2 Hari Kerja Sejak SPJ Diunggah">
                            <i class="bi bi-check2-all fs-5"></i>
                        </div>
                    </div>

                    <div class="my-2">
                        <div class="d-flex align-items-baseline gap-2 mb-1">
                            <h2 class="fw-extrabold text-dark mb-0" style="font-weight: 800; font-size: 2.1rem; line-height: 1;">
                                {{ $spjVerifikasiTepatWaktuCount }}
                            </h2>
                            <span class="small fw-semibold text-muted">Tepat Waktu</span>
                        </div>
                        <div class="text-muted small" style="font-size: 11px;">
                            <i class="bi bi-info-circle me-1"></i>Target Durasi: Max 2 Hari Kerja
                        </div>
                    </div>
                </div>

                <div class="p-2 px-3 rounded-3 d-flex align-items-center justify-content-between mt-3" style="background: #f8fafc; border: 1px solid #f1f5f9;">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill small fw-semibold" style="font-size: 10.5px;">
                        🟢 {{ $spjVerifikasiTepatWaktuCount }} Tepat Waktu
                    </span>
                    @if($spjVerifikasiTerlambatCount > 0)
                        <span class="badge bg-danger text-white px-2.5 py-1 rounded-pill small fw-bold" style="font-size: 10.5px;">
                            🔴 {{ $spjVerifikasiTerlambatCount }} Terlambat
                        </span>
                    @else
                        <span class="badge bg-success text-white px-2.5 py-1 rounded-pill small fw-bold" style="font-size: 10.5px;">
                            <i class="bi bi-shield-check me-1"></i> 100% SLA
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 3. STATUS TRACKING PELAKSANAAN BERKAS (UNIFIED HARMONIOUS LIFECYCLE GRID) -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark mb-0">
            <i class="bi bi-activity text-primary me-2"></i>Status Tahapan Pelacakan Lifecycle Berkas SPJ
        </h6>
        <span class="text-muted small" style="font-size: 11.5px;"><i class="bi bi-arrow-right me-1"></i>Alur Berkas dari Pengajuan hingga Pencairan Selesai</span>
    </div>

    <div class="mb-4 overflow-x-auto pb-1" style="scrollbar-width: thin;">
        <div class="text-center d-grid gap-2" style="grid-template-columns: repeat(8, minmax(0, 1fr)); min-width: 820px;">
            <div>
                <div class="card card-custom bg-white p-3 h-100 border-0 border-top border-secondary border-4 shadow-sm kpi-card" style="border-radius: 14px;">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary mx-auto mb-2 rounded-pill fw-bold" style="font-size: 9.5px; width: fit-content;">Draft</span>
                    <div class="text-secondary mb-2" style="font-size: 22px;">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <h4 class="fw-extrabold text-secondary mb-1" style="font-weight: 800;">{{ $draftCount }}</h4>
                    <span class="small fw-semibold text-secondary text-truncate d-block" style="font-size: 11px;">Draft Pemohon</span>
                </div>
            </div>
            <div>
                <div class="card card-custom bg-white p-3 h-100 border-0 border-top border-warning border-4 shadow-sm kpi-card" style="border-radius: 14px;">
                    <span class="badge bg-warning bg-opacity-10 text-dark mx-auto mb-2 rounded-pill fw-bold" style="font-size: 9.5px; width: fit-content;">Tahap 1</span>
                    <div class="text-warning mb-2" style="font-size: 22px;">
                        <i class="bi bi-hourglass-top"></i>
                    </div>
                    <h4 class="fw-extrabold text-warning mb-1" style="font-weight: 800;">{{ $menungguVerifikasi }}</h4>
                    <span class="small fw-semibold text-secondary text-truncate d-block" style="font-size: 11px;">Menunggu Verif</span>
                </div>
            </div>
            <div>
                <div class="card card-custom bg-white p-3 h-100 border-0 border-top border-danger border-4 shadow-sm kpi-card" style="border-radius: 14px;">
                    <span class="badge bg-danger bg-opacity-10 text-danger mx-auto mb-2 rounded-pill fw-bold" style="font-size: 9.5px; width: fit-content;">Revisi</span>
                    <div class="text-danger mb-2" style="font-size: 22px;">
                        <i class="bi bi-x-octagon"></i>
                    </div>
                    <h4 class="fw-extrabold text-danger mb-1" style="font-weight: 800;">{{ $perluPerbaikan }}</h4>
                    <span class="small fw-semibold text-secondary text-truncate d-block" style="font-size: 11px;">Perlu Perbaikan</span>
                </div>
            </div>
            <div>
                <div class="card card-custom bg-white p-3 h-100 border-0 border-top border-info border-4 shadow-sm kpi-card" style="border-radius: 14px;">
                    <span class="badge bg-info bg-opacity-10 text-info mx-auto mb-2 rounded-pill fw-bold" style="font-size: 9.5px; width: fit-content;">Tahap 2</span>
                    <div class="text-info mb-2" style="font-size: 22px;">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <h4 class="fw-extrabold text-info mb-1" style="font-weight: 800;">{{ $prosesPersetujuanPpk }}</h4>
                    <span class="small fw-semibold text-secondary text-truncate d-block" style="font-size: 11px;">Proses PPK</span>
                </div>
            </div>
            <div>
                <div class="card card-custom bg-white p-3 h-100 border-0 border-top border-purple border-4 shadow-sm kpi-card" style="border-radius: 14px; border-top-color: #6f42c1 !important;">
                    <span class="badge bg-purple bg-opacity-10 text-purple mx-auto mb-2 rounded-pill fw-bold" style="font-size: 9.5px; width: fit-content; color: #6f42c1; background: rgba(111, 66, 193, 0.1);">Tahap 3</span>
                    <div class="mb-2" style="font-size: 22px; color: #6f42c1;">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h4 class="fw-extrabold mb-1" style="font-weight: 800; color: #6f42c1;">{{ $penerbitanSpp + $sppMenungguTtd }}</h4>
                    <span class="small fw-semibold text-secondary text-truncate d-block" style="font-size: 11px;">Terbit SPP</span>
                </div>
            </div>
            <div>
                <div class="card card-custom bg-white p-3 h-100 border-0 border-top border-primary border-4 shadow-sm kpi-card" style="border-radius: 14px;">
                    <span class="badge bg-primary bg-opacity-10 text-primary mx-auto mb-2 rounded-pill fw-bold" style="font-size: 9.5px; width: fit-content;">Tahap 4</span>
                    <div class="text-primary mb-2" style="font-size: 22px;">
                        <i class="bi bi-send-check"></i>
                    </div>
                    <h4 class="fw-extrabold text-primary mb-1" style="font-weight: 800;">{{ $diajukanSakti }}</h4>
                    <span class="small fw-semibold text-secondary text-truncate d-block" style="font-size: 11px;">SAKTI/SPM</span>
                </div>
            </div>
            <div>
                <div class="card card-custom bg-white p-3 h-100 border-0 border-top border-dark border-4 shadow-sm kpi-card" style="border-radius: 14px;">
                    <span class="badge bg-dark bg-opacity-10 text-dark mx-auto mb-2 rounded-pill fw-bold" style="font-size: 9.5px; width: fit-content;">Tahap 5</span>
                    <div class="text-dark mb-2" style="font-size: 22px;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <h4 class="fw-extrabold text-dark mb-1" style="font-weight: 800;">{{ $menungguSp2d }}</h4>
                    <span class="small fw-semibold text-secondary text-truncate d-block" style="font-size: 11px;">Menunggu SP2D</span>
                </div>
            </div>
            <div>
                <div class="card card-custom bg-white p-3 h-100 border-0 border-top border-success border-4 shadow-sm kpi-card" style="border-radius: 14px;">
                    <span class="badge bg-success bg-opacity-10 text-success mx-auto mb-2 rounded-pill fw-bold" style="font-size: 9.5px; width: fit-content;">Selesai</span>
                    <div class="text-success mb-2" style="font-size: 22px;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h4 class="fw-extrabold mb-1 text-success" style="font-weight: 800;">{{ $dicairkan }}</h4>
                    <span class="small fw-semibold text-secondary text-truncate d-block" style="font-size: 11px;">Cair & Verified</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. PERFORMA STAKEHOLDER & KETEPATAN SPJ PER BIDANG/UPTD -->
    <div class="row mb-4 g-4">
        <!-- Pihak Utama (Role Level Overview) — Hanya tampil untuk role non-Operator Bidang -->
        @if(Auth::user()->role != 'Operator Bidang')
        <div class="col-lg-5">
            <div class="card card-custom shadow-sm border-0 p-4 bg-white h-100" style="border-radius: 18px;">
                <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                    <div class="icon-box text-dark me-3" style="background: #f1f5f9;">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Performa Stakeholder Utama</h6>
                        <span class="text-muted small" style="font-size: 11.5px;">Aktivitas & Keterlibatan Per Role Jabatan</span>
                    </div>
                </div>

                <div class="list-group list-group-flush">
                    <!-- Verifikator Keuangan -->
                    <div class="list-group-item px-0 py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                            <span class="fw-bold text-dark small"><i class="bi bi-shield-check text-warning me-2"></i> Verifikator Keuangan</span>
                            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50 px-2.5 py-1 rounded-pill small" style="font-size: 10px;">{{ $stakeholderMetrics['verifikator']['total_user'] }} Akun</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted ms-4" style="font-size: 11.5px;">
                            <span>Dokumen Diverifikasi: <strong class="text-dark">{{ $stakeholderMetrics['verifikator']['verified_total'] }}</strong></span>
                            <span>Upload SPM Tepat: <strong class="text-success">{{ $stakeholderMetrics['verifikator']['spm_tepat_waktu'] }}</strong></span>
                        </div>
                    </div>

                    <!-- PPK -->
                    <div class="list-group-item px-0 py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                            <span class="fw-bold text-dark small"><i class="bi bi-person-check-fill text-primary me-2"></i> Pejabat Pembuat Komitmen (PPK)</span>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1 rounded-pill small" style="font-size: 10px;">{{ $stakeholderMetrics['ppk']['total_user'] }} Akun</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted ms-4" style="font-size: 11.5px;">
                            <span>Total Persetujuan Berkas: <strong class="text-primary">{{ $stakeholderMetrics['ppk']['approved_total'] }}</strong></span>
                            <span>Status PPK Active: <strong class="text-dark">Sesuai Alur</strong></span>
                        </div>
                    </div>

                    <!-- Operator Pembayaran (SPP & SPM) -->
                    <div class="list-group-item px-0 py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                            <span class="fw-bold text-dark small"><i class="bi bi-send-check-fill text-info me-2"></i> Operator Pembayaran (SPP & SPM)</span>
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2.5 py-1 rounded-pill small" style="font-size: 10px;">{{ $stakeholderMetrics['operator_pembayaran']['total_user'] }} Akun</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted ms-4" style="font-size: 11.5px;">
                            <span>SPP & SPM Diterbitkan: <strong class="text-info">{{ $stakeholderMetrics['operator_pembayaran']['spm_issued_total'] }}</strong></span>
                            <span>Role: <strong class="text-success">Operator Pembayaran</strong></span>
                        </div>
                    </div>

                    <!-- Bendahara Pengeluaran -->
                    <div class="list-group-item px-0 py-3">
                        <div class="d-flex justify-content-between align-items-center mb-1.5">
                            <span class="fw-bold text-dark small"><i class="bi bi-wallet2 text-success me-2"></i> Bendahara Pengeluaran</span>
                            <span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 px-2.5 py-1 rounded-pill small" style="font-size: 10px;">{{ $stakeholderMetrics['bendahara']['total_user'] }} Akun</span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted ms-4" style="font-size: 11.5px;">
                            <span>Dokumen Dicairkan: <strong class="text-success">{{ $stakeholderMetrics['bendahara']['cair_total'] }}</strong></span>
                            <span>Penyerahan Uang: <strong class="text-dark">{{ $stakeholderMetrics['bendahara']['penyerahan_total'] }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @endif

        <!-- Ketepatan SPJ Pemohon Per Bidang & UPTD -->
        <div class="{{ Auth::user()->role == 'Operator Bidang' ? 'col-lg-12' : 'col-lg-7' }}">
            <div class="card card-custom shadow-sm border-0 p-4 bg-white h-100" style="border-radius: 18px;">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="icon-box text-success me-3" style="background: #ecfdf5;">
                            <i class="bi bi-building-check fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Ketepatan SPJ Pemohon Per Bidang & UPTD</h6>
                            <span class="text-muted small" style="font-size: 11.5px;">Evaluasi Kepatuhan SLA 5 Hari Sejak SPM Terbit</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0" style="font-size: 12.5px;">
                        <thead class="table-light">
                            <tr>
                                <th class="py-2.5 ps-2">Bidang / UPTD Instansi</th>
                                <th class="text-center py-2.5">Pengajuan</th>
                                <th class="text-center py-2.5">SPJ Uploaded</th>
                                <th class="text-center py-2.5">Keterlambatan</th>
                                <th class="text-end pe-3 py-2.5">Kepatuhan SLA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bidangPerformance as $bPerf)
                                <tr>
                                    <td class="fw-bold text-dark py-2.5 ps-2">
                                        @if($bPerf['is_uptd'])
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-0.5 me-1.5" style="font-size: 10px;"><i class="bi bi-building"></i> UPTD</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-0.5 me-1.5" style="font-size: 10px;">PUSAT</span>
                                        @endif
                                        {{ $bPerf['bidang'] }}
                                    </td>
                                    <td class="text-center fw-semibold py-2.5">{{ $bPerf['total_pengajuan'] }}</td>
                                    <td class="text-center text-success fw-bold py-2.5">{{ $bPerf['spj_uploaded'] }}</td>
                                    <td class="text-center py-2.5">
                                        @if($bPerf['spj_terlambat'] > 0)
                                            <span class="badge bg-danger text-white rounded-pill px-2.5 py-0.5"><i class="bi bi-exclamation-triangle"></i> {{ $bPerf['spj_terlambat'] }} Terlambat</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3 py-2.5">
                                        @php
                                            $rateColor = match(true) {
                                                $bPerf['timeliness_rate'] >= 90 => 'bg-success text-white',
                                                $bPerf['timeliness_rate'] >= 70 => 'bg-warning text-dark',
                                                default => 'bg-danger text-white',
                                            };
                                        @endphp
                                        <span class="badge {{ $rateColor }} px-2.5 py-1 rounded-pill fw-bold" style="font-size: 10.5px;">{{ $bPerf['timeliness_rate'] }}%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Belum ada data bidang kerja atau UPTD pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. TABEL PEMANTAUAN RINCI MONITORING SPM (PERFECTLY SPACED SLA GUIDE & TABLE) -->
    <div class="card card-custom shadow-sm border-0 p-4 bg-white mb-4" style="border-radius: 18px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 pb-3 border-bottom">
            <div>
                <h6 class="fw-bold text-dark mb-1">
                    <i class="bi bi-stopwatch-fill text-primary me-2"></i>Pemantauan Rinci SPM & Status Ketepatan Waktu (SLA)
                </h6>
                <span class="text-muted small" style="font-size: 11.5px;">Menampilkan {{ count($daftarSpmMonitoring) }} dokumen terpantau di sistem</span>
            </div>

            <!-- Integrated SLA Legend Cards & Live Search Input with Status Filter -->
            <div class="d-flex flex-column gap-3 mt-2 w-100">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <span class="fw-bold text-dark" style="font-size: 13px;"><i class="bi bi-info-circle-fill text-primary me-1"></i> Panduan Status SLA</span>
                    
                    <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                        <!-- Filter Status SLA -->
                        <div class="input-group input-group-sm shadow-sm rounded-pill overflow-hidden border border-light-subtle" style="max-width: 190px;">
                            <span class="input-group-text bg-white border-0 text-muted px-2.5"><i class="bi bi-funnel"></i></span>
                            <select id="dashboard_spm_status_filter" class="form-select border-0 px-1 fw-medium text-dark" style="font-size: 11.5px;" onchange="filterDashboardSpmTable()">
                                <option value="">Semua SLA</option>
                                <option value="tepat">🟢 Tepat Waktu</option>
                                <option value="proses">🟡 Dalam Proses</option>
                                <option value="terlambat">🔴 Terlambat SLA</option>
                            </select>
                        </div>

                        <!-- Live Search Input -->
                        <div class="input-group input-group-sm shadow-sm rounded-pill overflow-hidden border border-light-subtle" style="max-width: 260px;">
                            <span class="input-group-text bg-white border-0 text-muted px-3"><i class="bi bi-search"></i></span>
                            <input type="text" id="dashboard_spm_search" class="form-control border-0 px-2" placeholder="Cari No Pengajuan, Kegiatan, SPM..." onkeyup="filterDashboardSpmTable()">
                        </div>
                    </div>
                </div>
                <div class="sla-guide-container">
                    <div class="sla-guide-card sla-tepat">
                        <div class="sla-guide-icon icon-tepat">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <div class="sla-guide-label text-success">🟢 Tepat Waktu</div>
                            <div class="sla-guide-desc">Selesai ≤ Batas SLA</div>
                        </div>
                    </div>
                    <div class="sla-guide-card sla-proses">
                        <div class="sla-guide-icon icon-proses">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <div class="sla-guide-label" style="color: #b45309;">🟡 Dalam Proses</div>
                            <div class="sla-guide-desc">Berjalan ≤ Batas SLA</div>
                        </div>
                    </div>
                    <div class="sla-guide-card sla-terlambat">
                        <div class="sla-guide-icon icon-terlambat">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <div class="sla-guide-label text-danger">🔴 Terlambat</div>
                            <div class="sla-guide-desc">Melebihi Batas Waktu SLA</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="spm_monitoring_table" style="font-size: 12.5px;">
                <thead class="table-dark rounded-top" style="background-color: #0f172a;">
                    <tr>
                        <th class="ps-3 py-3">No Pengajuan</th>
                        <th class="py-3">Bidang / UPTD</th>
                        <th class="py-3">Nama Kegiatan</th>
                        <th class="py-3">Nilai Neto</th>
                        <th class="py-3">No SPM dan Status</th>
                        <th class="text-center py-3" title="SLA Utama: Pemohon mengajukan s/d Bendahara menyerahkan uang / SP2D cair (Max 7 Hari / 1 Minggu)">SLA Pencairan (7hr) <i class="bi bi-info-circle text-warning ms-1"></i></th>
                        <th class="text-center py-3" title="Batas waktu Verifikator Keuangan upload SPM (Max 2 Hari Kerja)">Upload SPM (2hr) <i class="bi bi-info-circle text-white-50 ms-1"></i></th>
                        <th class="text-center py-3" title="Batas waktu Pemohon / UPTD upload berkas SPJ (Max 5 Hari Kerja)">SPJ Pemohon (5hr) <i class="bi bi-info-circle text-white-50 ms-1"></i></th>
                        <th class="text-center py-3" title="Batas waktu Verifikator Keuangan verifikasi SPJ (Max 2 Hari Kerja)">Verif SPJ (2hr) <i class="bi bi-info-circle text-white-50 ms-1"></i></th>
                        <th class="text-center pe-3 py-3">Status SLA Overall</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daftarSpmMonitoring as $item)
                        @php
                            $p = $item['pengajuan'];
                        @endphp
                        @php
                            $slaStatusAttr = $item['is_overall_terlambat'] ? 'terlambat' : ($item['is_overall_tepat_waktu'] ? 'tepat' : 'proses');
                        @endphp
                        <tr class="spm-data-row" data-sla-status="{{ $slaStatusAttr }}">
                            <td class="ps-3 py-3">
                                <a href="{{ route('pengajuan.show', $p->id) }}" class="fw-bold text-primary text-decoration-none" title="Lihat Detail Berkas">
                                    {{ $p->no_pengajuan }}
                                </a>
                            </td>
                            <td class="py-3">
                                @if($item['is_uptd'])
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded" style="font-size: 10px;"><i class="bi bi-building"></i> {{ $p->bidang }}</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded" style="font-size: 10px;">{{ $p->bidang }}</span>
                                @endif
                            </td>
                            <td class="py-3">
                                <div class="text-truncate" style="max-width: 170px;" title="{{ $p->nama_kegiatan }}">{{ $p->nama_kegiatan }}</div>
                            </td>
                            <td class="fw-bold text-success py-3">Rp {{ number_format($p->nilai_neto, 0, ',', '.') }}</td>
                            <td class="py-3">
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

                            <!-- SLA Utama Pencairan Keuangan (Max 7 Hari / 1 Minggu Pemohon -> Bendahara Cair) -->
                            <td class="text-center py-3">
                                @if($item['pencairan_sla_status'] == 'Tepat Waktu')
                                    <span class="badge bg-success text-white px-2.5 py-1 rounded-pill" title="Pencairan selesai <= 7 hari (1 minggu)" style="font-size: 10.5px;"><i class="bi bi-check-circle-fill"></i> 🟢 Tepat ({{ round($item['durasi_pencairan_hari']) }}hr)</span>
                                @elseif($item['pencairan_sla_status'] == 'Terlambat')
                                    <span class="badge bg-danger text-white px-2.5 py-1 rounded-pill" title="Pencairan melebihi batas 7 hari (1 minggu)" style="font-size: 10.5px;"><i class="bi bi-exclamation-triangle-fill"></i> 🔴 Terlambat ({{ round($item['durasi_pencairan_hari']) }}hr)</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50 px-2.5 py-1 rounded-pill" title="Proses pencairan berjalan dalam batas 7 hari (1 minggu)" style="font-size: 10.5px;"><i class="bi bi-clock-history"></i> 🟡 Proses ({{ round($item['durasi_pencairan_hari']) }}hr)</span>
                                @endif
                            </td>

                            <!-- SLA Upload SPM (2 Hari Verifikator Keuangan) -->
                            <td class="text-center py-3">
                                @if($item['spm_sla_status'] == 'Tepat Waktu')
                                    <span class="badge bg-success text-white px-2.5 py-1 rounded-pill" title="SPM diunggah tepat waktu <= 2 hari kerja" style="font-size: 10.5px;"><i class="bi bi-check-circle-fill"></i> 🟢 Tepat</span>
                                @elseif($item['spm_sla_status'] == 'Terlambat')
                                    <span class="badge bg-danger text-white px-2.5 py-1 rounded-pill" title="Melebihi tenggat 2 hari upload SPM" style="font-size: 10.5px;"><i class="bi bi-exclamation-triangle-fill"></i> 🔴 Terlambat</span>
                                @elseif($item['spm_sla_status'] == 'Berjalan')
                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50 px-2.5 py-1 rounded-pill" title="Sedang berjalan dalam batas 2 hari" style="font-size: 10.5px;"><i class="bi bi-clock-history"></i> 🟡 Dalam Proses</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            <!-- SLA Upload SPJ Pemohon (5 Hari) -->
                            <td class="text-center py-3">
                                @if($item['spj_pemohon_sla_status'] == 'Tepat Waktu')
                                    <span class="badge bg-success text-white px-2.5 py-1 rounded-pill" title="SPJ Pemohon diunggah <= 5 hari" style="font-size: 10.5px;"><i class="bi bi-check-circle-fill"></i> 🟢 Tepat</span>
                                @elseif($item['spj_pemohon_sla_status'] == 'Terlambat')
                                    <span class="badge bg-danger text-white px-2.5 py-1 rounded-pill" title="SPJ Pemohon melebihi 5 hari" style="font-size: 10.5px;"><i class="bi bi-exclamation-triangle-fill"></i> 🔴 Terlambat</span>
                                @elseif($item['spj_pemohon_sla_status'] == 'Berjalan')
                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50 px-2.5 py-1 rounded-pill" title="Sedang berjalan dalam batas 5 hari" style="font-size: 10.5px;"><i class="bi bi-clock-history"></i> 🟡 Dalam Proses</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            <!-- SLA Verifikasi SPJ (2 Hari Verifikator) -->
                            <td class="text-center py-3">
                                @if($item['spj_verifikator_sla_status'] == 'Tepat Waktu')
                                    <span class="badge bg-success text-white px-2.5 py-1 rounded-pill" title="Verifikasi SPJ diselesaikan <= 2 hari" style="font-size: 10.5px;"><i class="bi bi-check-circle-fill"></i> 🟢 Tepat</span>
                                @elseif($item['spj_verifikator_sla_status'] == 'Terlambat')
                                    <span class="badge bg-danger text-white px-2.5 py-1 rounded-pill" title="Verifikasi SPJ melebihi 2 hari" style="font-size: 10.5px;"><i class="bi bi-exclamation-triangle-fill"></i> 🔴 Terlambat</span>
                                @elseif($item['spj_verifikator_sla_status'] == 'Berjalan')
                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50 px-2.5 py-1 rounded-pill" title="Sedang berjalan dalam batas 2 hari" style="font-size: 10.5px;"><i class="bi bi-clock-history"></i> 🟡 Dalam Proses</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            <!-- Status SLA Overall Badge -->
                            <td class="text-center pe-3 py-3">
                                @if($item['is_overall_terlambat'])
                                    <span class="badge bg-danger text-white px-2.5 py-1 rounded-pill fw-bold" style="font-size: 10.5px;">
                                        <i class="bi bi-x-circle-fill me-1"></i> Terlambat SLA
                                    </span>
                                @elseif($item['is_overall_tepat_waktu'])
                                    <span class="badge bg-success text-white px-2.5 py-1 rounded-pill fw-bold" style="font-size: 10.5px;">
                                        <i class="bi bi-shield-check me-1"></i> Tepat Waktu
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1 rounded-pill" style="font-size: 10.5px;">
                                        <i class="bi bi-arrow-repeat me-1"></i> Dalam Proses
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="spm_empty_row">
                            <td colspan="10" class="text-center text-muted py-4">Tidak ada data SPM terpantau yang sesuai dengan filter pencarian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- SLA Table Pagination Controls (10 Rows Per Page) -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-3 border-top">
            <div class="text-muted small fw-medium" id="spm_table_pagination_info" style="font-size: 12px;">
                Menampilkan 1 - {{ min(10, count($daftarSpmMonitoring)) }} dari {{ count($daftarSpmMonitoring) }} data
            </div>
            <nav aria-label="SLA Table Pagination">
                <ul class="pagination pagination-sm mb-0 rounded-pill shadow-sm overflow-hidden" id="spm_table_pagination_controls">
                    <!-- Dynamic Pagination Buttons Generated via JS -->
                </ul>
            </nav>
        </div>
    </div>

    <!-- 6. GRAFIK SEBARAN VOLUME & DISTRIBUSI STATUS -->
    <div class="row mb-4 g-4">
        <div class="col-lg-8">
            <div class="card card-custom shadow-sm border-0 p-4 bg-white h-100" style="border-radius: 18px;">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="bi bi-bar-chart-line text-primary me-2"></i>Sebaran Volume Pengajuan per Bidang Kerja & Satpel
                </h6>
                <div style="position: relative; max-height: 280px;">
                    <canvas id="grafikBidang" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-custom shadow-sm border-0 p-4 bg-white h-100" style="border-radius: 18px;">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="bi bi-pie-chart text-info me-2"></i>Distribusi Status Tahapan Berkas
                </h6>
                <div style="position: relative; max-height: 280px;" class="d-flex justify-content-center">
                    <canvas id="grafikStatus" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Bootstrap tooltips for layperson microcopy
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        });

        // Client-side live search, SLA status filter & 10-row pagination
        let currentSpmPage = 1;
        const spmRowsPerPage = 10;

        function filterDashboardSpmTable(resetPage = true) {
            if (resetPage) currentSpmPage = 1;

            const searchInput = document.getElementById('dashboard_spm_search');
            const statusFilter = document.getElementById('dashboard_spm_status_filter');
            const table = document.getElementById('spm_monitoring_table');
            if (!table) return;
            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const allRows = Array.from(tbody.querySelectorAll('tr.spm-data-row'));
            const emptyRow = document.getElementById('spm_empty_row');

            const searchKeyword = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const selectedStatus = statusFilter ? statusFilter.value.toLowerCase().trim() : '';

            let matchingRows = [];

            allRows.forEach(row => {
                const text = (row.textContent || row.innerText).toLowerCase();
                const rowStatus = (row.getAttribute('data-sla-status') || '').toLowerCase();

                const matchesSearch = (searchKeyword === '' || text.indexOf(searchKeyword) > -1);
                const matchesStatus = (selectedStatus === '' || rowStatus === selectedStatus);

                if (matchesSearch && matchesStatus) {
                    matchingRows.push(row);
                } else {
                    row.style.display = 'none';
                }
            });

            if (emptyRow) {
                emptyRow.style.display = (matchingRows.length === 0) ? '' : 'none';
            }

            // Pagination calculation
            const totalMatching = matchingRows.length;
            const totalPages = Math.ceil(totalMatching / spmRowsPerPage) || 1;
            if (currentSpmPage > totalPages) currentSpmPage = totalPages;

            const startIndex = (currentSpmPage - 1) * spmRowsPerPage;
            const endIndex = startIndex + spmRowsPerPage;

            matchingRows.forEach((row, idx) => {
                if (idx >= startIndex && idx < endIndex) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Update Pagination Info
            const infoElem = document.getElementById('spm_table_pagination_info');
            if (infoElem) {
                if (totalMatching === 0) {
                    infoElem.textContent = 'Menampilkan 0 data';
                } else {
                    const startNum = startIndex + 1;
                    const endNum = Math.min(endIndex, totalMatching);
                    infoElem.textContent = `Menampilkan ${startNum} - ${endNum} dari ${totalMatching} data`;
                }
            }

            renderSpmPaginationControls(totalPages);
        }

        function goToSpmPage(page) {
            currentSpmPage = page;
            filterDashboardSpmTable(false);
        }

        function renderSpmPaginationControls(totalPages) {
            const controlsElem = document.getElementById('spm_table_pagination_controls');
            if (!controlsElem) return;
            controlsElem.innerHTML = '';

            if (totalPages <= 1) return;

            // Prev Button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentSpmPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link py-1 px-2.5" href="javascript:void(0)" onclick="goToSpmPage(${currentSpmPage - 1})"><i class="bi bi-chevron-left"></i></a>`;
            controlsElem.appendChild(prevLi);

            // Page Buttons
            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentSpmPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link py-1 px-2.5" href="javascript:void(0)" onclick="goToSpmPage(${i})">${i}</a>`;
                controlsElem.appendChild(li);
            }

            // Next Button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentSpmPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link py-1 px-2.5" href="javascript:void(0)" onclick="goToSpmPage(${currentSpmPage + 1})"><i class="bi bi-chevron-right"></i></a>`;
            controlsElem.appendChild(nextLi);
        }

        document.addEventListener('DOMContentLoaded', function() {
            filterDashboardSpmTable(true);
        });

        const labelBidang = {!! json_encode($labelBidang) !!};
        const angkaBidang = {!! json_encode($angkaBidang) !!};

        const ctx = document.getElementById('grafikBidang').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labelBidang,
                datasets: [{
                    label: 'Jumlah Dokumen Pengajuan',
                    data: angkaBidang,
                    backgroundColor: [
                        'rgba(30, 60, 114, 0.85)',
                        'rgba(40, 167, 69, 0.85)',
                        'rgba(255, 193, 7, 0.85)',
                        'rgba(23, 162, 184, 0.85)',
                        'rgba(111, 66, 193, 0.85)',
                        'rgba(253, 126, 20, 0.85)'
                    ],
                    borderColor: [
                        'rgba(30, 60, 114, 1)',
                        'rgba(40, 167, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(23, 162, 184, 1)',
                        'rgba(111, 66, 193, 1)',
                        'rgba(253, 126, 20, 1)'
                    ],
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // Grafik Doughnut Distribusi Status
        const ctxStatus = document.getElementById('grafikStatus').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Draft', 'Menunggu Verif', 'Perlu Perbaikan', 'Proses PPK', 'Terbit SPP', 'SAKTI/SPM', 'Menunggu SP2D', 'Cair & Verified'],
                datasets: [{
                    data: [
                        {{ $draftCount }},
                        {{ $menungguVerifikasi }},
                        {{ $perluPerbaikan }},
                        {{ $prosesPersetujuanPpk }},
                        {{ $penerbitanSpp + $sppMenungguTtd }},
                        {{ $diajukanSakti }},
                        {{ $menungguSp2d }},
                        {{ $dicairkan }}
                    ],
                    backgroundColor: [
                        '#6c757d',
                        '#ffc107',
                        '#dc3545',
                        '#0dcaf0',
                        '#6f42c1',
                        '#0d6efd',
                        '#212529',
                        '#198754'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { size: 10 }
                        }
                    }
                }
            }
        });
    </script>
@endsection