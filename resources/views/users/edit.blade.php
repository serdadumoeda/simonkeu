{{-- File: resources/views/users/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card card-custom p-4 bg-white shadow-sm border-0">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-3 me-2">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Edit Akun Pengguna</h5>
                        <span class="text-muted small" style="font-size: 11px;">Mengubah data akun {{ $user->name }}</span>
                    </div>
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

                <form action="{{ route('users.update', $user->id) }}" method="POST" class="mt-2">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Pengguna / Login</label>
                        <input type="text" name="name" class="form-control rounded-3 border-light-subtle shadow-sm" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Alamat Email</label>
                        <input type="email" name="email" class="form-control rounded-3 border-light-subtle shadow-sm" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">No. WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="no_wa" class="form-control rounded-3 border-light-subtle shadow-sm" value="{{ old('no_wa', $user->no_wa) }}" placeholder="Contoh: 628123456789" required>
                        <div class="form-text text-muted" style="font-size: 11px;">Nomor WhatsApp aktif untuk penerimaan notifikasi berkas.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Password Baru (Opsional)</label>
                        <input type="password" name="password" class="form-control rounded-3 border-light-subtle shadow-sm" placeholder="Kosongkan jika tidak ingin mengubah password">
                        <div class="form-text text-muted" style="font-size: 11px;">Minimal 4 karakter jika ingin memperbarui kata sandi.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Role / Hak Akses</label>
                        <select name="role" class="form-select rounded-3 border-light-subtle shadow-sm" required>
                            <option value="Operator Bidang" {{ old('role', $user->role) == 'Operator Bidang' ? 'selected' : '' }}>Operator Bidang (Pemohon)</option>
                            <option value="Verifikator Keuangan" {{ old('role', $user->role) == 'Verifikator Keuangan' ? 'selected' : '' }}>Verifikator Keuangan</option>
                            <option value="PPK" {{ old('role', $user->role) == 'PPK' ? 'selected' : '' }}>PPK (Pejabat Pembuat Komitmen)</option>
                            <option value="Operator Pembayaran" {{ old('role', $user->role) == 'Operator Pembayaran' ? 'selected' : '' }}>Operator Pembayaran (SPM)</option>
                            <option value="Bendahara" {{ old('role', $user->role) == 'Bendahara' ? 'selected' : '' }}>Bendahara (SP2D / Pencairan)</option>
                            <option value="Kepala Balai" {{ old('role', $user->role) == 'Kepala Balai' ? 'selected' : '' }}>Kepala Balai (Executive / Pimpinan)</option>
                            <option value="Admin Keuangan" {{ old('role', $user->role) == 'Admin Keuangan' ? 'selected' : '' }}>Admin Keuangan (Superadmin)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Bidang Kerja / Instansi</label>
                        <select id="bidang_select" name="bidang" class="form-select rounded-3 border-light-subtle shadow-sm" required>
                            <option value="Umum" {{ old('bidang', $user->bidang) == 'Umum' ? 'selected' : '' }}>Umum</option>
                            <option value="Penyelenggara" {{ old('bidang', $user->bidang) == 'Penyelenggara' ? 'selected' : '' }}>Penyelenggara</option>
                            <option value="Produktivitas" {{ old('bidang', $user->bidang) == 'Produktivitas' ? 'selected' : '' }}>Produktivitas</option>
                            <option value="Pemberdayaan" {{ old('bidang', $user->bidang) == 'Pemberdayaan' ? 'selected' : '' }}>Pemberdayaan</option>
                            <option value="Satpel" {{ old('bidang', $user->bidang) == 'Satpel' ? 'selected' : '' }}>Satpel</option>
                            <option value="UPTD" {{ old('bidang', $user->bidang) == 'UPTD' ? 'selected' : '' }}>UPTD (Umum)</option>
                            <option value="Keuangan" {{ old('bidang', $user->bidang) == 'Keuangan' ? 'selected' : '' }}>Keuangan</option>
                            <option value="POKJA" {{ old('bidang', $user->bidang) == 'POKJA' ? 'selected' : '' }}>POKJA</option>
                            <option value="None" {{ old('bidang', $user->bidang) == 'None' ? 'selected' : '' }}>None (Untuk PPK / Admin / Bendahara)</option>
                            <option value="custom" {{ old('bidang', $user->bidang) && !in_array(old('bidang', $user->bidang), ['Umum', 'Penyelenggara', 'Produktivitas', 'Pemberdayaan', 'Satpel', 'UPTD', 'Keuangan', 'POKJA', 'None']) ? 'selected' : '' }}>Tulis UPTD / Satpel Spesifik...</option>
                        </select>
                    </div>

                    <div class="mb-4" id="custom_bidang_container" style="display: none;">
                        <label class="form-label small fw-semibold text-primary">Nama UPTD / Satpel Spesifik</label>
                        <input type="text" id="bidang_custom" class="form-control rounded-3 border-primary shadow-sm" placeholder="Contoh: UPTD Cilacap / Satpel A" value="{{ old('bidang', $user->bidang) && !in_array(old('bidang', $user->bidang), ['Umum', 'Penyelenggara', 'Produktivitas', 'Pemberdayaan', 'Satpel', 'UPTD', 'Keuangan', 'POKJA', 'None']) ? old('bidang', $user->bidang) : '' }}">
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-warning btn-sm text-white flex-fill rounded-pill py-2 fw-semibold shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm flex-fill rounded-pill py-2 fw-semibold shadow-sm">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
