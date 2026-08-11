{{-- File: resources/views/pengajuan/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat Pengajuan')

@section('content')
    <div class="card card-custom p-5 bg-white border-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Form Pengajuan Pembayaran</h3>
                <p class="text-muted mb-0 small">Masukkan detail dokumen pengajuan SPJ & data dukung dokumen</p>
            </div>
            <a href="{{ route('pengajuan.index') }}" class="btn btn-secondary btn-sm rounded-pill px-4">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
        <hr class="text-muted opacity-25">

        {{-- Menampilkan error validasi --}}
        @if ($errors->any())
            <div class="alert alert-danger shadow-sm rounded-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terdapat kesalahan pengisian form:</h6>
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('pengajuan.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-semibold text-secondary">Nomor Pengajuan (Otomatis)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-0 shadow-sm"><i class="bi bi-tag-fill"></i></span>
                        <input type="text" name="no_pengajuan" class="form-control border-0 bg-light shadow-sm" value="{{ $noPengajuanBaru }}" readonly>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-semibold text-secondary">Bidang</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-0 shadow-sm"><i class="bi bi-building"></i></span>
                        <input type="text" class="form-control border-0 bg-light shadow-sm" value="{{ Auth::user()->bidang }}" readonly>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-semibold text-secondary">Kategori Pengajuan <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-tags-fill"></i></span>
                        <select name="kategori_pengajuan" id="select_kategori" class="form-select border-0 shadow-sm" onchange="renderDataDukungFields()" required>
                            <option value="" disabled selected>-- Pilih Kategori Pengajuan --</option>
                            <option value="GU/UP/TUP" {{ old('kategori_pengajuan') == 'GU/UP/TUP' ? 'selected' : '' }}>GU/UP/TUP</option>
                            <option value="LS Kontrak" {{ old('kategori_pengajuan') == 'LS Kontrak' ? 'selected' : '' }}>LS Kontrak</option>
                            <option value="LS Non Kontrak" {{ old('kategori_pengajuan') == 'LS Non Kontrak' ? 'selected' : '' }}>LS Non Kontrak</option>
                            <option value="LS banyak penerima" {{ old('kategori_pengajuan') == 'LS banyak penerima' ? 'selected' : '' }}>LS banyak penerima (Uang Saku)</option>
                            <option value="LS Bendahara" {{ old('kategori_pengajuan') == 'LS Bendahara' ? 'selected' : '' }}>LS Bendahara</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-semibold text-secondary">Nama Kegiatan <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-card-text"></i></span>
                        <input type="text" name="nama_kegiatan" class="form-control border-0 shadow-sm" value="{{ old('nama_kegiatan') }}" placeholder="Contoh: Honorarium Narasumber Peningkatan Mutu" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-semibold text-secondary">Nomor Akun <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-hash"></i></span>
                        <input type="text" name="no_akun" class="form-control border-0 shadow-sm" value="{{ old('no_akun') }}" placeholder="Contoh: 521211" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label small fw-semibold text-secondary">Jenis Belanja <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-cart3"></i></span>
                        <select name="jenis_belanja" class="form-select border-0 shadow-sm" required>
                            <option value="Honorarium" {{ old('jenis_belanja') == 'Honorarium' ? 'selected' : '' }}>Honorarium</option>
                            <option value="Pembayaran Uang Saku Peserta" {{ old('jenis_belanja') == 'Pembayaran Uang Saku Peserta' ? 'selected' : '' }}>Pembayaran Uang Saku Peserta</option>
                            <option value="Pengadaan Barang" {{ old('jenis_belanja') == 'Pengadaan Barang' ? 'selected' : '' }}>Pengadaan Barang</option>
                            <option value="Pemeliharaan" {{ old('jenis_belanja') == 'Pemeliharaan' ? 'selected' : '' }}>Pemeliharaan</option>
                            <option value="Lainnya" {{ old('jenis_belanja') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                    </div>
                </div>

                <!-- RINCIAN KEUANGAN & PAJAK OTOMATIS -->
                <div class="col-md-4 mb-3">
                    <label class="form-label small fw-semibold text-secondary">Nilai Bruto (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm">Rp</span>
                        <input type="number" step="0.01" name="nilai_bruto" id="nilai_bruto" class="form-control border-0 shadow-sm" value="{{ old('nilai_bruto') }}" placeholder="0" oninput="hitungNeto()" required>
                    </div>
                    <div id="helper_nilai_bruto" class="form-text text-success fw-semibold small mt-1"></div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label small fw-semibold text-secondary">Potongan Pajak (Rp)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm">Rp</span>
                        <input type="number" step="0.01" name="potongan_pajak" id="potongan_pajak" class="form-control border-0 shadow-sm" value="{{ old('potongan_pajak', 0) }}" placeholder="0" oninput="hitungNeto()">
                    </div>
                    <div id="helper_potongan_pajak" class="form-text text-danger fw-semibold small mt-1"></div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label small fw-semibold text-secondary">Nilai Neto / Riil (Rp)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm">Rp</span>
                        <input type="number" step="0.01" name="nilai_neto" id="nilai_neto" class="form-control border-0 bg-light shadow-sm text-success fw-bold" value="{{ old('nilai_neto') }}" placeholder="0" readonly required>
                    </div>
                    <div id="helper_nilai_neto" class="form-text text-success fw-semibold small mt-1"></div>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label small fw-semibold text-secondary">Uraian Pembayaran</label>
                    <textarea name="uraian_pembayaran" class="form-control border-0 shadow-sm" rows="3" placeholder="Masukkan uraian detail pembayaran..." required>{{ old('uraian_pembayaran') }}</textarea>
                </div>

                <div class="col-md-12 mb-4 bg-light p-4 rounded border border-light-subtle shadow-sm">
                    <h5 class="fw-bold text-secondary mb-2"><i class="bi bi-link-45deg"></i> Link Utama Google Drive SPJ <span class="text-danger">*</span></h5>
                    <p class="text-muted small mb-3">Masukkan link Google Drive folder berkas dokumen pendukung asli:</p>
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-google"></i></span>
                        <input type="url" name="link_google_drive" class="form-control border-0 shadow-sm" placeholder="Contoh: https://drive.google.com/..." value="{{ old('link_google_drive') }}" required>
                    </div>

                    <!-- BERKAS DATA DUKUNG WAJIB PER KATEGORI -->
                    <div class="card border-primary border-opacity-25 p-3 bg-white">
                        <h6 class="fw-bold text-primary mb-2">
                            <i class="bi bi-file-earmark-check-fill me-1"></i> Data Dukung Dokumen Wajib
                        </h6>
                        <p class="text-muted small mb-3">
                            Pilih Kategori Pengajuan di atas terlebih dahulu untuk menampilkan daftar berkas data dukung wajib. Masukkan link Google Drive untuk masing-masing dokumen.
                        </p>
                        <div id="containerDataDukung">
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="bi bi-info-circle me-1"></i> Silakan pilih Kategori Pengajuan di atas.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" name="action" value="draft" class="btn btn-secondary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-save"></i> Simpan Draft
                </button>
                <button type="submit" name="action" value="ajukan" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-send-fill"></i> Ajukan ke Keuangan
                </button>
            </div>
        </form>
    </div>

    <!-- Script Form Helper -->
    <script>
        const dataDukungMap = {
            'GU/UP/TUP': ['SPTB', 'Rincian POK', 'DRPP'],
            'LS Kontrak': ['Surat pesanan', 'BA Serah Terima', 'Permintaan Pembayaran', 'BA Pembayaran', 'Kwitansi', 'SPTB'],
            'LS Non Kontrak': ['Rincian POK', 'Npwp', 'Rekening', 'Surat pesanan', 'BA Serah Terima', 'Permintaan Pembayaran', 'BA Pembayaran', 'Kwitansi', 'SPTB'],
            'LS banyak penerima': ['SPTB', 'SK', 'Rincian POK', 'Pendaftaran suplier', 'Rekap pengajuan'],
            'LS Bendahara': ['SPTB', 'SK/SPT', 'Rincian POK', 'Daftar pembayaran']
        };

        function formatRupiah(angka) {
            if (!angka || isNaN(angka)) return '';
            var number_string = angka.toString().replace(/[^,\d]/g, ''),
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
            return rupiah ? 'Format: Rp ' + rupiah : '';
        }

        function hitungNeto() {
            const bruto = parseFloat(document.getElementById('nilai_bruto').value) || 0;
            const pajak = parseFloat(document.getElementById('potongan_pajak').value) || 0;
            const neto = Math.max(0, bruto - pajak);
            document.getElementById('nilai_neto').value = neto;

            document.getElementById('helper_nilai_bruto').innerText = formatRupiah(bruto);
            document.getElementById('helper_potongan_pajak').innerText = pajak > 0 ? formatRupiah(pajak) : '';
            document.getElementById('helper_nilai_neto').innerText = formatRupiah(neto);
        }

        function renderDataDukungFields() {
            const kategori = document.getElementById('select_kategori').value;
            const container = document.getElementById('containerDataDukung');
            container.innerHTML = '';

            if (!kategori || !dataDukungMap[kategori]) {
                container.innerHTML = '<div class="alert alert-info py-2 small mb-0"><i class="bi bi-info-circle me-1"></i> Silakan pilih Kategori Pengajuan di atas.</div>';
                return;
            }

            const docs = dataDukungMap[kategori];
            let html = '<div class="row g-2">';

            docs.forEach((docName) => {
                html += `
                    <div class="col-md-6 mb-2">
                        <label class="form-label small fw-semibold text-dark mb-1">
                            <i class="bi bi-file-earmark-text me-1"></i> ${docName} <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-link-45deg"></i></span>
                            <input type="url" name="data_dukung[${docName}]" class="form-control" placeholder="Tautan Drive ${docName}" required>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        }

        // Trigger on load if old value exists
        document.addEventListener('DOMContentLoaded', function() {
            hitungNeto();
            if (document.getElementById('select_kategori').value) {
                renderDataDukungFields();
            }
        });
    </script>
@endsection