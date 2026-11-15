{{-- File: resources/views/banprog/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat Pengajuan Banprog')

@section('content')
    @include('banprog._style')
    @php use App\Models\PengajuanBanprog as B; @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-buildings text-warning me-2"></i>Buat Pengajuan Banprog</h4>
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
                    <div class="bp-section-title mb-3"><i class="bi bi-building me-1"></i> Identitas Penerima Banprog (LPKS / BLKK)</div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small fw-semibold">Nama LPKS / BLKK *</label><input name="nama_lpks" id="nama_lpks" class="form-control" value="{{ old('nama_lpks') }}" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Kabupaten / Kota *</label><input name="kabupaten_kota" id="kabupaten_kota" class="form-control" value="{{ old('kabupaten_kota') }}" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Kejuruan *</label><input name="kejuruan" id="kejuruan" class="form-control" value="{{ old('kejuruan') }}" placeholder="Contoh: Menjahit, Las, TIK" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">No. SK Penetapan</label><input name="no_sk_penetapan" id="no_sk_penetapan" class="form-control" value="{{ old('no_sk_penetapan') }}"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Bank</label><input name="bank_lpks" id="bank_lpks" class="form-control" value="{{ old('bank_lpks') }}"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">No. Rekening LPKS / BLKK</label><input name="rekening_lpks" id="rekening_lpks" class="form-control" value="{{ old('rekening_lpks') }}"></div>
                    </div>
                </div>

                <div class="card card-custom p-4 bg-white">
                    <div class="bp-section-title mb-3"><i class="bi bi-cash-coin me-1"></i> Nominal & Kategori Pengajuan</div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nominal Pengajuan Banprog (Rp) *</label>
                        <input type="number" step="1" min="1" name="nilai_banprog" id="nilai_banprog" class="form-control form-control-lg fw-bold" value="{{ old('nilai_banprog') }}" placeholder="Masukkan nominal pengajuan" required>
                        <div class="small fw-semibold text-primary mt-1" id="preview_nilai">Rp 0</div>
                    </div>

                    <div>
                        <label class="form-label small fw-semibold">Kategori Pengajuan (Termin) *</label>
                        <select name="kategori_pengajuan" id="kategori_pengajuan" class="form-select" required>
                            <option value="Termin I" {{ old('kategori_pengajuan', 'Termin I') === 'Termin I' ? 'selected' : '' }}>Termin I</option>
                            <option value="Termin II" {{ old('kategori_pengajuan') === 'Termin II' ? 'selected' : '' }}>Termin II</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card card-custom p-4 bg-white">
                    <div class="bp-section-title mb-3" id="dok-card-title"><i class="bi bi-ui-checks me-1"></i> Dokumen Termin 1 (masing-masing 2 rangkap)</div>
                    <label class="form-label small fw-semibold">Link Google Drive (folder dokumen) *</label>
                    <input type="url" name="link_drive_termin1" id="link_drive_termin1" class="form-control mb-3" value="{{ old('link_drive_termin1') }}" placeholder="https://drive.google.com/..." required>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Tandai dokumen yang sudah diunggah</span>
                        <button type="button" class="btn btn-link btn-sm p-0" id="btn-centang-semua">Centang semua</button>
                    </div>
                    <div class="d-grid gap-2" id="checklist-t1">
                        @foreach(B::CHECKLIST_TERMIN1 as $no => $uraian)
                            <label class="bp-check-item d-flex align-items-start gap-2 small {{ in_array($no, B::CHECKLIST_HIGHLIGHT) ? 'highlight' : '' }}" for="dok_t1_{{ $no }}">
                                <input type="checkbox" class="form-check-input mt-0 dok-check dok-t1" name="dok[{{ $no }}]" id="dok_t1_{{ $no }}" value="1" {{ old("dok.$no") ? 'checked' : '' }}>
                                <span><span class="badge bg-primary bg-opacity-10 text-primary me-1">{{ $no }}</span>{{ $uraian }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="d-grid gap-2 d-none" id="checklist-t2">
                        @foreach(B::CHECKLIST_TERMIN2 as $no => $uraian)
                            <label class="bp-check-item d-flex align-items-start gap-2 small" for="dok_t2_{{ $no }}">
                                <input type="checkbox" class="form-check-input mt-0 dok-check dok-t2" name="dok[{{ $no }}]" id="dok_t2_{{ $no }}" value="1" disabled {{ old("dok.$no") ? 'checked' : '' }}>
                                <span><span class="badge bg-success bg-opacity-10 text-success me-1">{{ $no }}</span>{{ $uraian }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" name="action" value="draft" id="btn-simpan-draft" class="btn btn-outline-secondary flex-fill rounded-pill fw-semibold" formnovalidate><i class="bi bi-save me-1"></i> Simpan Draft</button>
                        <button type="submit" name="action" value="ajukan" id="btn-ajukan-banprog" class="btn btn-warning flex-fill rounded-pill fw-semibold"><i class="bi bi-send me-1"></i> <span id="lbl-ajukan">Ajukan Termin 1</span></button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fmt = n => 'Rp ' + Math.round(n || 0).toLocaleString('id-ID');
            const nilai = document.getElementById('nilai_banprog');
            const prev = document.getElementById('preview_nilai');
            const kat = document.getElementById('kategori_pengajuan');

            const upd = () => prev.textContent = fmt(parseFloat(nilai.value) || 0);
            nilai.addEventListener('input', upd);
            upd();

            const syncKategori = () => {
                const isT2 = kat.value === 'Termin II';
                document.getElementById('checklist-t1').classList.toggle('d-none', isT2);
                document.getElementById('checklist-t2').classList.toggle('d-none', !isT2);
                document.querySelectorAll('.dok-t1').forEach(el => el.disabled = isT2);
                document.querySelectorAll('.dok-t2').forEach(el => el.disabled = !isT2);
                document.getElementById('dok-card-title').innerHTML = isT2
                    ? '<i class="bi bi-ui-checks me-1"></i> Dokumen Termin 2'
                    : '<i class="bi bi-ui-checks me-1"></i> Dokumen Termin 1 (masing-masing 2 rangkap)';
                document.getElementById('lbl-ajukan').textContent = isT2 ? 'Ajukan Termin 2' : 'Ajukan Termin 1';
            };
            kat.addEventListener('change', syncKategori);
            syncKategori();

            document.getElementById('btn-centang-semua').addEventListener('click', () => {
                const selector = kat.value === 'Termin II' ? '.dok-t2' : '.dok-t1';
                document.querySelectorAll(selector).forEach(c => c.checked = true);
            });
        });
    </script>
@endsection
