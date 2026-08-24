{{-- File: resources/views/users/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-header bg-warning text-white fw-semibold">
                    Edit Akun Pengguna: {{ $user->name }}
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger small">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('users.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama (Untuk Login)</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password baru (Opsional)</label>
                            <input type="password" name="password" class="form-control">
                            <div class="form-text text-muted small">Biarkan kosong jika tidak ingin mengubah password.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Role / Hak Akses</label>
                            <select name="role" class="form-select" required>
                                <option value="Operator Bidang" {{ old('role', $user->role) == 'Operator Bidang' ? 'selected' : '' }}>Operator Bidang</option>
                                <option value="PIC UPTD" {{ old('role', $user->role) == 'PIC UPTD' ? 'selected' : '' }}>PIC UPTD</option>
                                <option value="Verifikator Keuangan" {{ old('role', $user->role) == 'Verifikator Keuangan' ? 'selected' : '' }}>Verifikator Keuangan</option>
                                <option value="PPK" {{ old('role', $user->role) == 'PPK' ? 'selected' : '' }}>PPK</option>
                                <option value="Operator Pembayaran" {{ old('role', $user->role) == 'Operator Pembayaran' ? 'selected' : '' }}>Operator Pembayaran</option>
                                <option value="Bendahara" {{ old('role', $user->role) == 'Bendahara' ? 'selected' : '' }}>Bendahara</option>
                                <option value="Admin Keuangan" {{ old('role', $user->role) == 'Admin Keuangan' ? 'selected' : '' }}>Admin Keuangan</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Bidang</label>
                            <select id="bidang_select" name="bidang" class="form-select" required>
                                <option value="Umum" {{ old('bidang', $user->bidang) == 'Umum' ? 'selected' : '' }}>Umum</option>
                                <option value="Penyelenggara" {{ old('bidang', $user->bidang) == 'Penyelenggara' ? 'selected' : '' }}>Penyelenggara</option>
                                <option value="Produktivitas" {{ old('bidang', $user->bidang) == 'Produktivitas' ? 'selected' : '' }}>Produktivitas</option>
                                <option value="Pemberdayaan" {{ old('bidang', $user->bidang) == 'Pemberdayaan' ? 'selected' : '' }}>Pemberdayaan</option>
                                <option value="Satpel" {{ old('bidang', $user->bidang) == 'Satpel' ? 'selected' : '' }}>Satpel</option>
                                <option value="UPTD" {{ old('bidang', $user->bidang) == 'UPTD' ? 'selected' : '' }}>UPTD (Umum)</option>
                                <option value="Keuangan" {{ old('bidang', $user->bidang) == 'Keuangan' ? 'selected' : '' }}>Keuangan</option>
                                <option value="POKJA" {{ old('bidang', $user->bidang) == 'POKJA' ? 'selected' : '' }}>POKJA</option>
                                <option value="None" {{ old('bidang', $user->bidang) == 'None' ? 'selected' : '' }}>None (Untuk PPK dll)</option>
                                <option value="custom" {{ old('bidang', $user->bidang) && !in_array(old('bidang', $user->bidang), ['Umum', 'Penyelenggara', 'Produktivitas', 'Pemberdayaan', 'Satpel', 'UPTD', 'Keuangan', 'POKJA', 'None']) ? 'selected' : '' }}>Tulis UPTD / Satpel Spesifik...</option>
                            </select>
                        </div>
                        <div class="mb-4" id="custom_bidang_container" style="display: none;">
                            <label class="form-label small fw-semibold text-primary">Nama UPTD / Satpel / Bidang Spesifik</label>
                            <input type="text" id="bidang_custom" class="form-control" placeholder="Contoh: UPTD Cilacap / Satpel A" value="{{ old('bidang', $user->bidang) && !in_array(old('bidang', $user->bidang), ['Umum', 'Penyelenggara', 'Produktivitas', 'Pemberdayaan', 'Satpel', 'UPTD', 'Keuangan', 'POKJA', 'None']) ? old('bidang', $user->bidang) : '' }}">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning text-white btn-sm flex-fill fw-semibold">Simpan Perubahan</button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm flex-fill fw-semibold">Batal</a>
                        </div>
                    </form>
                </div>
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

            bidangSelect.addEventListener('change', toggleCustomBidang);
            toggleCustomBidang(); // Run on load
        });
    </script>
@endsection
