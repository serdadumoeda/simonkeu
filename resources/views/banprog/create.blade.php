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
                    <div class="bp-section-title mb-3"><i class="bi bi-building me-1"></i> Identitas LPKS / BLKK</div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small fw-semibold">Nama LPKS / BLKK *</label><input name="nama_lpks" id="nama_lpks" class="form-control" value="{{ old('nama_lpks') }}" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Kabupaten / Kota *</label><input name="kabupaten_kota" id="kabupaten_kota" class="form-control" value="{{ old('kabupaten_kota') }}" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Nama Kepala LPKS / BLKK *</label><input name="nama_kepala_lpks" id="nama_kepala_lpks" class="form-control" value="{{ old('nama_kepala_lpks') }}" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">No. HP Kepala LPKS / BLKK *</label><input name="no_hp_kepala_lpks" id="no_hp_kepala_lpks" class="form-control" value="{{ old('no_hp_kepala_lpks') }}" placeholder="08xxxxxxxxxx" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Kejuruan *</label><input name="kejuruan" id="kejuruan" class="form-control" value="{{ old('kejuruan') }}" placeholder="Contoh: Menjahit, Las, TIK" required></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">No. SK Penetapan</label><input name="no_sk_penetapan" id="no_sk_penetapan" class="form-control" value="{{ old('no_sk_penetapan') }}"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Tgl Pelaksanaan Mulai</label><input type="date" name="tgl_pelaksanaan_mulai" id="tgl_mulai" class="form-control" value="{{ old('tgl_pelaksanaan_mulai') }}"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Tgl Pelaksanaan Selesai</label><input type="date" name="tgl_pelaksanaan_selesai" id="tgl_selesai" class="form-control" value="{{ old('tgl_pelaksanaan_selesai') }}"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Bank</label><input name="bank_lpks" id="bank_lpks" class="form-control" value="{{ old('bank_lpks') }}"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">No. Rekening LPKS / BLKK</label><input name="rekening_lpks" id="rekening_lpks" class="form-control" value="{{ old('rekening_lpks') }}"></div>
                    </div>
                </div>

                <div class="card card-custom p-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bp-section-title mb-0"><i class="bi bi-cash-coin me-1"></i> Kategori & Nilai Bantuan</div>
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="btn-auto-7030"><i class="bi bi-calculator me-1"></i>Hitung otomatis 70/30</button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kategori Pengajuan *</label>
                        <select name="kategori_pengajuan" id="kategori_pengajuan" class="form-select" required>
                            <option value="Termin I" {{ old('kategori_pengajuan', 'Termin I') === 'Termin I' ? 'selected' : '' }}>Termin I (70% — Pengajuan Awal)</option>
                            <option value="Termin II" {{ old('kategori_pengajuan') === 'Termin II' ? 'selected' : '' }}>Termin II (30% — Setelah Laporan)</option>
                        </select>
                    </div>

                    <label class="form-label small fw-semibold">Nilai Total Banprog (Rp) *</label>
                    <input type="number" step="1" min="1" name="nilai_banprog" id="nilai_banprog" class="form-control form-control-lg fw-bold" value="{{ old('nilai_banprog') }}" required>
                    <div class="row g-3 mt-1">
                        <div class="col-6">
                            <div class="bp-termin active" id="box_t1">
                                <label class="small text-muted d-block mb-1" for="nilai_termin_1">Termin 1 (Rp) — manual/otomatis</label>
                                <input type="number" step="1" min="0" name="nilai_termin_1" id="nilai_termin_1" class="form-control form-control-sm fw-bold text-primary" value="{{ old('nilai_termin_1') }}" placeholder="0">
                                <div class="small fw-semibold text-primary mt-1" id="preview_t1">Rp 0</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bp-termin" id="box_t2">
                                <label class="small text-muted d-block mb-1" for="nilai_termin_2">Termin 2 (Rp) — manual/otomatis</label>
                                <input type="number" step="1" min="0" name="nilai_termin_2" id="nilai_termin_2" class="form-control form-control-sm fw-bold text-secondary" value="{{ old('nilai_termin_2') }}" placeholder="0">
                                <div class="small fw-semibold text-secondary mt-1" id="preview_t2">Rp 0</div>
                            </div>
                        </div>
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
            const inT1 = document.getElementById('nilai_termin_1');
            const inT2 = document.getElementById('nilai_termin_2');
            const prevT1 = document.getElementById('preview_t1');
            const prevT2 = document.getElementById('preview_t2');
            const kat = document.getElementById('kategori_pengajuan');

            let t1Manual = inT1.value !== '';
            let t2Manual = inT2.value !== '';

            const renderPreview = () => {
                prevT1.textContent = fmt(parseFloat(inT1.value) || 0);
                prevT2.textContent = fmt(parseFloat(inT2.value) || 0);
            };

            const recalcFromTotal = (force = false) => {
                const v = parseFloat(nilai.value) || 0;
                if (force || !t1Manual) {
                    const t1 = Math.round(v * 0.7);
                    inT1.value = v > 0 ? t1 : '';
                }
                if (force || !t2Manual) {
                    const t1Val = parseFloat(inT1.value) || 0;
                    inT2.value = v > 0 ? Math.max(0, v - t1Val) : '';
                }
                renderPreview();
            };

            nilai.addEventListener('input', () => recalcFromTotal(false));
            inT1.addEventListener('input', () => {
                t1Manual = inT1.value !== '';
                if (!t2Manual) {
                    const v = parseFloat(nilai.value) || 0;
                    const t1Val = parseFloat(inT1.value) || 0;
                    if (v > 0) inT2.value = Math.max(0, v - t1Val);
                }
                renderPreview();
            });
            inT2.addEventListener('input', () => {
                t2Manual = inT2.value !== '';
                renderPreview();
            });

            document.getElementById('btn-auto-7030').addEventListener('click', () => {
                t1Manual = false;
                t2Manual = false;
                recalcFromTotal(true);
            });

            const syncKategori = () => {
                const isT2 = kat.value === 'Termin II';
                document.getElementById('box_t1').classList.toggle('active', !isT2);
                document.getElementById('box_t2').classList.toggle('active', isT2);
                document.getElementById('checklist-t1').classList.toggle('d-none', isT2);
                document.getElementById('checklist-t2').classList.toggle('d-none', !isT2);
                document.querySelectorAll('.dok-t1').forEach(el => el.disabled = isT2);
                document.querySelectorAll('.dok-t2').forEach(el => el.disabled = !isT2);
                document.getElementById('dok-card-title').innerHTML = isT2
                    ? '<i class="bi bi-ui-checks me-1"></i> Dokumen Laporan Termin 2'
                    : '<i class="bi bi-ui-checks me-1"></i> Dokumen Termin 1 (masing-masing 2 rangkap)';
                document.getElementById('lbl-ajukan').textContent = isT2 ? 'Ajukan Termin 2' : 'Ajukan Termin 1';
            };
            kat.addEventListener('change', syncKategori);

            recalcFromTotal(false);
            syncKategori();

            document.getElementById('btn-centang-semua').addEventListener('click', () => {
                const selector = kat.value === 'Termin II' ? '.dok-t2' : '.dok-t1';
                document.querySelectorAll(selector).forEach(c => c.checked = true);
            });
        });
    </script>
@endsection
