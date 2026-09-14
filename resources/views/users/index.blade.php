{{-- File: resources/views/users/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-people-fill text-primary me-2"></i>Kelola Pengguna Sistem
            </h4>
            <p class="text-muted mb-0 small">Atur hak akses, tambah akun baru, dan intip tampilan aplikasi per role pengguna</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-arrow-left-short"></i> Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Form Tambah Pengguna -->
        <div class="col-lg-4">
            <div class="card card-custom p-4 bg-white shadow-sm border-0 h-100">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 me-2.5 me-2">
                        <i class="bi bi-person-plus-fill fs-5"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-0">Tambah Akun Baru</h5>
                </div>
                <hr class="text-muted opacity-25 my-2">

                @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm rounded-3 py-2 px-3 mb-3 small">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('users.store') }}" method="POST" class="mt-2">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Pengguna / Login</label>
                        <input type="text" name="name" class="form-control rounded-3 border-light-subtle shadow-sm" placeholder="Contoh: Budi Santoso" value="{{ old('name') }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Alamat Email</label>
                        <input type="email" name="email" class="form-control rounded-3 border-light-subtle shadow-sm" placeholder="email@bpvp.go.id" value="{{ old('email') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">No. WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="no_wa" class="form-control rounded-3 border-light-subtle shadow-sm" placeholder="Contoh: 628123456789" value="{{ old('no_wa', '628') }}" required>
                        <div class="form-text text-muted" style="font-size: 11px;">Nomor WA aktif (format: 628xxx).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Password</label>
                        <input type="password" name="password" class="form-control rounded-3 border-light-subtle shadow-sm" placeholder="Kata sandi minimal 4 karakter" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Role / Hak Akses</label>
                        <select name="role" class="form-select rounded-3 border-light-subtle shadow-sm" required>
                            <option value="Operator Bidang" {{ old('role') == 'Operator Bidang' ? 'selected' : '' }}>Operator Bidang (Pemohon)</option>
                            <option value="Verifikator Keuangan" {{ old('role') == 'Verifikator Keuangan' ? 'selected' : '' }}>Verifikator Keuangan</option>
                            <option value="PPK" {{ old('role') == 'PPK' ? 'selected' : '' }}>PPK (Pejabat Pembuat Komitmen)</option>
                            <option value="Operator Pembayaran" {{ old('role') == 'Operator Pembayaran' ? 'selected' : '' }}>Operator Pembayaran (SPM)</option>
                            <option value="Bendahara" {{ old('role') == 'Bendahara' ? 'selected' : '' }}>Bendahara (SP2D / Pencairan)</option>
                            <option value="Kepala Balai" {{ old('role') == 'Kepala Balai' ? 'selected' : '' }}>Kepala Balai (Executive / Pimpinan)</option>
                            <option value="Admin Keuangan" {{ old('role') == 'Admin Keuangan' ? 'selected' : '' }}>Admin Keuangan (Superadmin)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Bidang Kerja / Instansi</label>
                        <select id="bidang_select" name="bidang" class="form-select rounded-3 border-light-subtle shadow-sm" required>
                            <option value="Umum" {{ old('bidang') == 'Umum' ? 'selected' : '' }}>Umum</option>
                            <option value="Penyelenggara" {{ old('bidang') == 'Penyelenggara' ? 'selected' : '' }}>Penyelenggara</option>
                            <option value="Produktivitas" {{ old('bidang') == 'Produktivitas' ? 'selected' : '' }}>Produktivitas</option>
                            <option value="Pemberdayaan" {{ old('bidang') == 'Pemberdayaan' ? 'selected' : '' }}>Pemberdayaan</option>
                            <option value="Satpel" {{ old('bidang') == 'Satpel' ? 'selected' : '' }}>Satpel</option>
                            <option value="UPTD" {{ old('bidang') == 'UPTD' ? 'selected' : '' }}>UPTD (Umum)</option>
                            <option value="Keuangan" {{ old('bidang') == 'Keuangan' ? 'selected' : '' }}>Keuangan</option>
                            <option value="POKJA" {{ old('bidang') == 'POKJA' ? 'selected' : '' }}>POKJA</option>
                            <option value="None" {{ old('bidang') == 'None' ? 'selected' : '' }}>None (Untuk PPK / Admin / Bendahara / Kepala Balai)</option>
                            <option value="custom" {{ old('bidang') && !in_array(old('bidang'), ['Umum', 'Penyelenggara', 'Produktivitas', 'Pemberdayaan', 'Satpel', 'UPTD', 'Keuangan', 'POKJA', 'None']) ? 'selected' : '' }}>Tulis UPTD / Satpel Spesifik...</option>
                        </select>
                    </div>

                    <div class="mb-4" id="custom_bidang_container" style="display: none;">
                        <label class="form-label small fw-semibold text-primary">Nama UPTD / Satpel Spesifik</label>
                        <input type="text" id="bidang_custom" class="form-control rounded-3 border-primary shadow-sm" placeholder="Contoh: UPTD Cilacap / Satpel A" value="{{ old('bidang') && !in_array(old('bidang'), ['Umum', 'Penyelenggara', 'Produktivitas', 'Pemberdayaan', 'Satpel', 'UPTD', 'Keuangan', 'POKJA', 'None']) ? old('bidang') : '' }}">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill py-2 fw-semibold shadow-sm mt-2">
                        <i class="bi bi-person-check-fill me-1"></i> Simpan Akun Baru
                    </button>
                </form>
            </div>
        </div>

        <!-- Tabel Daftar Pengguna -->
        <div class="col-lg-8">
            <div class="card card-custom p-4 bg-white shadow-sm border-0 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center">
                        <div class="p-2 bg-dark bg-opacity-10 text-dark rounded-3 me-2">
                            <i class="bi bi-people fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Daftar Pengguna Sistem</h5>
                            <span class="text-muted small" style="font-size: 11px;">Total {{ count($users) }} akun terdaftar</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark rounded-top">
                            <tr>
                                <th class="ps-3">Pengguna</th>
                                <th>Role Akses</th>
                                <th>Bidang</th>
                                <th class="text-center pe-3">Aksi & Intip Akses</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
                                @php
                                    $roleColor = match($u->role) {
                                        'Admin Keuangan' => 'bg-purple text-white',
                                        'Kepala Balai' => 'bg-danger text-white border border-danger',
                                        'PPK' => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                                        'Verifikator Keuangan' => 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50',
                                        'Operator Bidang' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                        'Operator Pembayaran' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                                        'Bendahara' => 'bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25',
                                        default => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                                    };
                                @endphp
                                <tr>
                                    <td class="ps-3 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-2.5 me-2" style="width: 38px; height: 38px; font-size: 14px;">
                                                {{ strtoupper(substr($u->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <strong class="text-dark d-block mb-0">{{ $u->name }}</strong>
                                                <span class="text-muted small d-block" style="font-size: 11px;">{{ $u->email }}</span>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-0.5 mt-1" style="font-size: 10px;">
                                                    <i class="bi bi-whatsapp me-1"></i>{{ $u->no_wa ?? 'Belum Diisi' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $roleColor }} px-2.5 py-1 rounded-pill small fw-semibold" style="font-size: 11px;">
                                            {{ $u->role }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border px-2 py-1 rounded small">
                                            {{ $u->bidang ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="text-center pe-3">
                                        <div class="d-flex gap-1.5 gap-1 justify-content-center align-items-center flex-wrap">
                                            <!-- Tombol Impersonate / Intip Akses -->
                                            @if($u->id != Auth::id())
                                                <form action="{{ route('users.impersonate', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Mode Intip: Anda akan masuk dan melihat tampilan sistem sebagai {{ $u->name }} ({{ $u->role }}). Lanjutkan?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-purple rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 11px;" title="Intip tampilan & hak akses user ini">
                                                        <i class="bi bi-incognito me-1"></i> Intip Akses
                                                    </button>
                                                </form>
                                            @else
                                                <span class="badge bg-light text-muted border px-2 py-1 rounded-pill small" style="font-size: 10px;">Akun Anda</span>
                                            @endif

                                            <!-- Tombol Edit -->
                                            <a href="{{ route('users.edit', $u->id) }}" class="btn btn-sm btn-outline-warning rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 11px;" title="Edit Pengguna">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>

                                            <!-- Tombol Hapus -->
                                            @if($u->id != Auth::id())
                                                <form action="{{ route('users.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('PERINGATAN: Yakin ingin menghapus akun pengguna {{ $u->name }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" style="font-size: 11px;" title="Hapus Pengguna">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-purple {
            background-color: #6f42c1 !important;
        }
        .btn-outline-purple {
            color: #6f42c1;
            border-color: #6f42c1;
        }
        .btn-outline-purple:hover {
            color: #ffffff;
            background-color: #6f42c1;
            border-color: #6f42c1;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bidangSelect = document.getElementById('bidang_select');
            const customContainer = document.getElementById('custom_bidang_container');
            const bidangCustomInput = document.getElementById('bidang_custom');

            function toggleCustomBidang() {
                if (bidangSelect.value === 'custom') {
                    customContainer.style.display = 'block';
                    bidangCustomInput.setAttribute('name', 'bidang');
                    bidangCustomInput.required = true;
                    bidangSelect.removeAttribute('name');
                } else {
                    customContainer.style.display = 'none';
                    bidangCustomInput.removeAttribute('name');
                    bidangCustomInput.required = false;
                    bidangSelect.setAttribute('name', 'bidang');
                }
            }

            if (bidangSelect && customContainer && bidangCustomInput) {
                bidangSelect.addEventListener('change', toggleCustomBidang);
                toggleCustomBidang(); // Run on load
            }
        });
    </script>
@endsection