{{-- File: resources/views/pengajuan/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Pengajuan')

@section('content')
    @php
        $status = $pengajuan->status;
        
        $selisih = null;
        if ($pengajuan->tgl_spm) {
            $tgl_spm = \Carbon\Carbon::parse($pengajuan->tgl_spm);
            $tgl_cair = $pengajuan->tgl_cair ? \Carbon\Carbon::parse($pengajuan->tgl_cair) : \Carbon\Carbon::today();
            $selisih = $tgl_spm->diffInDays($tgl_cair);
        }
        
        // Step 1: Pemohon (Always completed)
        $step1_class = 'completed';
        
        // Step 2: Verifikasi
        if ($pengajuan->verifikator || in_array($status, ['Disetujui PPK', 'Diajukan ke SAKTI', 'Belum Terbit SP2D', 'Dicairkan', 'Selesai'])) {
            $step2_class = 'completed';
        } elseif ($status == 'Menunggu Verifikasi') {
            $step2_class = 'active';
        } elseif ($status == 'Perlu Perbaikan' && $pengajuan->verifikator_id) {
            $step2_class = 'warning';
        } else {
            $step2_class = 'pending';
        }
        
        // Step 3: PPK
        if ($pengajuan->ppk || in_array($status, ['Diajukan ke SAKTI', 'Belum Terbit SP2D', 'Dicairkan', 'Selesai'])) {
            $step3_class = 'completed';
        } elseif ($status == 'Disetujui PPK') {
            $step3_class = 'active';
        } elseif ($status == 'Perlu Perbaikan' && $pengajuan->ppk_id) {
            $step3_class = 'warning';
        } else {
            $step3_class = 'pending';
        }
        
        // Step 4: SPM
        if ($pengajuan->operatorPembayaran || in_array($status, ['Belum Terbit SP2D', 'Dicairkan', 'Selesai'])) {
            $step4_class = 'completed';
        } elseif ($status == 'Diajukan ke SAKTI') {
            $step4_class = 'active';
        } else {
            $step4_class = 'pending';
        }
        
        // Step 5: Cair
        if (in_array($status, ['Dicairkan', 'Selesai'])) {
            $step5_class = 'completed';
        } elseif ($status == 'Belum Terbit SP2D') {
            $step5_class = 'active';
        } else {
            $step5_class = 'pending';
        }

        // Step 6: Serah Terima
        if ($status == 'Selesai') {
            $step6_class = 'completed';
        } elseif ($status == 'Dicairkan') {
            $step6_class = 'active';
        } else {
            $step6_class = 'pending';
        }

        // Calculate progress width for stepper line
        $progress_width = '0%';
        if ($step6_class == 'completed') { $progress_width = '90%'; }
        elseif ($step5_class == 'completed') { $progress_width = '72%'; }
        elseif ($step4_class == 'completed') { $progress_width = '54%'; }
        elseif ($step3_class == 'completed') { $progress_width = '36%'; }
        elseif ($step2_class == 'completed') { $progress_width = '18%'; }
    @endphp

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card card-custom p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-0">Detail Pengajuan: {{ $pengajuan->no_pengajuan }}</h3>
                <p class="text-muted mb-0 small">Lacak dan verifikasi berkas pengajuan SPJ</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('pengajuan.index') }}" class="btn btn-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <a href="{{ route('pengajuan.cetak', $pengajuan->id) }}" target="_blank" class="btn btn-dark btn-sm rounded-pill px-3">
                    <i class="bi bi-printer"></i> Cetak Ringkasan
                </a>
            </div>
        </div>

        <!-- VISUAL STEPPER TIMELINE -->
        <h5 class="fw-bold text-dark mb-4 text-center">
            <i class="bi bi-geo-alt-fill text-primary"></i> Posisi Berkas SPJ Saat Ini
        </h5>
        
        <div class="stepper-container">
            <div class="stepper-line"></div>
            <div class="stepper-line-progress" style="width: {{ $progress_width }};"></div>

            <!-- Step 1 -->
            <div class="stepper-item {{ $step1_class }}">
                <div class="stepper-icon">
                    <i class="bi bi-file-earmark-plus-fill"></i>
                </div>
                <div class="stepper-label">Pemohon</div>
                <div class="stepper-sublabel text-truncate" style="max-width: 120px;" title="{{ $pengajuan->user->name ?? '' }}">{{ $pengajuan->user->name ?? '' }}</div>
            </div>

            <!-- Step 2 -->
            <div class="stepper-item {{ $step2_class }}">
                <div class="stepper-icon">
                    @if($step2_class == 'completed') <i class="bi bi-check2"></i>
                    @elseif($step2_class == 'warning') <i class="bi bi-exclamation-triangle"></i>
                    @else <i class="bi bi-shield-check"></i>
                    @endif
                </div>
                <div class="stepper-label">Verifikasi Keuangan</div>
                <div class="stepper-sublabel text-truncate" style="max-width: 120px;">
                    @if($pengajuan->verifikator) {{ $pengajuan->verifikator->name }}
                    @elseif($step2_class == 'warning') Perlu Revisi
                    @else ⏳ Menunggu
                    @endif
                </div>
            </div>

            <!-- Step 3 -->
            <div class="stepper-item {{ $step3_class }}">
                <div class="stepper-icon">
                    @if($step3_class == 'completed') <i class="bi bi-check2"></i>
                    @elseif($step3_class == 'warning') <i class="bi bi-exclamation-triangle"></i>
                    @else <i class="bi bi-file-earmark-person"></i>
                    @endif
                </div>
                <div class="stepper-label">Persetujuan PPK</div>
                <div class="stepper-sublabel text-truncate" style="max-width: 120px;">
                    @if($pengajuan->ppk) {{ $pengajuan->ppk->name }}
                    @elseif($step3_class == 'warning') Ditolak PPK
                    @else ⏳ Menunggu
                    @endif
                </div>
            </div>

            <!-- Step 4 -->
            <div class="stepper-item {{ $step4_class }}">
                <div class="stepper-icon">
                    @if($step4_class == 'completed') <i class="bi bi-check2"></i>
                    @else <i class="bi bi-send"></i>
                    @endif
                </div>
                <div class="stepper-label">Proses SAKTI (SPM)</div>
                <div class="stepper-sublabel text-truncate" style="max-width: 120px;">
                    @if($pengajuan->operatorPembayaran) {{ $pengajuan->operatorPembayaran->name }}
                    @elseif($pengajuan->no_spm) SPM: {{ $pengajuan->no_spm }}
                    @else ⏳ Menunggu
                    @endif
                </div>
            </div>

            <!-- Step 5 -->
            <div class="stepper-item {{ $step5_class }}">
                <div class="stepper-icon">
                    @if($step5_class == 'completed') <i class="bi bi-cash-coin"></i>
                    @else <i class="bi bi-wallet2"></i>
                    @endif
                </div>
                <div class="stepper-label">Pencairan Bendahara</div>
                <div class="stepper-sublabel" style="font-size: 11px; color: #adb5bd; margin-top: 2px;">
                    @if(in_array($status, ['Dicairkan', 'Selesai']))
                        Lunas/Cair @if(isset($selisih)) ({{ $selisih == 0 ? 'Hari H' : $selisih . ' Hari' }}) @endif
                    @else
                        ⏳ Menunggu @if(isset($selisih)) ({{ $selisih == 0 ? 'Hari H' : $selisih . ' Hari' }}) @endif
                    @endif
                </div>
            </div>

            <!-- Step 6 -->
            <div class="stepper-item {{ $step6_class }}">
                <div class="stepper-icon">
                    @if($step6_class == 'completed') <i class="bi bi-check-circle-fill text-white"></i>
                    @else <i class="bi bi-cash-stack"></i>
                    @endif
                </div>
                <div class="stepper-label">Penyerahan Uang</div>
                <div class="stepper-sublabel" style="font-size: 11px; color: #adb5bd; margin-top: 2px;">
                    @if($status == 'Selesai')
                        Selesai/Diserahkan
                    @elseif($status == 'Dicairkan')
                        ⏳ Siap Diserahkan
                    @else
                        ⏳ Menunggu
                    @endif
                </div>
            </div>
        </div>

        @if(isset($selisih))
            <div class="bg-light p-3 rounded-3 border border-light-subtle d-flex align-items-center justify-content-between mb-4 shadow-sm animate__animated animate__fadeIn">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Durasi Proses Pencairan Dana (SPM ➔ SP2D)</h6>
                        <p class="text-muted mb-0 small">
                            @if(in_array($pengajuan->status, ['Dicairkan', 'Selesai']))
                                SPM diajukan pada <strong>{{ \Carbon\Carbon::parse($pengajuan->tgl_spm)->format('d F Y') }}</strong> dan dicairkan oleh Bendahara pada <strong>{{ \Carbon\Carbon::parse($pengajuan->tgl_cair)->format('d F Y') }}</strong>. Total durasi pencairan: <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1"><i class="bi bi-lightning-fill"></i> {{ $selisih == 0 ? 'Hari yang sama (0 hari)' : $selisih . ' hari' }}</span>.
                            @else
                                SPM diajukan pada <strong>{{ \Carbon\Carbon::parse($pengajuan->tgl_spm)->format('d F Y') }}</strong>. Saat ini sedang menunggu pencairan dari Bendahara selama <span class="badge bg-warning bg-opacity-10 text-warning fw-bold px-2 py-1"><i class="bi bi-hourglass-split"></i> {{ $selisih == 0 ? 'Hari yang sama (0 hari)' : $selisih . ' hari' }}</span> berjalan.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="d-none d-md-block">
                    <span class="badge bg-{{ in_array($pengajuan->status, ['Dicairkan', 'Selesai']) ? 'success' : 'warning' }} text-white px-3 py-2 rounded-pill shadow-sm small">
                        <i class="bi bi-{{ in_array($pengajuan->status, ['Dicairkan', 'Selesai']) ? 'check-circle-fill' : 'clock' }} me-1"></i>
                        {{ in_array($pengajuan->status, ['Dicairkan', 'Selesai']) ? 'Selesai Dicairkan' : 'Dalam Proses' }}
                    </span>
                </div>
            </div>
        @endif

        <div class="row">
            <!-- Informasi Dasar -->
            <div class="col-md-6 mb-4">
                <div class="bg-light p-4 rounded-3 border border-light-subtle h-100">
                    <h5 class="fw-bold text-secondary mb-3"><i class="bi bi-info-circle-fill text-primary"></i> Informasi Dasar</h5>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td width="35%" class="fw-semibold text-muted">Kegiatan</td>
                            <td class="text-dark">: {{ $pengajuan->nama_kegiatan }}</td>
                        </tr>
                        @if($pengajuan->kategori_pengajuan)
                        <tr>
                            <td class="fw-semibold text-muted">Kategori</td>
                            <td class="text-dark">: <span class="badge bg-info bg-opacity-10 text-info px-2">{{ $pengajuan->kategori_pengajuan }}</span></td>
                        </tr>
                        @endif
                        <tr>
                            <td class="fw-semibold text-muted">Nomor Akun</td>
                            <td class="text-dark">: {{ $pengajuan->no_akun }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Jenis Belanja</td>
                            <td class="text-dark">: {{ $pengajuan->jenis_belanja }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Uraian Pembayaran</td>
                            <td class="text-dark">: {{ $pengajuan->uraian_pembayaran }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Rincian Keuangan -->
            <div class="col-md-6 mb-4">
                <div class="bg-light p-4 rounded-3 border border-light-subtle h-100">
                    <h5 class="fw-bold text-secondary mb-3"><i class="bi bi-cash-coin text-success"></i> Rincian Keuangan</h5>
                    <table class="table table-sm table-borderless mb-3">
                        <tr>
                            <td width="35%" class="fw-semibold text-muted">Nilai Bruto</td>
                            <td class="text-dark">: Rp {{ number_format($pengajuan->nilai_bruto, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Potongan Pajak</td>
                            <td class="text-danger">: Rp {{ number_format($pengajuan->potongan_pajak ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Nilai Neto (Riil)</td>
                            <td class="text-success fw-bold">: Rp {{ number_format($pengajuan->nilai_neto, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Folder Utama Drive</td>
                            <td class="text-dark">: 
                                <a href="{{ $pengajuan->link_google_drive }}" target="_blank" class="btn btn-primary btn-sm rounded-pill py-0 px-3 text-white small">
                                    <i class="bi bi-cloud-arrow-down-fill"></i> Buka Google Drive
                                </a>
                            </td>
                        </tr>
                        @if($pengajuan->bukti_penyerahan)
                        <tr>
                            <td class="fw-semibold text-muted">Bukti Penyerahan</td>
                            <td class="text-dark">: 
                                <a href="{{ $pengajuan->bukti_penyerahan }}" target="_blank" class="btn btn-success btn-sm rounded-pill py-0 px-3 text-white small">
                                    <i class="bi bi-file-earmark-check-fill"></i> Buka Bukti Google Drive
                                </a>
                            </td>
                        </tr>
                        @endif
                    </table>

                    <!-- Nomor SPM & SP2D jika ada -->
                    @if($pengajuan->no_spm || $pengajuan->no_sp2d)
                        <div class="border-top pt-2 mt-2">
                            <table class="table table-sm table-borderless mb-0 small">
                                @if($pengajuan->no_spm)
                                    <tr>
                                        <td width="35%" class="fw-semibold text-muted">Nomor SPM</td>
                                        <td class="text-dark">: <span class="badge bg-primary bg-opacity-10 text-primary px-2">{{ $pengajuan->no_spm }}</span></td>
                                    </tr>
                                @endif
                                @if($pengajuan->no_sp2d)
                                    <tr>
                                        <td class="fw-semibold text-muted">Nomor SP2D</td>
                                        <td class="text-dark">: <span class="badge bg-success bg-opacity-10 text-success px-2">{{ $pengajuan->no_sp2d }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Tanggal Cair</td>
                                        <td class="text-dark">: {{ \Carbon\Carbon::parse($pengajuan->tgl_cair)->format('d F Y') }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @php
            $dataDukungMap = [
                'GU/UP/TUP' => ['SPTB', 'Rincian POK', 'DRPP'],
                'LS Kontrak' => ['Surat pesanan', 'BA Serah Terima', 'Permintaan Pembayaran', 'BA Pembayaran', 'Kwitansi', 'SPTB'],
                'LS Non Kontrak' => ['Rincian POK', 'Npwp', 'Rekening', 'Surat pesanan', 'BA Serah Terima', 'Permintaan Pembayaran', 'BA Pembayaran', 'Kwitansi', 'SPTB'],
                'LS banyak penerima' => ['SPTB', 'SK', 'Rincian POK', 'Pendaftaran suplier', 'Rekap pengajuan'],
                'LS Bendahara' => ['SPTB', 'SK/SPT', 'Rincian POK', 'Daftar pembayaran'],
            ];
            $dataDukungList = json_decode($pengajuan->data_dukung_json, true) ?? [];
            if (empty($dataDukungList) && isset($dataDukungMap[$pengajuan->kategori_pengajuan])) {
                foreach ($dataDukungMap[$pengajuan->kategori_pengajuan] as $doc) {
                    $dataDukungList[] = ['nama_dokumen' => $doc, 'link_drive' => $pengajuan->link_google_drive];
                }
            }
        @endphp

        <!-- DAFTAR DATA DUKUNG DOKUMEN WAJIB -->
        <div class="card border-primary border-opacity-25 bg-light p-4 mb-4 shadow-sm">
            <h5 class="fw-bold text-dark mb-3">
                <i class="bi bi-folder-symlink-fill text-primary me-2"></i> Berkas Data Dukung Wajib ({{ $pengajuan->kategori_pengajuan }})
            </h5>
            <div class="row g-3">
                @if(count($dataDukungList) > 0)
                    @foreach($dataDukungList as $idx => $doc)
                        <div class="col-md-6">
                            <div class="p-3 bg-white border rounded-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="badge bg-primary bg-opacity-10 text-primary me-2">{{ $idx + 1 }}</span>
                                    <strong class="text-dark small">{{ $doc['nama_dokumen'] ?? '' }}</strong>
                                </div>
                                @if(!empty($doc['link_drive']))
                                    <a href="{{ $doc['link_drive'] }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 small">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Tautan
                                    </a>
                                @else
                                    <span class="badge bg-secondary">Belum diunggah</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="col-12 text-muted small">Tidak ada data dukung tambahan khusus.</div>
                @endif
            </div>
        </div>

        @if($pengajuan->catatan_koreksi)
            <div class="alert alert-danger shadow-sm rounded-3 mb-4">
                <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Catatan Koreksi Perbaikan Berkas:</h6>
                <p class="mb-0 small">{{ $pengajuan->catatan_koreksi }}</p>
            </div>
        @endif

        <hr class="text-muted opacity-25">

        <!-- PANEL TINDAKAN VERIFIKATOR -->
        @if(Auth::user()->role == 'Verifikator Keuangan' && $pengajuan->status == 'Menunggu Verifikasi')
            <div class="card card-custom border-warning border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check text-warning"></i> Panel Verifikasi Administrasi Keuangan</h5>
                <form action="{{ route('pengajuan.verifikasi', $pengajuan->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Checklist Verifikasi Kelengkapan Dokumen Data Dukung ({{ $pengajuan->kategori_pengajuan }}):</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input border-secondary" type="checkbox" id="checkAkun" required> 
                            <label class="form-check-label small fw-semibold text-dark" for="checkAkun">
                                Kesesuaian Nomor Akun DIPA ({{ $pengajuan->no_akun }}) & Ketersediaan Pagu Anggaran
                            </label>
                        </div>
                        @foreach($dataDukungList as $cIdx => $cDoc)
                            <div class="form-check mb-2">
                                <input class="form-check-input border-secondary" type="checkbox" id="checkDoc_{{ $cIdx }}" required> 
                                <label class="form-check-label small" for="checkDoc_{{ $cIdx }}">
                                    Kelengkapan & Kesesuaian Berkas: <strong>{{ $cDoc['nama_dokumen'] ?? '' }}</strong>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Catatan Koreksi (Wajib diisi jika mengembalikan berkas/revisi)</label>
                        <textarea name="catatan_koreksi" class="form-control" rows="2" placeholder="Tulis catatan perbaikan di sini jika ada berkas yang kurang/salah..."></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="setuju" class="btn btn-success rounded-pill px-4 shadow-sm">
                            <i class="bi bi-check-circle-fill"></i> Setujui & Teruskan ke PPK
                        </button>
                        <button type="submit" name="action" value="perbaiki" class="btn btn-warning rounded-pill px-4 shadow-sm">
                            <i class="bi bi-arrow-counterclockwise"></i> Kembalikan ke Bidang (Revisi)
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- PANEL TINDAKAN PPK -->
        @if(Auth::user()->role == 'PPK' && $pengajuan->status == 'Disetujui PPK')
            <div class="card card-custom border-primary border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-person text-primary"></i> Panel Persetujuan Akhir Komitmen (PPK)</h5>
                <form action="{{ route('pengajuan.ppkApproval', $pengajuan->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Catatan PPK (Opsional)</label>
                        <textarea name="catatan_koreksi" class="form-control" rows="2" placeholder="Tulis arahan atau catatan tambahan dari PPK..."></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="setuju" class="btn btn-primary rounded-pill px-4 shadow-sm">
                            <i class="bi bi-pencil-fill"></i> Beri Persetujuan Finansial
                        </button>
                        <button type="submit" name="action" value="tolak" class="btn btn-danger rounded-pill px-4 shadow-sm">
                            <i class="bi bi-x-circle-fill"></i> Tolak & Kembalikan ke Bidang
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- PANEL TINDAKAN OPERATOR PEMBAYARAN -->
        @if(Auth::user()->role == 'Operator Pembayaran' && $pengajuan->status == 'Diajukan ke SAKTI')
            <div class="card card-custom border-dark border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-send-fill text-dark"></i> Panel Input Nomor SPM (Aplikasi SAKTI)</h5>
                <form action="{{ route('pengajuan.realisasi', $pengajuan->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nomor SPM SAKTI</label>
                        <input type="text" name="no_spm" class="form-control" placeholder="Masukkan nomor SPM SAKTI (Contoh: 26054X...)" required>
                    </div>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 shadow-sm">
                        <i class="bi bi-save-fill"></i> Simpan Nomor SPM
                    </button>
                </form>
            </div>
        @endif

        <!-- PANEL TINDAKAN BENDAHARA (KONFIRMASI CAIR) -->
        @if(Auth::user()->role == 'Bendahara' && $pengajuan->status == 'Belum Terbit SP2D')
            <div class="card card-custom border-success border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-cash-coin text-success"></i> Panel Konfirmasi Pencairan & Nomor SP2D</h5>
                <form action="{{ route('pengajuan.realisasi', $pengajuan->id) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold text-secondary">Nomor SP2D (Sakti)</label>
                            <input type="text" name="no_sp2d" class="form-control" placeholder="Masukkan nomor SP2D dari KPPN..." required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold text-secondary">Tanggal Pembayaran / Cair</label>
                            <input type="date" name="tgl_cair" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm mt-2">
                        <i class="bi bi-check-circle-fill"></i> Sudah Cair (Proses Pencairan Selesai)
                    </button>
                </form>
            </div>
        @endif

        <!-- PANEL PENYERAHAN UANG (BENDAHARA) -->
        @if(Auth::user()->role == 'Bendahara' && $pengajuan->status == 'Dicairkan')
            <div class="card card-custom border-success border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-cash-stack text-success"></i> Panel Penyerahan Uang & Upload Bukti Serah Terima</h5>
                <form action="{{ route('pengajuan.realisasi', $pengajuan->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Link Google Drive Tanda Bukti Penyerahan / Kuitansi Terima</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-google"></i></span>
                            <input type="url" name="bukti_penyerahan" class="form-control border-0 shadow-sm" placeholder="Contoh: https://drive.google.com/..." required>
                        </div>
                        <div class="form-text text-muted small mt-2">
                            Unggah berkas tanda bukti penyerahan uang (misal: scan kuitansi/tanda terima) ke Google Drive Anda, lalu masukkan linknya di atas.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm mt-2">
                        <i class="bi bi-check-circle-fill"></i> Konfirmasi Uang Diserahkan (Proses Selesai)
                    </button>
                </form>
            </div>
        @endif

    </div>
@endsection