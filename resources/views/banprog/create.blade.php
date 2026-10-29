{{-- File: resources/views/banprog/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat Pengajuan Banprog')

@section('content')
    @include('banprog._style')
    @php use App\Models\PengajuanBanprog as B; @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-buildings text-warning me-2"></i>Pengajuan Banprog — Termin 1</h4>
            <p class="text-muted small mb-0">No. Pengajuan: <span class="badge bg-light text-dark border">{{ $noBaru }}</span> (final dibuat saat disimpan)</p>
        </div>
        <a href="{{ route('banprog.index') }}" class="btn btn-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left-short"></i> Kembali</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('banprog.store') }}" method="POST" id="form-banprog">
        @csrf
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card card-custom p-4 bg-white mb-4">
                    <div class="bp-section-title mb-3"><i class="bi bi-building me-1"></i> Identitas LPKS</div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small fw-semibold">Nama LPKS *</label><input name="nama_lpks" id="nama_lpks" class="form-control" value="{{ old('nama_lpks') }}" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Kabupaten / Kota *</label><input name="kabupaten_kota" id="kabupaten_kota" class="form-control" value="{{ old('kabupaten_kota') }}" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Nama Kepala LPKS *</label><input name="nama_kepala_lpks" id="nama_kepala_lpks" class="form-control" value="{{ old('nama_kepala_lpks') }}" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">No. HP Kepala LPKS *</label><input name="no_hp_kepala_lpks" id="no_hp_kepala_lpks" class="form-control" value="{{ old('no_hp_kepala_lpks') }}" placeholder="08xxxxxxxxxx" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Kejuruan *</label><input name="kejuruan" id="kejuruan" class="form-control" value="{{ old('kejuruan') }}" placeholder="Contoh: Menjahit, Las, TIK" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">No. SK Penetapan</label><input name="no_sk_penetapan" id="no_sk_penetapan" class="form-control" value="{{ old('no_sk_penetapan') }}"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Tgl Pelaksanaan Mulai</label><input type="date" name="tgl_pelaksanaan_mulai" id="tgl_mulai" class="form-control" value="{{ old('tgl_pelaksanaan_mulai') }}"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Tgl Pelaksanaan Selesai</label><input type="date" name="tgl_pelaksanaan_selesai" id="tgl_selesai" class="form-control" value="{{ old('tgl_pelaksanaan_selesai') }}"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Bank</label><input name="bank_lpks" id="bank_lpks" class="form-control" value="{{ old('bank_lpks') }}"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">No. Rekening LPKS</label><input name="rekening_lpks" id="rekening_lpks" class="form-control" value="{{ old('rekening_lpks') }}"></div>
                    </div>
                </div>

                <div class="card card-custom p-4 bg-white">
                    <div class="bp-section-title mb-3"><i class="bi bi-cash-coin me-1"></i> Nilai Bantuan</div>
                    <label class="form-label small fw-semibold">Nilai Total Banprog (Rp) *</label>
                    <input type="number" step="1" min="1" name="nilai_banprog" id="nilai_banprog" class="form-control form-control-lg fw-bold" value="{{ old('nilai_banprog') }}" required>
                    <div class="row g-3 mt-1">
                        <div class="col-6">
                            <div class="bp-termin active">
                                <div class="small text-muted">Termin 1 (70%)</div>
                                <div class="fs-5 fw-bold text-primary" id="preview_t1">Rp 0</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bp-termin">
                                <div class="small text-muted">Termin 2 (30%) — setelah laporan</div>
                                <div class="fs-5 fw-bold text-secondary" id="preview_t2">Rp 0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card card-custom p-4 bg-white">
                    <div class="bp-section-title mb-3"><i class="bi bi-ui-checks me-1"></i> Dokumen Termin 1 (masing-masing 2 rangkap)</div>
                    <label class="form-label small fw-semibold">Link Google Drive (folder dokumen) *</label>
                    <input type="url" name="link_drive_termin1" id="link_drive_termin1" class="form-control mb-3" value="{{ old('link_drive_termin1') }}" placeholder="https://drive.google.com/..." required>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Tandai dokumen yang sudah diunggah</span>
                        <button type="button" class="btn btn-link btn-sm p-0" id="btn-centang-semua">Centang semua</button>
                    </div>
                    <div class="d-grid gap-2">
                        @foreach(B::CHECKLIST_TERMIN1 as $no => $uraian)
                            <label class="bp-check-item d-flex align-items-start gap-2 small {{ in_array($no, B::CHECKLIST_HIGHLIGHT) ? 'highlight' : '' }}" for="dok_{{ $no }}">
                                <input type="checkbox" class="form-check-input mt-0 dok-check" name="dok[{{ $no }}]" id="dok_{{ $no }}" value="1" {{ old("dok.$no") ? 'checked' : '' }}>
                                <span><span class="badge bg-primary bg-opacity-10 text-primary me-1">{{ $no }}</span>{{ $uraian }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" name="action" value="draft" id="btn-simpan-draft" class="btn btn-outline-secondary flex-fill rounded-pill fw-semibold" formnovalidate><i class="bi bi-save me-1"></i> Simpan Draft</button>
                        <button type="submit" name="action" value="ajukan" id="btn-ajukan-banprog" class="btn btn-warning flex-fill rounded-pill fw-semibold"><i class="bi bi-send me-1"></i> Ajukan Termin 1</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fmt = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
            const nilai = document.getElementById('nilai_banprog');
            const upd = () => {
                const v = parseFloat(nilai.value) || 0;
                const t1 = Math.round(v * 0.7);
                document.getElementById('preview_t1').textContent = fmt(t1);
                document.getElementById('preview_t2').textContent = fmt(v - t1);
            };
            nilai.addEventListener('input', upd); upd();
            document.getElementById('btn-centang-semua').addEventListener('click', () =>
                document.querySelectorAll('.dok-check').forEach(c => c.checked = true));
        });
    </script>
@endsection
