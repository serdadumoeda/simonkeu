{{-- File: resources/views/users/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white fw-semibold">Tambah Pengguna Baru</div>
                <div class="card-body">
                    @if(session('success') && !session('error'))
                        <div class="alert alert-success small">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger small">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('users.store') }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="small fw-semibold">Nama (Untuk Login)</label>
                            <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm" value="{{ old('email') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="small fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="small fw-semibold">Role / Hak Akses</label>
                            <select name="role" class="form-select form-select-sm" required>
                                <option value="Operator Bidang" {{ old('role') == 'Operator Bidang' ? 'selected' : '' }}>Operator Bidang</option>
                                <option value="PIC UPTD" {{ old('role') == 'PIC UPTD' ? 'selected' : '' }}>PIC UPTD</option>
                                <option value="Verifikator Keuangan" {{ old('role') == 'Verifikator Keuangan' ? 'selected' : '' }}>Verifikator Keuangan</option>
                                <option value="PPK" {{ old('role') == 'PPK' ? 'selected' : '' }}>PPK</option>
                                <option value="Operator Pembayaran" {{ old('role') == 'Operator Pembayaran' ? 'selected' : '' }}>Operator Pembayaran</option>
                                <option value="Bendahara" {{ old('role') == 'Bendahara' ? 'selected' : '' }}>Bendahara</option>
                                <option value="Admin Keuangan" {{ old('role') == 'Admin Keuangan' ? 'selected' : '' }}>Admin Keuangan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-semibold">Bidang</label>
                            <select id="bidang_select" name="bidang" class="form-select form-select-sm" required>
                                <option value="Umum" {{ old('bidang') == 'Umum' ? 'selected' : '' }}>Umum</option>
                                <option value="Penyelenggara" {{ old('bidang') == 'Penyelenggara' ? 'selected' : '' }}>Penyelenggara</option>
                                <option value="Produktivitas" {{ old('bidang') == 'Produktivitas' ? 'selected' : '' }}>Produktivitas</option>
                                <option value="Pemberdayaan" {{ old('bidang') == 'Pemberdayaan' ? 'selected' : '' }}>Pemberdayaan</option>
                                <option value="Satpel" {{ old('bidang') == 'Satpel' ? 'selected' : '' }}>Satpel</option>
                                <option value="UPTD" {{ old('bidang') == 'UPTD' ? 'selected' : '' }}>UPTD (Umum)</option>
                                <option value="Keuangan" {{ old('bidang') == 'Keuangan' ? 'selected' : '' }}>Keuangan</option>
                                <option value="POKJA" {{ old('bidang') == 'POKJA' ? 'selected' : '' }}>POKJA</option>
                                <option value="None" {{ old('bidang') == 'None' ? 'selected' : '' }}>None (Untuk PPK dll)</option>
                                <option value="custom" {{ old('bidang') && !in_array(old('bidang'), ['Umum', 'Penyelenggara', 'Produktivitas', 'Pemberdayaan', 'Satpel', 'UPTD', 'Keuangan', 'POKJA', 'None']) ? 'selected' : '' }}>Tulis UPTD / Satpel Spesifik...</option>
                            </select>
                        </div>
                        <div class="mb-3" id="custom_bidang_container" style="display: none;">
                            <label class="small fw-semibold text-primary">Nama UPTD / Satpel / Bidang Spesifik</label>
                            <input type="text" id="bidang_custom" class="form-control form-control-sm" placeholder="Contoh: UPTD Cilacap / Satpel A" value="{{ old('bidang') && !in_array(old('bidang'), ['Umum', 'Penyelenggara', 'Produktivitas', 'Pemberdayaan', 'Satpel', 'UPTD', 'Keuangan', 'POKJA', 'None']) ? old('bidang') : '' }}">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Simpan Akun</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white fw-semibold">Daftar Pengguna Sistem</div>
                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3 small mb-2" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show m-3 small mb-2" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0 text-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nama</th>
                                    <th>Role</th>
                                    <th>Bidang</th>
                                    <th width="25%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $u)
                                    <tr>
                                        <td><strong>{{ $u->name }}</strong><br><small class="text-muted">{{ $u->email }}</small></td>
                                        <td><span class="badge bg-secondary">{{ $u->role }}</span></td>
                                        <td>{{ $u->bidang }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('users.edit', $u->id) }}" class="btn btn-warning btn-xs py-1 px-2 btn-sm text-white fw-semibold">Edit</a>
                                            
                                            @if($u->id != Auth::id())
                                                <form action="{{ route('users.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna {{ $u->name }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-xs py-1 px-2 btn-sm fw-semibold">Hapus</button>
                                                </form>
                                            @else
                                                <button class="btn btn-secondary btn-xs py-1 px-2 btn-sm fw-semibold" disabled title="Anda tidak bisa menghapus diri sendiri">Hapus</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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