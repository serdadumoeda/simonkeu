{{-- File: resources/views/anggaran/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Ketersediaan Anggaran')

@section('content')
    <div class="card shadow border-0 p-4" style="border-radius: 18px;">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h4 class="fw-bold text-dark mb-1"><i class="bi bi-wallet2 text-primary me-2"></i>Informasi Ketersediaan Anggaran (DIPA)</h4>
                <p class="text-muted small mb-0">Pastikan ketersediaan pagu anggaran mencukupi sebelum mengajukan berkas pembayaran.</p>
            </div>

            {{-- Form Update Link Khusus Admin --}}
            @if(Auth::user()->role == 'Admin Keuangan')
                <form action="{{ route('anggaran.upload') }}" method="POST" class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 500px;">
                    @csrf
                    <div class="input-group input-group-sm shadow-sm rounded-pill overflow-hidden border border-light-subtle">
                        <span class="input-group-text bg-white text-primary border-0 px-3"><i class="bi bi-link-45deg"></i></span>
                        <input type="url" name="link_anggaran" class="form-control border-0 px-2" placeholder="https://drive.google.com/..." value="{{ old('link_anggaran', $linkAnggaran) }}" required>
                        <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">Simpan Link Drive</button>
                    </div>
                </form>
            @endif
        </div>

        @if(session('success'))
            <div class="alert alert-success rounded-3 small fw-bold mb-3"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div>
        @endif

        <div class="alert alert-info small border-0 bg-info bg-opacity-10 text-dark rounded-3 mb-4">
            <strong>Pemberitahuan:</strong> Sebelum mengajukan pembayaran, pastikan ketersediaan pagu anggaran pada dokumen DIPA di bawah ini mencukupi. Jika pagu habis, pengajuan dapat ditolak oleh Verifikator Keuangan.
        </div>

        {{-- Display Google Drive Embed or Link Button --}}
        @if(!empty($linkAnggaran))
            @php
                // Transform google drive view link into preview embed link if possible
                $embedUrl = $linkAnggaran;
                if (str_contains($embedUrl, 'drive.google.com') && str_contains($embedUrl, '/view')) {
                    $embedUrl = str_replace('/view', '/preview', $embedUrl);
                }
            @endphp
            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded-3 border">
                <div class="d-flex align-items-center gap-2 text-primary fw-bold small">
                    <i class="bi bi-google-drive fs-5 text-success"></i>
                    <span>Tautan Google Drive Pagu Anggaran DIPA Terbuka</span>
                </div>
                <a href="{{ $linkAnggaran }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                    <i class="bi bi-box-arrow-up-right me-1.5"></i> Buka Dokumen di Google Drive
                </a>
            </div>

            <div class="ratio ratio-16x9 border rounded-3 overflow-hidden shadow-sm">
                <iframe src="{{ $embedUrl }}" allowfullscreen></iframe>
            </div>
        @elseif(file_exists(public_path('uploads/anggaran_terbaru.pdf')))
            <div class="ratio ratio-16x9 border rounded-3 overflow-hidden shadow-sm">
                <iframe src="{{ asset('uploads/anggaran_terbaru.pdf') }}" allowfullscreen></iframe>
            </div>
        @else
            <div class="p-5 text-center bg-light rounded-4 border border-dashed">
                <i class="bi bi-file-earmark-pdf text-muted display-4 d-block mb-2"></i>
                <h6 class="fw-bold text-dark mb-1">Dokumen Anggaran Belum Diatur</h6>
                <p class="text-muted small mb-0">Admin Keuangan belum memasukkan tautan Google Drive dokumen anggaran DIPA.</p>
            </div>
        @endif
    </div>
@endsection