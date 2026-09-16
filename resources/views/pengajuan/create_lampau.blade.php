{{-- File: resources/views/pengajuan/create_lampau.blade.php --}}
@extends('layouts.app')

@section('title', 'Rekam Data Lampau')

@section('content')
    <div class="card card-custom p-4 p-md-5 bg-white border-0 shadow-sm">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
            <div>
                <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill fw-bold mb-2">
                    <i class="bi bi-clock-history me-1"></i> PEREKAMAN DATA LAMPAU
                </span>
                <h3 class="fw-bold text-dark mb-1">Perekaman SPM & SP2D (Pencairan lalu)</h3>
                <p class="text-muted mb-0 small">Input data pencairan masa lalu yang telah terbit SPM & SP2D sebelum aplikasi digunakan</p>
            </div>
            <a href="{{ route('pengajuan.index') }}" class="btn btn-secondary btn-sm rounded-pill px-4 shadow-sm">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar
            </a>
        </div>
        
        <div class="alert alert-warning border-warning border-opacity-50 bg-warning bg-opacity-10 rounded-3 mb-4 small p-3">
            <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5 align-middle"></i>
            <strong>PERHATIAN:</strong> Form ini khusus digunakan oleh <strong>Admin Keuangan / Operator Pembayaran</strong> untuk merekam data pencairan yang <strong>sudah cair di masa lalu</strong>. Data yang di-input akan langsung disimpan dengan status akhir <strong>Dicairkan</strong> atau <strong>Selesai</strong> tanpa melalui alur verifikasi dari awal.
        </div>

        {{-- Menampilkan error validasi --}}
        @if ($errors->any())
            <div class="alert alert-danger shadow-sm rounded-3 mb-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terdapat kesalahan pengisian form:</h6>
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('pengajuan.storeLampau') }}" method="POST">
            @csrf

            <!-- SECTION 1: INFORMASI UMUM & BIDANG -->
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <h5 class="fw-bold text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-info-circle me-2"></i>1. Informasi Pengajuan & Bidang
                    </h5>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Nomor Pengajuan <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-0 shadow-sm"><i class="bi bi-tag-fill"></i></span>
                        <input type="text" name="no_pengajuan" class="form-control border-0 bg-light shadow-sm fw-semibold" value="{{ old('no_pengajuan', $noPengajuanBaru) }}" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Tanggal Pengajuan <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-calendar-event"></i></span>
                        <input type="date" name="tgl_pengajuan" class="form-control border-0 shadow-sm" value="{{ old('tgl_pengajuan', date('Y-m-d')) }}" required>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Bidang / UPTD Pengaju <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-building"></i></span>
                        <select name="bidang" class="form-select border-0 shadow-sm" required>
                            <option value="" disabled selected>-- Pilih Bidang / UPTD --</option>
                            @foreach($daftarBidang as $b)
                                <option value="{{ $b }}" {{ old('bidang') == $b ? 'selected' : '' }}>{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Kategori Pengajuan <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-tags-fill"></i></span>
                        <select name="kategori_pengajuan" id="select_kategori" class="form-select border-0 shadow-sm" onchange="renderDataDukungFields()" required>
                            <option value="" disabled selected>-- Pilih Kategori --</option>
                            <option value="GU/UP/TUP" {{ old('kategori_pengajuan') == 'GU/UP/TUP' ? 'selected' : '' }}>GU/UP/TUP</option>
                            <option value="LS Kontrak" {{ old('kategori_pengajuan') == 'LS Kontrak' ? 'selected' : '' }}>LS Kontrak</option>
                            <option value="LS Non Kontrak" {{ old('kategori_pengajuan') == 'LS Non Kontrak' ? 'selected' : '' }}>LS Non Kontrak</option>
                            <option value="LS banyak penerima" {{ old('kategori_pengajuan') == 'LS banyak penerima' ? 'selected' : '' }}>LS banyak penerima (Uang Saku)</option>
                            <option value="LS Bendahara" {{ old('kategori_pengajuan') == 'LS Bendahara' ? 'selected' : '' }}>LS Bendahara</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Nomor Akun <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-hash"></i></span>
                        <input type="text" name="no_akun" class="form-control border-0 shadow-sm" value="{{ old('no_akun') }}" placeholder="Contoh: 521211" required>
                    </div>
                </div>

                <div class="col-md-4">
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

                <div class="col-md-12">
                    <label class="form-label small fw-semibold text-secondary">Nama Kegiatan <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-card-text"></i></span>
                        <input type="text" name="nama_kegiatan" class="form-control border-0 shadow-sm" value="{{ old('nama_kegiatan') }}" placeholder="Contoh: Pembayaran SPJ Honorarium Pelatihan Purworejo" required>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: RINCIAN KEUANGAN & ANGGARAN -->
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <h5 class="fw-bold text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-cash-stack me-2"></i>2. Rincian Keuangan & Pajak
                    </h5>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Nilai Bruto (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm">Rp</span>
                        <input type="number" step="0.01" name="nilai_bruto" id="nilai_bruto" class="form-control border-0 shadow-sm" value="{{ old('nilai_bruto') }}" placeholder="0" oninput="hitungNeto()" required>
                    </div>
                    <div id="helper_nilai_bruto" class="form-text text-success fw-semibold small mt-1"></div>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Potongan Pajak (Rp)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm">Rp</span>
                        <input type="number" step="0.01" name="potongan_pajak" id="potongan_pajak" class="form-control border-0 shadow-sm" value="{{ old('potongan_pajak', 0) }}" placeholder="0" oninput="hitungNeto()">
                    </div>
                    <div id="helper_potongan_pajak" class="form-text text-danger fw-semibold small mt-1"></div>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-secondary">Nilai Neto / Riil (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm">Rp</span>
                        <input type="number" step="0.01" name="nilai_neto" id="nilai_neto" class="form-control border-0 bg-light shadow-sm text-success fw-bold" value="{{ old('nilai_neto') }}" placeholder="0" readonly required>
                    </div>
                    <div id="helper_nilai_neto" class="form-text text-success fw-semibold small mt-1"></div>
                </div>

                <div class="col-md-12">
                    <label class="form-label small fw-semibold text-secondary">Uraian Pembayaran</label>
                    <textarea name="uraian_pembayaran" class="form-control border-0 shadow-sm" rows="2" placeholder="Uraian ringkas pencairan berkas lama...">{{ old('uraian_pembayaran') }}</textarea>
                </div>
            </div>

            <!-- SECTION 3: DATA SP2D & SPM SAKTI LAMPAU -->
            <div class="row g-3 mb-4 bg-light p-3 rounded border border-light-subtle shadow-sm mx-0">
                <div class="col-md-12">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-file-earmark-check me-2 text-success"></i>3. Perekaman Data SPM & SP2D SAKTI
                    </h5>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Nomor SPM SAKTI <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-receipt"></i></span>
                        <input type="text" name="no_spm" class="form-control border-0 shadow-sm" value="{{ old('no_spm') }}" placeholder="Contoh: 260541X" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Tanggal Terbit SPM <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-calendar-check"></i></span>
                        <input type="date" name="tgl_spm" class="form-control border-0 shadow-sm" value="{{ old('tgl_spm', date('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Nomor SP2D KPPN <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-bank"></i></span>
                        <input type="text" name="no_sp2d" class="form-control border-0 shadow-sm" value="{{ old('no_sp2d') }}" placeholder="Contoh: 24053000123" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Tanggal Cair SP2D <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-calendar2-check"></i></span>
                        <input type="date" name="tgl_cair" class="form-control border-0 shadow-sm" value="{{ old('tgl_cair', date('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Status Akhir Dokumen <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-check-all"></i></span>
                        <select name="status" class="form-select border-0 shadow-sm fw-bold text-success" required>
                            <option value="Dicairkan" {{ old('status') == 'Dicairkan' ? 'selected' : '' }}>Dicairkan (SP2D Terbit)</option>
                            <option value="Selesai" {{ old('status') == 'Selesai' ? 'selected' : '' }}>Selesai (Uang Diserahkan & Complete)</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-secondary">Status Penatausahaan SPJ</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-journal-check"></i></span>
                        <select name="spj_status" class="form-select border-0 shadow-sm">
                            <option value="SPJ Lengkap" {{ old('spj_status', 'SPJ Lengkap') == 'SPJ Lengkap' ? 'selected' : '' }}>SPJ Lengkap & Terverifikasi</option>
                            <option value="Menunggu Verifikasi SPJ" {{ old('spj_status') == 'Menunggu Verifikasi SPJ' ? 'selected' : '' }}>Menunggu Verifikasi SPJ</option>
                            <option value="Belum Upload" {{ old('spj_status') == 'Belum Upload' ? 'selected' : '' }}>Belum Upload SPJ</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: TAUTAN DOKUMEN GOOGLE DRIVE -->
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <h5 class="fw-bold text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-link-45deg me-2"></i>4. Tautan Berkas Google Drive
                    </h5>
                </div>

                <div class="col-md-6 mb-2">
                    <label class="form-label small fw-semibold text-secondary">Link Utama Google Drive SPJ <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-google"></i></span>
                        <input type="url" name="link_google_drive" class="form-control border-0 shadow-sm gdrive-input" placeholder="https://drive.google.com/..." value="{{ old('link_google_drive') }}" oninput="validateGDriveUrl(this)" required>
                    </div>
                </div>

                <div class="col-md-6 mb-2">
                    <label class="form-label small fw-semibold text-secondary">Link Bukti Penyerahan (Wajib) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-file-earmark-arrow-up"></i></span>
                        <input type="url" name="bukti_penyerahan" class="form-control border-0 shadow-sm gdrive-input" placeholder="https://drive.google.com/..." value="{{ old('bukti_penyerahan') }}" oninput="validateGDriveUrl(this)" required>
                    </div>
                </div>

                <!-- BERKAS DATA DUKUNG PER KATEGORI -->
                <div class="col-md-12">
                    <div class="card border-primary border-opacity-25 p-3 bg-light bg-opacity-50 shadow-sm">
                        <h6 class="fw-bold text-primary mb-2">
                            <i class="bi bi-file-earmark-check-fill me-1"></i> Berkas Data Dukung Dokumen (Wajib)
                        </h6>
                        <p class="text-muted small mb-3">
                            Pilih Kategori Pengajuan di atas terlebih dahulu untuk menampilkan daftar berkas data dukung. Masukkan link Google Drive untuk masing-masing dokumen.
                        </p>
                        <div id="containerDataDukung">
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="bi bi-info-circle me-1"></i> Silakan pilih Kategori Pengajuan pada bagian 1 di atas.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BUTTON ACTIONS -->
            <div class="d-flex gap-2 justify-content-end border-top pt-4">
                <a href="{{ route('pengajuan.index') }}" class="btn btn-light border rounded-pill px-4">
                    Batal
                </a>
                <button type="submit" class="btn btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="bi bi-journal-check me-1"></i> Simpan Perekaman Data Lampau
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
                container.innerHTML = '<div class="alert alert-info py-2 small mb-0"><i class="bi bi-info-circle me-1"></i> Silakan pilih Kategori Pengajuan pada bagian 1 di atas.</div>';
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
                            <input type="url" name="data_dukung[${docName}]" class="form-control gdrive-input" placeholder="Tautan Drive ${docName}" oninput="validateGDriveUrl(this)" required>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        }

        function validateGDriveUrl(inputElem) {
            if (!inputElem) return;
            const val = (inputElem.value || '').trim();
            let feedbackElem = inputElem.parentNode.nextElementSibling;
            
            if (!feedbackElem || !feedbackElem.classList.contains('gdrive-feedback')) {
                feedbackElem = document.createElement('div');
                feedbackElem.className = 'gdrive-feedback form-text fw-semibold small mt-1';
                inputElem.parentNode.parentNode.insertBefore(feedbackElem, inputElem.parentNode.nextSibling);
            }
            
            if (!val) {
                feedbackElem.innerHTML = '';
                return;
            }
            
            const isDriveDomain = /https?:\/\/(drive|docs)\.google\.com\//i.test(val);
            
            if (!isDriveDomain) {
                feedbackElem.className = 'gdrive-feedback form-text text-danger fw-semibold small mt-1';
                feedbackElem.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i> Format URL harus diawali dengan https://drive.google.com/ atau https://docs.google.com/';
                return;
            }
            
            const hasSharing = val.includes('usp=sharing') || val.includes('usp=drivesdk') || val.includes('/drive/folders/') || val.includes('/file/d/');
            
            if (hasSharing) {
                feedbackElem.className = 'gdrive-feedback form-text text-success fw-semibold small mt-1';
                feedbackElem.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Link Google Drive terdeteksi valid. Pastikan izin akses diatur ke "Siapa saja yang memiliki link".';
            } else {
                feedbackElem.className = 'gdrive-feedback form-text text-warning fw-semibold small mt-1';
                feedbackElem.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> Format link valid. Disarankan menggunakan link bagikan (share link) Google Drive.';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            hitungNeto();
            if (document.getElementById('select_kategori').value) {
                renderDataDukungFields();
            }
            document.querySelectorAll('.gdrive-input').forEach(el => validateGDriveUrl(el));
        });
    </script>
@endsection
