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
        if ($pengajuan->verifikator || in_array($status, ['Proses Persetujuan PPK', 'Diajukan ke SAKTI', 'Belum Terbit SP2D', 'Dicairkan', 'Selesai'])) {
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
        } elseif ($status == 'Proses Persetujuan PPK') {
            $step3_class = 'active';
        } elseif ($status == 'Perlu Perbaikan' && $pengajuan->ppk_id) {
            $step3_class = 'warning';
        } else {
            $step3_class = 'pending';
        }
        
        // Step 4: SPP (Penerbitan SPP - Operator Pembayaran)
        if ($pengajuan->sppOperator || in_array($status, ['Diajukan ke SAKTI', 'Belum Terbit SP2D', 'Dicairkan', 'Selesai'])) {
            $step4_class = 'completed';
        } elseif (in_array($status, ['Penerbitan SPP', 'SPP Menunggu TTD UPTD'])) {
            $step4_class = 'active';
        } else {
            $step4_class = 'pending';
        }
        
        // Step 5: SPM (Proses SAKTI - PPSPM)
        if ($pengajuan->operatorPembayaran || in_array($status, ['Belum Terbit SP2D', 'Dicairkan', 'Selesai'])) {
            $step5_class = 'completed';
        } elseif ($status == 'Diajukan ke SAKTI') {
            $step5_class = 'active';
        } else {
            $step5_class = 'pending';
        }
        
        // Step 6: Cair
        if (in_array($status, ['Dicairkan', 'Selesai'])) {
            $step6_class = 'completed';
        } elseif ($status == 'Belum Terbit SP2D') {
            $step6_class = 'active';
        } else {
            $step6_class = 'pending';
        }

        // Step 7: Serah Terima
        if ($status == 'Selesai') {
            $step7_class = 'completed';
        } elseif ($status == 'Dicairkan') {
            $step7_class = 'active';
        } else {
            $step7_class = 'pending';
        }

        // Step SPJ 8: Upload SPJ Verifikator
        $spjStatus = $pengajuan->spj_status ?? 'Belum Upload';
        if (in_array($spjStatus, ['Menunggu Upload Pemohon', 'Menunggu Verifikasi SPJ', 'SPJ Lengkap'])) {
            $step8_class = 'completed-green';
        } elseif ($status == 'Selesai' && $spjStatus == 'Belum Upload') {
            $step8_class = 'active-green';
        } else {
            $step8_class = 'pending';
        }

        // Step SPJ 9: Upload SPJ Pemohon
        if (in_array($spjStatus, ['Menunggu Verifikasi SPJ', 'SPJ Lengkap'])) {
            $step9_class = 'completed-green';
        } elseif ($spjStatus == 'Menunggu Upload Pemohon') {
            $step9_class = 'active-green';
        } else {
            $step9_class = 'pending';
        }

        // Step SPJ 10: Verifikasi SPJ
        if ($spjStatus == 'SPJ Lengkap') {
            $step10_class = 'completed-green';
        } elseif ($spjStatus == 'Menunggu Verifikasi SPJ') {
            $step10_class = 'active-green';
        } else {
            $step10_class = 'pending';
        }

        // Calculate progress width for stepper line (7 main steps + 3 SPJ steps)
        $progress_width = '0%';
        if ($step10_class == 'completed-green') { $progress_width = '95%'; }
        elseif ($step9_class == 'completed-green') { $progress_width = '87%'; }
        elseif ($step8_class == 'completed-green') { $progress_width = '80%'; }
        elseif ($step7_class == 'completed') { $progress_width = '72%'; }
        elseif ($step6_class == 'completed') { $progress_width = '60%'; }
        elseif ($step5_class == 'completed') { $progress_width = '48%'; }
        elseif ($step4_class == 'completed') { $progress_width = '38%'; }
        elseif ($step3_class == 'completed') { $progress_width = '28%'; }
        elseif ($step2_class == 'completed') { $progress_width = '18%'; }
        elseif ($step1_class == 'completed') { $progress_width = '5%'; }

        // SPJ Deadline warning for Pemohon (5 Hari sejak Verifikator upload)
        $spjDeadlineWarning = null;
        if ($pengajuan->spj_deadline && $spjStatus != 'SPJ Lengkap') {
            $nowP = \Carbon\Carbon::now();
            $deadlineP = \Carbon\Carbon::parse($pengajuan->spj_deadline);
            $diffHoursP = $nowP->diffInHours($deadlineP, false);
            $diffMinutesP = $nowP->diffInMinutes($deadlineP, false) % 60;

            if ($diffHoursP < 0 || ($diffHoursP == 0 && $diffMinutesP < 0)) {
                $overdueDaysP = (int) round(abs($nowP->diffInDays($deadlineP)));
                $spjDeadlineWarning = [
                    'level' => 'danger',
                    'badge' => '🚨 TERLAMBAT UPLOAD SPJ (> 5 HARI)',
                    'text' => '🚨 TERLAMBAT! Upload dokumen SPJ Lengkap telah MELEBIHI BATAS WAKTU 5 HARI (' . ($overdueDaysP > 0 ? 'Lewat ' . $overdueDaysP . ' hari!' : 'Lewat beberapa jam!') . ').',
                    'is_overdue' => true
                ];
            } else {
                $sisaDaysP = floor($diffHoursP / 24);
                $sisaHoursP = $diffHoursP % 24;
                $spjDeadlineWarning = [
                    'level' => 'success',
                    'badge' => '🟢 ⏱️ DALAM BATAS WAKTU 5 HARI',
                    'text' => '⏱️ Batas waktu 5 hari upload SPJ Lengkap tersisa ' . $sisaDaysP . ' hari ' . $sisaHoursP . ' jam lagi (Batas: ' . $deadlineP->format('d/m/Y H:i') . ').',
                    'is_overdue' => false
                ];
            }
        }
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
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm" id="btnToggleSplitScreen" onclick="toggleSplitScreen()">
                    <i class="bi bi-layout-split me-1"></i> Mode Split-Screen
                </button>
                <a href="{{ route('pengajuan.index') }}" class="btn btn-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <a href="{{ route('pengajuan.cetak', $pengajuan->id) }}" target="_blank" class="btn btn-dark btn-sm rounded-pill px-3">
                    <i class="bi bi-printer"></i> Cetak Ringkasan
                </a>
            </div>
        </div>

        <div class="row g-4" id="splitScreenRow">
            <div class="col-12" id="splitScreenLeft">

        <!-- PETUNJUK TINDAKAN SELANJUTNYA & NOTIFIKASI WHATSAPP SPESIFIK AKTOR -->
        @php
            $actionTitle = "";
            $actionDesc = "";
            $actionRole = "";
            $actionBadge = "bg-primary text-white";
            $targetActorUser = null;

            if ($status == 'Draft') {
                $actionTitle = "Draft Pengajuan Pembayaran";
                $actionDesc = "Berkas masih tersimpan sebagai draft. Pemohon silakan memeriksa kelengkapan data lalu klik Ajukan.";
                $actionRole = "Pemohon";
                $actionBadge = "bg-secondary text-white";
                $targetActorUser = $pengajuan->user;
            } elseif ($status == 'Menunggu Verifikasi') {
                $actionTitle = "Verifikasi Kelengkapan Administrasi Keuangan";
                $actionDesc = "Berkas berada di antrean Verifikator Keuangan (" . ($pengajuan->picUptd->name ?? 'Tim Verifikator') . "). Perlu pemeriksaan kelengkapan dokumen data dukung.";
                $actionRole = "Verifikator Keuangan";
                $actionBadge = "bg-warning text-dark";
                $targetActorUser = $pengajuan->picUptd ?? \App\Models\User::where('role', 'Verifikator Keuangan')->first();
            } elseif ($status == 'Perlu Perbaikan') {
                $actionTitle = "Perbaikan Berkas & Pengajuan Ulang";
                $actionDesc = "Berkas dikembalikan untuk diperbaiki. Pemohon (" . ($pengajuan->user->name ?? 'Pemohon') . ") silakan cek Catatan Koreksi dan ajukan ulang.";
                $actionRole = "Pemohon";
                $actionBadge = "bg-danger text-white";
                $targetActorUser = $pengajuan->user;
            } elseif ($status == 'Proses Persetujuan PPK') {
                $actionTitle = "Persetujuan Finansial oleh PPK";
                $actionDesc = "Berkas disetujui Verifikator dan kini menunggu persetujuan finansial dari Pejabat Pembuat Komitmen (PPK).";
                $actionRole = "PPK";
                $actionBadge = "bg-info text-dark";
                $targetActorUser = $pengajuan->ppk ?? \App\Models\User::where('role', 'PPK')->first();
            } elseif ($status == 'Penerbitan SPP') {
                $actionTitle = "Penerbitan SPP oleh Operator Pembayaran";
                $actionDesc = "Berkas disetujui PPK. Operator Pembayaran perlu menerbitkan SPP dan mengunggah link dokumen SPP.";
                $actionRole = "Operator Pembayaran";
                $actionBadge = "bg-primary bg-opacity-75 text-white";
                $targetActorUser = $pengajuan->sppOperator ?? \App\Models\User::where('role', 'Operator Pembayaran')->first();
            } elseif ($status == 'SPP Menunggu TTD UPTD') {
                if ($pengajuan->spp_signed_link) {
                    $actionTitle = "Validasi SPP Bertandatangan oleh Operator Pembayaran";
                    $actionDesc = "SPP bertandatangan telah diunggah UPTD. Operator Pembayaran perlu memvalidasi dan melanjutkan ke proses SAKTI.";
                    $actionRole = "Operator Pembayaran";
                    $actionBadge = "bg-success text-white";
                    $targetActorUser = $pengajuan->sppOperator ?? \App\Models\User::where('role', 'Operator Pembayaran')->first();
                } else {
                    $actionTitle = "Tanda Tangan SPP oleh UPTD (Tanpa Cap Basah)";
                    $actionDesc = "SPP (No. " . ($pengajuan->no_spp ?? '-') . ") telah diterbitkan. UPTD perlu download dokumen SPP, tanda tangani, lalu unggah kembali.";
                    $actionRole = "Pemohon UPTD";
                    $actionBadge = "bg-warning text-dark";
                    $targetActorUser = $pengajuan->user;
                }
            } elseif ($status == 'Diajukan ke SAKTI') {
                $actionTitle = "Proses SAKTI / SPM oleh PPSPM";
                $actionDesc = "SPP telah diterbitkan (" . ($pengajuan->no_spp ?? '-') . "). PPSPM perlu memproses SPM di SAKTI dan menginputkan Nomor SPM.";
                $actionRole = "PPSPM (Operator Pembayaran)";
                $actionBadge = "bg-primary text-white";
                $targetActorUser = $pengajuan->operatorPembayaran ?? \App\Models\User::where('role', 'Operator Pembayaran')->first();
            } elseif ($status == 'Belum Terbit SP2D') {
                $actionTitle = "Konfirmasi Pencairan SP2D KPPN";
                $actionDesc = "Nomor SPM sudah terbit (" . ($pengajuan->no_spm ?? '-') . "). Bendahara perlu memasukkan Nomor SP2D & Konfirmasi Tanggal Cair.";
                $actionRole = "Bendahara";
                $actionBadge = "bg-dark text-white";
                $targetActorUser = $pengajuan->bendahara ?? \App\Models\User::where('role', 'Bendahara')->first();
            } elseif ($status == 'Dicairkan') {
                $actionTitle = "Penyerahan Uang & Upload Tanda Terima";
                $actionDesc = "Dana SP2D telah cair. Bendahara menyerahkan uang dan mengunggah Tanda Bukti Penyerahan.";
                $actionRole = "Bendahara & Pemohon";
                $actionBadge = "bg-success text-white";
                $targetActorUser = $pengajuan->user ?? $pengajuan->bendahara;
            } elseif ($status == 'Selesai') {
                if ($spjStatus == 'Belum Upload') {
                    $actionTitle = "Upload Dokumen SPM & SP2D oleh Verifikator";
                    $actionDesc = "Pencairan selesai. Verifikator Keuangan mengunggah dokumen SPM, SP2D, dan SPP ke Google Drive.";
                    $actionRole = "Verifikator Keuangan";
                    $actionBadge = "bg-primary text-white";
                    $targetActorUser = \App\Models\User::where('role', 'Verifikator Keuangan')->first();
                } elseif ($spjStatus == 'Menunggu Upload Pemohon') {
                    $actionTitle = "Upload SPJ Lengkap oleh Pemohon (Batas 5 Hari)";
                    $actionDesc = "Verifikator telah mengunggah SPM/SP2D. Pemohon (" . ($pengajuan->user->name ?? 'Pemohon') . ") wajib mengunggah SPJ Lengkap.";
                    $actionRole = "Pemohon";
                    $actionBadge = "bg-info text-dark";
                    $targetActorUser = $pengajuan->user;
                } elseif ($spjStatus == 'Menunggu Verifikasi SPJ') {
                    $actionTitle = "Verifikasi Akhir Dokumen SPJ Lengkap (Batas 2 Hari)";
                    $actionDesc = "Pemohon telah mengunggah SPJ. Verifikator Keuangan perlu memverifikasi kelengkapan berkas SPJ.";
                    $actionRole = "Verifikator Keuangan";
                    $actionBadge = "bg-warning text-dark";
                    $targetActorUser = \App\Models\User::where('role', 'Verifikator Keuangan')->first();
                } else {
                    $actionTitle = "Penatausahaan SPJ Selesai 100%";
                    $actionDesc = "Seluruh rangkaian pencairan keuangan dan pertanggungjawaban SPJ telah selesai 100% (Lengkap & Verified).";
                    $actionRole = "Selesai";
                    $actionBadge = "bg-success text-white";
                    $targetActorUser = $pengajuan->user;
                }
            }

            $waUrl = $pengajuan->getWhatsappNotificationUrl($targetActorUser);
            $targetNameStr = $targetActorUser->name ?? $actionRole;
            $targetPhoneNum = $targetActorUser->no_wa ?? '-';
        @endphp

        <div class="card border-0 shadow-sm rounded-4 bg-primary bg-opacity-10 p-3 mb-4 border-start border-primary border-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white p-3 rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                        <i class="bi bi-lightbulb-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-bold text-dark fs-6">💡 Petunjuk Tindakan Selanjutnya:</span>
                            <span class="badge {{ $actionBadge }} rounded-pill px-2.5 py-1 small">PJ: {{ $actionRole }}</span>
                        </div>
                        <h6 class="fw-bold text-primary mb-0">{{ $actionTitle }}</h6>
                        <p class="text-muted small mb-0">{{ $actionDesc }}</p>
                    </div>
                </div>
                <div>
                    <a href="{{ $waUrl }}" target="_blank" class="btn btn-success rounded-pill px-3 py-2 shadow-sm d-flex align-items-center gap-2 fw-semibold text-white" title="Kirim notifikasi WhatsApp ke {{ $targetNameStr }} ({{ $targetPhoneNum }})">
                        <i class="bi bi-whatsapp fs-5"></i>
                        <span>Kirim Notifikasi WA ({{ $targetNameStr }})</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- VISUAL STEPPER TIMELINE (6 step utama + 3 step SPJ hijau) -->
        <h5 class="fw-bold text-dark mb-4 text-center">
            <i class="bi bi-geo-alt-fill text-primary"></i> Posisi Berkas SPJ Saat Ini
        </h5>
        
        <div class="table-responsive py-2 mb-3">
            <div class="stepper-container" style="min-width: 900px;">
                <div class="stepper-line"></div>
                <div class="stepper-line-progress" style="width: {{ $progress_width }};"></div>

            <!-- Step 1 -->
            <div class="stepper-item {{ $step1_class }}">
                <div class="stepper-icon">
                    <i class="bi bi-file-earmark-plus-fill"></i>
                </div>
                <div class="stepper-label">Pemohon</div>
                <div class="stepper-sublabel text-truncate" style="max-width: 110px;" title="{{ $pengajuan->user->name ?? '' }}">
                    {{ $pengajuan->user->name ?? 'Pemohon' }}
                </div>
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
                <div class="stepper-sublabel text-truncate" style="max-width: 110px;">
                    @if($step2_class == 'completed')
                        {{ $pengajuan->verifikator->name ?? '✓ Disetujui' }}
                    @elseif($step2_class == 'warning')
                        ⚠️ Perlu Revisi
                    @elseif($step2_class == 'active')
                        ⏳ Proses Verifikasi
                    @else
                        Belum Dimulai
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
                <div class="stepper-sublabel text-truncate" style="max-width: 110px;">
                    @if($step3_class == 'completed')
                        {{ $pengajuan->ppk->name ?? '✓ Disetujui PPK' }}
                    @elseif($step3_class == 'warning')
                        ⚠️ Ditolak PPK
                    @elseif($step3_class == 'active')
                        ⏳ Proses PPK
                    @else
                        Belum Dimulai
                    @endif
                </div>
            </div>

            <!-- Step 4: Penerbitan SPP -->
            <div class="stepper-item {{ $step4_class }}">
                <div class="stepper-icon">
                    @if($step4_class == 'completed') <i class="bi bi-check2"></i>
                    @else <i class="bi bi-file-earmark-text"></i>
                    @endif
                </div>
                <div class="stepper-label">Penerbitan SPP</div>
                <div class="stepper-sublabel text-truncate" style="max-width: 110px;">
                    @if($step4_class == 'completed')
                        {{ $pengajuan->sppOperator->name ?? ($pengajuan->no_spp ? 'SPP: ' . $pengajuan->no_spp : '✓ SPP Terbit') }}
                    @elseif($step4_class == 'active' && $status == 'SPP Menunggu TTD UPTD')
                        ⏳ Menunggu TTD UPTD
                    @elseif($step4_class == 'active')
                        ⏳ Proses SPP
                    @else
                        Belum Dimulai
                    @endif
                </div>
            </div>

            <!-- Step 5: Proses SAKTI / SPM (PPSPM) -->
            <div class="stepper-item {{ $step5_class }}">
                <div class="stepper-icon">
                    @if($step5_class == 'completed') <i class="bi bi-check2"></i>
                    @else <i class="bi bi-send"></i>
                    @endif
                </div>
                <div class="stepper-label">SAKTI/SPM (PPSPM)</div>
                <div class="stepper-sublabel text-truncate" style="max-width: 110px;">
                    @if($step5_class == 'completed')
                        {{ $pengajuan->operatorPembayaran->name ?? ($pengajuan->no_spm ? 'SPM: ' . $pengajuan->no_spm : '✓ SPM Terbit') }}
                    @elseif($step5_class == 'active')
                        ⏳ Proses SAKTI
                    @else
                        Belum Dimulai
                    @endif
                </div>
            </div>

            <!-- Step 6: Pencairan -->
            <div class="stepper-item {{ $step6_class }}">
                <div class="stepper-icon">
                    @if($step6_class == 'completed') <i class="bi bi-cash-coin"></i>
                    @else <i class="bi bi-wallet2"></i>
                    @endif
                </div>
                <div class="stepper-label">Pencairan</div>
                <div class="stepper-sublabel text-truncate" style="max-width: 110px;">
                    @if($step6_class == 'completed')
                        {{ $pengajuan->bendahara->name ?? '✓ SP2D Cair' }}
                    @elseif($step6_class == 'active')
                        ⏳ Proses SP2D
                    @else
                        Belum Dimulai
                    @endif
                </div>
            </div>

            <!-- Step 7: Penyerahan -->
            <div class="stepper-item {{ $step7_class }}">
                <div class="stepper-icon">
                    @if($step7_class == 'completed') <i class="bi bi-check-circle-fill text-white"></i>
                    @else <i class="bi bi-cash-stack"></i>
                    @endif
                </div>
                <div class="stepper-label">Penyerahan</div>
                <div class="stepper-sublabel text-truncate" style="max-width: 110px;">
                    @if($step7_class == 'completed')
                        ✓ Uang Diserahkan
                    @elseif($step7_class == 'active')
                        ⏳ Siap Diserahkan
                    @else
                        Belum Dimulai
                    @endif
                </div>
            </div>

            <!-- Step 8: SPJ Verifikator (HIJAU) -->
            <div class="stepper-item {{ $step8_class }}">
                <div class="stepper-icon">
                    @if($step8_class == 'completed-green') <i class="bi bi-check2"></i>
                    @else <i class="bi bi-cloud-arrow-up"></i>
                    @endif
                </div>
                <div class="stepper-label">Upload SPM/SP2D</div>
                <div class="stepper-sublabel text-truncate" style="max-width: 110px;">
                    @if($step8_class == 'completed-green')
                        ✓ SPM/SP2D Uploaded
                    @elseif($step8_class == 'active-green')
                        ⏳ Upload SPM/SP2D
                    @else
                        Belum Dimulai
                    @endif
                </div>
            </div>

            <!-- Step 9: SPJ Pemohon (HIJAU) -->
            <div class="stepper-item {{ $step9_class }}">
                <div class="stepper-icon">
                    @if($step9_class == 'completed-green') <i class="bi bi-check2"></i>
                    @else <i class="bi bi-file-earmark-arrow-up"></i>
                    @endif
                </div>
                <div class="stepper-label">SPJ Pemohon</div>
                <div class="stepper-sublabel text-truncate" style="max-width: 110px;">
                    @if($step9_class == 'completed-green')
                        ✓ SPJ Diunggah
                    @elseif($step9_class == 'active-green')
                        ⏳ Upload SPJ (5 Hari)
                    @else
                        Belum Dimulai
                    @endif
                </div>
            </div>

            <!-- Step 10: Verifikasi SPJ (HIJAU) -->
            <div class="stepper-item {{ $step10_class }}">
                <div class="stepper-icon">
                    @if($step10_class == 'completed-green') <i class="bi bi-check2"></i>
                    @else <i class="bi bi-check2-square"></i>
                    @endif
                </div>
                <div class="stepper-label">Verifikasi SPJ</div>
                <div class="stepper-sublabel text-truncate" style="max-width: 110px;">
                    @if($step10_class == 'completed-green')
                        {{ $pengajuan->spjVerifiedBy->name ?? '✓ SPJ Lengkap 100%' }}
                    @elseif($step10_class == 'active-green')
                        ⏳ Verifikasi SPJ
                    @else
                        Belum Dimulai
                    @endif
                </div>
            </div>
            </div>

        </div>

        <!-- SPJ Deadline Warning -->
        @if($spjDeadlineWarning)
            <div class="alert alert-{{ $spjDeadlineWarning['level'] }} shadow-sm rounded-3 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-alarm-fill fs-5"></i>
                <div>
                    <strong>Peringatan Batas Waktu SPJ:</strong> {{ $spjDeadlineWarning['text'] }}
                </div>
            </div>
        @endif

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
                    @if($pengajuan->status == 'Selesai' && ($pengajuan->spj_status ?? 'Belum Upload') == 'SPJ Lengkap')
                        <span class="badge bg-success text-white px-3 py-2 rounded-pill shadow-sm small">
                            <i class="bi bi-check-all me-1"></i> Lengkap & Verified
                        </span>
                    @elseif($pengajuan->status == 'Selesai')
                        <span class="badge bg-primary text-white px-3 py-2 rounded-pill shadow-sm small">
                            <i class="bi bi-clock-history me-1"></i> Proses Penatausahaan SPJ ({{ $pengajuan->overall_progress_percent }}%)
                        </span>
                    @elseif($pengajuan->status == 'Dicairkan')
                        <span class="badge bg-info text-white px-3 py-2 rounded-pill shadow-sm small">
                            <i class="bi bi-cash-stack me-1"></i> Uang Diserahkan / Cair
                        </span>
                    @else
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm small">
                            <i class="bi bi-clock me-1"></i> Dalam Proses
                        </span>
                    @endif
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
                        @if($pengajuan->picUptd)
                        <tr>
                            <td class="fw-semibold text-muted">PIC Verifikator UPTD</td>
                            <td class="text-dark">: <span class="badge bg-primary bg-opacity-10 text-primary px-2">{{ $pengajuan->picUptd->name }}</span></td>
                        </tr>
                        @endif
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

                    <!-- Nomor SPP, SPM & SP2D jika ada -->
                    @if($pengajuan->no_spp || $pengajuan->no_spm || $pengajuan->no_sp2d)
                        <div class="border-top pt-2 mt-2">
                            <table class="table table-sm table-borderless mb-0 small">
                                @if($pengajuan->no_spp)
                                    <tr>
                                        <td width="35%" class="fw-semibold text-muted">Nomor SPP</td>
                                        <td class="text-dark">: <span class="badge bg-info bg-opacity-10 text-info px-2">{{ $pengajuan->no_spp }}</span>
                                            @if($pengajuan->tgl_spp)
                                                <span class="text-muted small ms-1">({{ \Carbon\Carbon::parse($pengajuan->tgl_spp)->format('d/m/Y') }})</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
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

        <!-- DAFTAR DATA DUKUNG DOKUMEN WAJIB (FORMAT VERTIKAL) -->
        <div class="card border-primary border-opacity-25 bg-light p-4 mb-4 shadow-sm">
            <h5 class="fw-bold text-dark mb-3">
                <i class="bi bi-folder-symlink-fill text-primary me-2"></i> Berkas Data Dukung Wajib ({{ $pengajuan->kategori_pengajuan }})
            </h5>
            <div class="row g-2">
                @if(count($dataDukungList) > 0)
                    @foreach($dataDukungList as $idx => $doc)
                        <div class="col-12">
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

        <!-- ========================================================= -->
        <!-- POIN 1: PENATAUSAHAAN SPJ (Section baru di bawah Data Dukung) -->
        <!-- ========================================================= -->
        @if($pengajuan->status == 'Selesai' || in_array($spjStatus, ['Menunggu Upload Pemohon', 'Menunggu Verifikasi SPJ', 'SPJ Lengkap']))
            <div class="card border-success border-opacity-25 bg-light p-4 mb-4 shadow-sm">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="bi bi-journal-check text-success me-2"></i> Penatausahaan SPJ
                    @if($spjStatus == 'SPJ Lengkap')
                        <span class="badge bg-success ms-2 rounded-pill"><i class="bi bi-check-circle-fill"></i> Lengkap & Terverifikasi</span>
                    @endif
                </h5>

                <!-- Status SPJ -->
                <div class="mb-3">
                    <span class="small fw-semibold text-muted">Status SPJ:</span>
                    @if($spjStatus == 'Belum Upload')
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-50 px-2 py-1 rounded-pill ms-2">⏳ Belum Upload</span>
                    @elseif($spjStatus == 'Menunggu Upload Pemohon')
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-50 px-2 py-1 rounded-pill ms-2"><i class="bi bi-upload"></i> Menunggu Upload Pemohon</span>
                    @elseif($spjStatus == 'Menunggu Verifikasi SPJ')
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-50 px-2 py-1 rounded-pill ms-2"><i class="bi bi-clock"></i> Menunggu Verifikasi SPJ</span>
                    @elseif($spjStatus == 'SPJ Lengkap')
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-50 px-2 py-1 rounded-pill ms-2"><i class="bi bi-check-circle-fill"></i> SPJ Lengkap</span>
                    @endif
                </div>

                <!-- Dokumen SPJ yang sudah diupload -->
                <div class="row g-2 mb-3">
                    <div class="col-12">
                        <div class="p-3 bg-white border rounded-3 d-flex align-items-center justify-content-between">
                            <div><i class="bi bi-file-earmark-pdf text-danger me-2"></i><strong class="small">Dokumen SP2D</strong></div>
                            @if($pengajuan->spj_sp2d_link)
                                <a href="{{ $pengajuan->spj_sp2d_link }}" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 small"><i class="bi bi-box-arrow-up-right me-1"></i> Buka</a>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Belum diupload</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 bg-white border rounded-3 d-flex align-items-center justify-content-between">
                            <div><i class="bi bi-file-earmark-text text-primary me-2"></i><strong class="small">Dokumen SPM</strong></div>
                            @if($pengajuan->spj_spm_link)
                                <a href="{{ $pengajuan->spj_spm_link }}" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 small"><i class="bi bi-box-arrow-up-right me-1"></i> Buka</a>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Belum diupload</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 bg-white border rounded-3 d-flex align-items-center justify-content-between">
                            <div><i class="bi bi-file-earmark-medical text-warning me-2"></i><strong class="small">Dokumen SPP</strong></div>
                            @if($pengajuan->spj_spp_link)
                                <a href="{{ $pengajuan->spj_spp_link }}" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 small"><i class="bi bi-box-arrow-up-right me-1"></i> Buka</a>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Belum diupload</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 bg-white border rounded-3 d-flex align-items-center justify-content-between">
                            <div><i class="bi bi-folder-fill text-success me-2"></i><strong class="small">SPJ Lengkap (Pemohon)</strong></div>
                            @if($pengajuan->spj_lengkap_link)
                                <a href="{{ $pengajuan->spj_lengkap_link }}" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 small"><i class="bi bi-box-arrow-up-right me-1"></i> Buka</a>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Belum diupload</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($pengajuan->spj_verified_at)
                    <div class="alert alert-success mb-0 py-2 small">
                        <i class="bi bi-patch-check-fill me-1"></i> SPJ diverifikasi pada <strong>{{ \Carbon\Carbon::parse($pengajuan->spj_verified_at)->format('d F Y H:i') }}</strong>
                    </div>
                @endif
            </div>
        @endif

        @if($pengajuan->catatan_koreksi)
            <div class="alert alert-danger shadow-sm rounded-3 mb-4">
                <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Catatan Koreksi Perbaikan Berkas:</h6>
                <p class="mb-0 small">{{ $pengajuan->catatan_koreksi }}</p>
            </div>
        @endif

        <!-- PANEL AJUKAN ULANG BERKAS UNTUK PEMOHON (JIKA PERLU PERBAIKAN) -->
        @if(
            Auth::user()->role == 'Operator Bidang'
            && $pengajuan->user_id == Auth::id()
            && $pengajuan->status == 'Perlu Perbaikan'
        )
            <div class="card card-custom border-danger border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-arrow-counterclockwise text-danger"></i> Panel Perbaikan & Pengajuan Ulang Berkas</h5>
                <form action="{{ route('pengajuan.resubmit', $pengajuan->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Tautan Google Drive Data Dukung (Dapat diperbarui jika ada berkas revisi)</label>
                        <input type="url" name="link_google_drive" class="form-control" value="{{ old('link_google_drive', $pengajuan->link_google_drive) }}" required>
                        <div class="form-text text-muted small">Pastikan berkas perbaikan sesuai catatan koreksi di atas telah diunggah ke Google Drive.</div>
                    </div>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 shadow-sm">
                        <i class="bi bi-send-check-fill me-1"></i> Simpan Perbaikan & Ajukan Ulang Berkas
                    </button>
                </form>
            </div>
        @endif

        <hr class="text-muted opacity-25">

        <!-- PANEL TINDAKAN VERIFIKATOR -->
        @if(Auth::user()->role == 'Verifikator Keuangan' && $pengajuan->status == 'Menunggu Verifikasi')
            <div class="card card-custom border-warning border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-shield-check text-warning"></i> Panel Verifikasi Administrasi Keuangan</h5>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="selectAllVerificationCheckboxes(true)">
                        <i class="bi bi-check-all me-1"></i> Centang Semua Dokumen (1-Click)
                    </button>
                </div>
                <form action="{{ route('pengajuan.verifikasi', $pengajuan->id) }}" method="POST" id="verificationForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Checklist Verifikasi Kelengkapan Dokumen Data Dukung ({{ $pengajuan->kategori_pengajuan }}):</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input border-secondary verify-checkbox" type="checkbox" id="checkAkun" required> 
                            <label class="form-check-label small fw-semibold text-dark" for="checkAkun">
                                Kesesuaian Nomor Akun DIPA ({{ $pengajuan->no_akun }}) & Ketersediaan Pagu Anggaran
                            </label>
                        </div>
                        @foreach($dataDukungList as $cIdx => $cDoc)
                            <div class="form-check mb-2">
                                <input class="form-check-input border-secondary verify-checkbox" type="checkbox" id="checkDoc_{{ $cIdx }}" required> 
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
        @if(Auth::user()->role == 'PPK' && $pengajuan->status == 'Proses Persetujuan PPK')
            <div class="card card-custom border-primary border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-person text-primary"></i> Panel Persetujuan Akhir Komitmen (PPK)</h5>
                
                <!-- PPK EXECUTIVE SUMMARY CARD -->
                <div class="p-3 bg-white border border-primary border-opacity-25 rounded-3 mb-3 shadow-sm">
                    <h6 class="fw-bold text-primary mb-2"><i class="bi bi-card-checklist me-1"></i> Ringkasan Eksekutif Finansial (PPK)</h6>
                    <div class="row g-2 small">
                        <div class="col-md-3">
                            <span class="text-muted d-block">Kegiatan:</span>
                            <strong class="text-dark">{{ $pengajuan->nama_kegiatan }}</strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block">Kategori & Akun:</span>
                            <strong class="text-dark">{{ $pengajuan->kategori_pengajuan }} ({{ $pengajuan->no_akun }})</strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block">Pajak / Potongan:</span>
                            <span class="text-danger fw-bold">Rp {{ number_format($pengajuan->potongan_pajak ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block">Nilai Neto Disetujui:</span>
                            <span class="text-success fw-bold fs-6">Rp {{ number_format($pengajuan->nilai_neto, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

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

        <!-- PANEL TINDAKAN PENERBITAN SPP (Operator Pembayaran) -->
        @if(Auth::user()->role == 'Operator Pembayaran' && $pengajuan->status == 'Penerbitan SPP')
            <div class="card card-custom border-primary border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-text-fill text-primary"></i> Panel Penerbitan SPP</h5>
                <p class="text-muted small mb-3">Berkas telah disetujui PPK. Silakan terbitkan SPP, unggah link dokumen SPP, dan masukkan nomor SPP di bawah ini.</p>
                <form action="{{ route('pengajuan.penerbitanSpp', $pengajuan->id) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold text-secondary">Nomor SPP</label>
                            <input type="text" name="no_spp" class="form-control" placeholder="Masukkan nomor SPP (Contoh: SPP-26054X...)" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold text-secondary">Link Dokumen SPP (Google Drive)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-google"></i></span>
                                <input type="url" name="spp_link" class="form-control border-0 shadow-sm" placeholder="https://drive.google.com/..." required>
                            </div>
                            <div class="form-text text-muted small mt-1">Link Google Drive dokumen SPP untuk didownload dan ditandatangani oleh UPTD.</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-save-fill"></i> Terbitkan SPP & Kirim ke UPTD untuk Ditandatangani
                    </button>
                </form>
            </div>
        @endif

        <!-- PANEL TANDA TANGAN SPP OLEH UPTD (Download, TTD, Upload Kembali) -->
        @if($pengajuan->status == 'SPP Menunggu TTD UPTD' && $pengajuan->user_id == Auth::id())
            <div class="card card-custom border-warning border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-pen-fill text-warning"></i> Panel Tanda Tangan SPP (UPTD)</h5>
                <p class="text-muted small mb-3">Dokumen SPP telah diterbitkan oleh Operator Pembayaran. Silakan download, tanda tangani <strong>(tanpa cap basah)</strong>, dan unggah kembali dokumen SPP bertandatangan.</p>

                {{-- Info SPP yang diterbitkan --}}
                <div class="alert alert-info py-2 mb-3 small">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <strong>No SPP:</strong> {{ $pengajuan->no_spp ?? '-' }} |
                    <strong>Tanggal:</strong> {{ $pengajuan->tgl_spp ? \Carbon\Carbon::parse($pengajuan->tgl_spp)->format('d/m/Y') : '-' }} |
                    <strong>Diterbitkan oleh:</strong> {{ $pengajuan->sppOperator->name ?? 'Operator Pembayaran' }}
                </div>

                {{-- Link Download SPP --}}
                @if($pengajuan->spp_link)
                    <div class="mb-3">
                        <a href="{{ $pengajuan->spp_link }}" target="_blank" class="btn btn-outline-primary rounded-pill px-4 shadow-sm">
                            <i class="bi bi-download me-1"></i> Download Dokumen SPP untuk Ditandatangani
                        </a>
                    </div>
                @endif

                {{-- Form Upload SPP Bertandatangan --}}
                @if(!$pengajuan->spp_signed_link)
                    <form action="{{ route('pengajuan.uploadSppUptd', $pengajuan->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Link SPP Bertandatangan (Google Drive)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted border-0 shadow-sm"><i class="bi bi-google"></i></span>
                                <input type="url" name="spp_signed_link" class="form-control border-0 shadow-sm" placeholder="https://drive.google.com/..." required>
                            </div>
                            <div class="form-text text-muted small mt-1">Unggah dokumen SPP yang telah ditandatangani (tanpa cap basah) ke Google Drive, lalu masukkan linknya.</div>
                        </div>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 shadow-sm text-dark fw-semibold">
                            <i class="bi bi-upload me-1"></i> Unggah SPP Bertandatangan
                        </button>
                    </form>
                @else
                    <div class="alert alert-success py-2 small">
                        <i class="bi bi-check-circle-fill me-1"></i> SPP bertandatangan telah diunggah. Menunggu validasi dari Operator Pembayaran.
                        <a href="{{ $pengajuan->spp_signed_link }}" target="_blank" class="ms-2"><i class="bi bi-box-arrow-up-right"></i> Lihat Dokumen</a>
                    </div>
                @endif
            </div>
        @endif

        <!-- PANEL VALIDASI SPP BERTANDATANGAN (Operator Pembayaran) -->
        @if(Auth::user()->role == 'Operator Pembayaran' && $pengajuan->status == 'SPP Menunggu TTD UPTD' && $pengajuan->spp_signed_link)
            <div class="card card-custom border-success border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-check2-circle text-success"></i> Panel Validasi SPP Bertandatangan</h5>
                <p class="text-muted small mb-3">SPP bertandatangan telah diunggah oleh UPTD. Periksa dokumen dan validasi untuk melanjutkan ke proses SAKTI (SPM).</p>

                {{-- Info SPP --}}
                <div class="alert alert-info py-2 mb-3 small">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <strong>No SPP:</strong> {{ $pengajuan->no_spp ?? '-' }} |
                    <strong>UPTD:</strong> {{ $pengajuan->user->name ?? '-' }} |
                    <strong>Diunggah:</strong> {{ $pengajuan->spp_signed_at ? \Carbon\Carbon::parse($pengajuan->spp_signed_at)->format('d/m/Y H:i') : '-' }}
                </div>

                <div class="d-flex gap-2 mb-3">
                    @if($pengajuan->spp_link)
                        <a href="{{ $pengajuan->spp_link }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-file-earmark-text me-1"></i> Lihat SPP Asli
                        </a>
                    @endif
                    <a href="{{ $pengajuan->spp_signed_link }}" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-3">
                        <i class="bi bi-file-earmark-check me-1"></i> Lihat SPP Bertandatangan UPTD
                    </a>
                </div>

                <form action="{{ route('pengajuan.validasiSppUptd', $pengajuan->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Catatan Evaluasi SPP (Wajib diisi jika dikembalikan/revisi)</label>
                        <textarea name="catatan" class="form-control border-0 shadow-sm" rows="2" placeholder="Tuliskan catatan hasil validasi atau alasan perbaikan dokumen SPP..."></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="setuju" class="btn btn-success rounded-pill px-4 shadow-sm fw-semibold">
                            <i class="bi bi-check-circle-fill me-1"></i> Validasi SPP & Lanjutkan ke SAKTI (SPM)
                        </button>
                        <button type="submit" name="action" value="perbaiki" class="btn btn-warning rounded-pill px-4 shadow-sm fw-semibold text-dark">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Tolak & Kembalikan ke UPTD
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- PANEL TINDAKAN OPERATOR PEMBAYARAN (PPSPM — INPUT SPM) -->
        @if(Auth::user()->role == 'Operator Pembayaran' && $pengajuan->status == 'Diajukan ke SAKTI')
            <div class="card card-custom border-dark border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-send-fill text-dark"></i> Panel Input Nomor SPM — PPSPM (Aplikasi SAKTI)</h5>
                @if($pengajuan->no_spp)
                    <div class="alert alert-info py-2 mb-3 small">
                        <i class="bi bi-info-circle-fill me-1"></i> SPP telah diterbitkan: <strong>{{ $pengajuan->no_spp }}</strong>
                        @if($pengajuan->tgl_spp)
                            ({{ \Carbon\Carbon::parse($pengajuan->tgl_spp)->format('d/m/Y') }})
                        @endif
                    </div>
                @endif
                <form action="{{ route('pengajuan.realisasi', $pengajuan->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nomor SPM SAKTI</label>
                        <input type="text" name="no_spm" class="form-control" placeholder="Masukkan nomor SPM SAKTI (Contoh: 26054X...)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Catatan Operator SPM (Opsional)</label>
                        <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan tambahan mengenai penerbitan SPM..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-dark rounded-pill px-4 shadow-sm">
                        <i class="bi bi-save-fill"></i> Simpan Nomor SPM & Teruskan ke Bendahara
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
                            <input type="text" name="no_sp2d" id="no_sp2d" class="form-control" placeholder="Contoh: 24053000123" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold text-secondary">Tanggal Pembayaran / Cair</label>
                            <input type="date" name="tgl_cair" id="tgl_cair" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label small fw-semibold text-secondary">
                                <i class="bi bi-link-45deg text-primary me-1"></i> Tautan Link Google Drive Dokumen SP2D <span class="text-danger">*</span>
                            </label>
                            <input type="url" name="spj_sp2d_link" class="form-control" placeholder="https://drive.google.com/..." value="{{ old('spj_sp2d_link', $pengajuan->spj_sp2d_link) }}" required>
                            <div class="form-text text-muted small">Masukkan tautan Google Drive dokumen SP2D resmi dari SAKTI/KPPN.</div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label small fw-semibold text-secondary">Catatan Bendahara (Opsional)</label>
                            <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan tambahan pencairan SP2D..."></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm mt-2">
                        <i class="bi bi-check-circle-fill"></i> Sudah Cair (Proses Pencairan Selesai)
                    </button>
                </form>
            </div>
        @endif

        <!-- PANEL PENYERAHAN UANG (BENDAHARA) -->
        @if(in_array($pengajuan->status, ['Dicairkan', 'Selesai']))
            <div class="card card-custom border-success border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-cash-stack text-success"></i> Panel Penyerahan Uang & Bukti Pembayaran</h5>
                    <a href="{{ route('pengajuan.cetak_bukti', $pengajuan->id) }}" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold">
                        <i class="bi bi-printer-fill me-1"></i> Cetak Bukti Penyerahan Uang
                    </a>
                </div>

                @if(Auth::user()->role == 'Bendahara' && $pengajuan->status == 'Dicairkan')
                    <form action="{{ route('pengajuan.realisasi', $pengajuan->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Link Google Drive Tanda Bukti Penyerahan / Kuitansi Terima <span class="text-danger">*</span></label>
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
                @endif
            </div>
        @endif

        @php
            $verifikatorDeadlineWarning = null;
            if ($pengajuan->verifikator_spm_deadline && in_array($spjStatus, ['Belum Upload'])) {
                $nowV = \Carbon\Carbon::now();
                $deadlineV = \Carbon\Carbon::parse($pengajuan->verifikator_spm_deadline);
                $diffHoursV = $nowV->diffInHours($deadlineV, false);
                $diffMinutesV = $nowV->diffInMinutes($deadlineV, false) % 60;
                
                if ($diffHoursV < 0 || ($diffHoursV == 0 && $diffMinutesV < 0)) {
                    $overdueDaysV = (int) round(abs($nowV->diffInDays($deadlineV)));
                    $verifikatorDeadlineWarning = [
                        'level' => 'danger',
                        'badge' => '🚨 TERLAMBAT UPLOAD SPM/SP2D',
                        'text' => 'Batas waktu 2 hari upload dokumen SPM/SP2D oleh Verifikator Keuangan telah TERLAMBAT ' . ($overdueDaysV > 0 ? $overdueDaysV . ' hari!' : 'beberapa jam!'),
                        'is_overdue' => true
                    ];
                } elseif ($diffHoursV < 24) {
                    $verifikatorDeadlineWarning = [
                        'level' => 'warning',
                        'badge' => '⚠️ SISA KERJA 1 HARI',
                        'text' => 'Batas waktu upload dokumen SPM/SP2D tersisa ' . max(1, $diffHoursV) . ' jam ' . abs($diffMinutesV) . ' menit lagi.',
                        'is_overdue' => false
                    ];
                } else {
                    $verifikatorDeadlineWarning = [
                        'level' => 'info',
                        'badge' => '⏱️ TENGGAT 2 HARI',
                        'text' => 'Batas waktu 2 hari upload dokumen SPM/SP2D tersisa ' . floor($diffHoursV / 24) . ' hari ' . ($diffHoursV % 24) . ' jam (Batas: ' . $deadlineV->format('d/m/Y H:i') . ').',
                        'is_overdue' => false
                    ];
                }
            }
        @endphp

        <!-- ========================================================= -->
        <!-- PANEL UPLOAD SPJ VERIFIKATOR (Setelah status Selesai) -->
        <!-- ========================================================= -->
        @if(
            (Auth::user()->role == 'Verifikator Keuangan' || Auth::user()->role == 'Admin Keuangan')
            && $pengajuan->status == 'Selesai'
            && in_array($spjStatus, ['Belum Upload'])
        )
            <div class="card card-custom border-success border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-cloud-arrow-up-fill text-success"></i> Panel Upload Dokumen SPJ (Verifikator)</h5>
                    @if($verifikatorDeadlineWarning)
                        <span class="badge bg-{{ $verifikatorDeadlineWarning['level'] }} px-3 py-1.5 rounded-pill fw-bold">
                            {{ $verifikatorDeadlineWarning['badge'] }}
                        </span>
                    @endif
                </div>
                <p class="text-muted small mb-3">Upload dokumen SP2D, SPM, dan SPP ke Google Drive lalu masukkan link-nya di bawah ini.</p>
                
                @if($verifikatorDeadlineWarning)
                    <div class="alert alert-{{ $verifikatorDeadlineWarning['level'] }} py-2.5 px-3 small mb-3 rounded-3 border">
                        <i class="bi bi-clock-history me-1.5 fs-6 align-middle"></i> <strong>Informasi Tenggat 2 Hari:</strong> {{ $verifikatorDeadlineWarning['text'] }}
                    </div>
                @endif
                <form action="{{ route('pengajuan.uploadSpjVerifikator', $pengajuan->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary"><i class="bi bi-link-45deg text-primary me-1"></i> Link Google Drive Dokumen SP2D <span class="text-danger">*</span></label>
                        <input type="url" name="spj_sp2d_link" class="form-control" placeholder="https://drive.google.com/..." value="{{ old('spj_sp2d_link', $pengajuan->spj_sp2d_link) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary"><i class="bi bi-link-45deg text-primary me-1"></i> Link Google Drive Dokumen SPM <span class="text-danger">*</span></label>
                        <input type="url" name="spj_spm_link" class="form-control" placeholder="https://drive.google.com/..." value="{{ old('spj_spm_link', $pengajuan->spj_spm_link) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary"><i class="bi bi-link-45deg text-primary me-1"></i> Link Google Drive Dokumen SPP <span class="text-danger">*</span></label>
                        <input type="url" name="spj_spp_link" class="form-control" placeholder="https://drive.google.com/..." value="{{ old('spj_spp_link', $pengajuan->spj_spp_link) }}" required>
                    </div>
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload & Kirim ke Pemohon
                    </button>
                </form>
            </div>
        @endif

        <!-- PANEL UPLOAD SPJ PEMOHON -->
        @if(
            $pengajuan->user_id == Auth::id()
            && $spjStatus == 'Menunggu Upload Pemohon'
        )
            <div class="card card-custom border-info border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-arrow-up-fill text-info"></i> Panel Upload SPJ Lengkap (Pemohon)</h5>
                    @if($spjDeadlineWarning && isset($spjDeadlineWarning['badge']))
                        <span class="badge bg-{{ $spjDeadlineWarning['level'] }} px-3 py-1.5 rounded-pill fw-bold">
                            {{ $spjDeadlineWarning['badge'] }}
                        </span>
                    @endif
                </div>
                <p class="text-muted small mb-3">Verifikator telah mengupload dokumen SP2D/SPM/SPP. Silakan upload SPJ lengkap Anda dalam batas waktu 5 hari.</p>
                @if($spjDeadlineWarning)
                    <div class="alert alert-{{ $spjDeadlineWarning['level'] }} py-2.5 px-3 small mb-3 rounded-3 border">
                        <i class="bi bi-alarm-fill me-1.5 fs-6 align-middle"></i> <strong>Informasi Tenggat SPJ 5 Hari:</strong> {{ $spjDeadlineWarning['text'] }}
                    </div>
                @endif
                <form action="{{ route('pengajuan.uploadSpjPemohon', $pengajuan->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary"><i class="bi bi-link-45deg text-primary me-1"></i> Link Google Drive SPJ Lengkap <span class="text-danger">*</span></label>
                        <input type="url" name="spj_lengkap_link" class="form-control" placeholder="https://drive.google.com/..." required>
                    </div>
                    <button type="submit" class="btn btn-info text-white rounded-pill px-4 shadow-sm">
                        <i class="bi bi-send-check-fill me-1"></i> Upload SPJ Lengkap
                    </button>
                </form>
            </div>
        @endif

        @php
            $spjVerifikatorDeadlineWarning = null;
            if ($pengajuan->spj_verifikator_deadline && $spjStatus == 'Menunggu Verifikasi SPJ') {
                $nowSV = \Carbon\Carbon::now();
                $deadlineSV = \Carbon\Carbon::parse($pengajuan->spj_verifikator_deadline);
                $diffHoursSV = $nowSV->diffInHours($deadlineSV, false);
                $diffMinutesSV = $nowSV->diffInMinutes($deadlineSV, false) % 60;
                
                if ($diffHoursSV < 0 || ($diffHoursSV == 0 && $diffMinutesSV < 0)) {
                    $overdueDaysSV = (int) round(abs($nowSV->diffInDays($deadlineSV)));
                    $spjVerifikatorDeadlineWarning = [
                        'level' => 'danger',
                        'badge' => '🚨 TERLAMBAT VERIFIKASI SPJ (> 2 HARI)',
                        'text' => '🚨 TERLAMBAT! Batas waktu 2 hari verifikasi SPJ oleh Verifikator Keuangan telah TERLAMBAT ' . ($overdueDaysSV > 0 ? $overdueDaysSV . ' hari!' : 'beberapa jam!'),
                        'is_overdue' => true
                    ];
                } elseif ($diffHoursSV < 24) {
                    $spjVerifikatorDeadlineWarning = [
                        'level' => 'warning',
                        'badge' => '⚠️ SISA KERJA 1 HARI',
                        'text' => 'Batas waktu verifikasi SPJ Lengkap tersisa ' . max(1, $diffHoursSV) . ' jam ' . abs($diffMinutesSV) . ' menit lagi.',
                        'is_overdue' => false
                    ];
                } else {
                    $spjVerifikatorDeadlineWarning = [
                        'level' => 'success',
                        'badge' => '🟢 ⏱️ TENGGAT 2 HARI VERIFIKASI',
                        'text' => 'Batas waktu 2 hari verifikasi SPJ Lengkap tersisa ' . floor($diffHoursSV / 24) . ' hari ' . ($diffHoursSV % 24) . ' jam (Batas: ' . $deadlineSV->format('d/m/Y H:i') . ').',
                        'is_overdue' => false
                    ];
                }
            }
        @endphp

        <!-- PANEL VERIFIKASI SPJ (Verifikator) -->
        @if(
            (Auth::user()->role == 'Verifikator Keuangan' || Auth::user()->role == 'Admin Keuangan')
            && $spjStatus == 'Menunggu Verifikasi SPJ'
        )
            <div class="card card-custom border-warning border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clipboard-check-fill text-warning"></i> Panel Verifikasi SPJ Lengkap</h5>
                    @if($spjVerifikatorDeadlineWarning)
                        <span class="badge bg-{{ $spjVerifikatorDeadlineWarning['level'] }} px-3 py-1.5 rounded-pill fw-bold">
                            {{ $spjVerifikatorDeadlineWarning['badge'] }}
                        </span>
                    @endif
                </div>
                <p class="text-muted small mb-3">Pemohon telah mengupload SPJ lengkap. Silakan verifikasi kelengkapannya dalam jangka waktu 2 hari.</p>
                
                @if($spjVerifikatorDeadlineWarning)
                    <div class="alert alert-{{ $spjVerifikatorDeadlineWarning['level'] }} py-2.5 px-3 small mb-3 rounded-3 border">
                        <i class="bi bi-clock-history me-1.5 fs-6 align-middle"></i> <strong>Informasi Tenggat Verifikasi 2 Hari:</strong> {{ $spjVerifikatorDeadlineWarning['text'] }}
                    </div>
                @endif
                @if($pengajuan->spj_lengkap_link)
                    <div class="mb-3 p-3 bg-white border rounded-3">
                        <strong class="small text-dark">SPJ Pemohon:</strong>
                        <a href="{{ $pengajuan->spj_lengkap_link }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3 ms-2"><i class="bi bi-box-arrow-up-right me-1"></i> Buka SPJ</a>
                    </div>
                @endif
                <form action="{{ route('pengajuan.verifikasiSpj', $pengajuan->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Catatan / Komentar Verifikator SPJ (Wajib diisi jika ditolak/perbaikan)</label>
                        <textarea name="catatan_spj" class="form-control" rows="2" placeholder="Tuliskan komentar hasil verifikasi kelengkapan SPJ..."></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="setuju" class="btn btn-success rounded-pill px-4 shadow-sm">
                            <i class="bi bi-check-circle-fill"></i> SPJ Lengkap & Terverifikasi
                        </button>
                        <button type="submit" name="action" value="perbaiki" class="btn btn-warning rounded-pill px-4 shadow-sm fw-semibold text-dark">
                            <i class="bi bi-arrow-counterclockwise"></i> Tolak & Kembalikan ke Pemohon
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- ========================================================= -->
        <!-- REKAM JEJAK KEPUTUSAN & CATATAN EVALUASI (TIMELINE AUDIT) -->
        <!-- ========================================================= -->
        <div class="card card-custom border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-clock-history text-primary me-2"></i> Rekam Jejak Keputusan & Catatan Evaluasi
                </h5>
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1 fw-bold">
                    {{ count($pengajuan->histori_catatan_json ?? []) }} Catatan
                </span>
            </div>
            <div class="card-body pt-0">
                @if(!empty($pengajuan->histori_catatan_json) && count($pengajuan->histori_catatan_json) > 0)
                    <div class="timeline position-relative ps-3 my-2 border-start border-2 border-primary border-opacity-25 ms-2">
                        @foreach(array_reverse($pengajuan->histori_catatan_json) as $h)
                            @php
                                $actionLower = strtolower($h['action'] ?? '');
                                $isSuccess = str_contains($actionLower, 'setuju') || str_contains($actionLower, 'terbit') || str_contains($actionLower, 'valid') || str_contains($actionLower, 'serah') || str_contains($actionLower, 'unggah') || str_contains($actionLower, ' verified');
                                $badgeClass = $isSuccess ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                            @endphp
                            <div class="timeline-item mb-3 position-relative ps-3">
                                <div class="d-flex justify-content-between align-items-start mb-1 flex-wrap gap-1">
                                    <div>
                                        <span class="fw-bold text-dark small me-2">{{ $h['tahap'] ?? '-' }}</span>
                                        <span class="badge {{ $badgeClass }} rounded-pill small" style="font-size: 10px;">{{ $h['action'] ?? '-' }}</span>
                                    </div>
                                    <span class="small text-muted" style="font-size: 11px;"><i class="bi bi-clock me-1"></i>{{ $h['created_at'] ?? '-' }}</span>
                                </div>
                                <div class="small text-secondary mb-1">
                                    <i class="bi bi-person-circle me-1"></i><strong>{{ $h['user_name'] ?? '-' }}</strong> ({{ $h['user_role'] ?? '-' }})
                                </div>
                                <div class="p-2.5 rounded-3 bg-white border small text-dark" style="font-size: 12px;">
                                    <i class="bi bi-chat-left-text-fill text-muted me-1.5"></i> {{ $h['catatan'] ?? '-' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-journal-text fs-3 d-block mb-1 text-secondary opacity-50"></i>
                        Belum ada catatan rekam jejak keputusan.
                    </div>
                @endif
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- POIN 2: PANEL ADMIN - EDIT TANGGAL & HAPUS PENGAJUAN -->
        <!-- ========================================================= -->
        @if(Auth::user()->role == 'Admin Keuangan')
            <div class="card card-custom border-danger border-top border-4 p-4 bg-light mb-4 shadow-sm">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-gear-fill text-danger"></i> Panel Admin Keuangan</h5>
                
                <div class="row">
                    <!-- Edit Tanggal -->
                    <div class="col-md-6 mb-3">
                        <form action="{{ route('pengajuan.adminEditDate', $pengajuan->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <label class="form-label small fw-semibold text-secondary">Edit Tanggal Pengajuan</label>
                            <div class="input-group">
                                <input type="date" name="tgl_pengajuan" class="form-control" value="{{ $pengajuan->tgl_pengajuan }}" required>
                                <button type="submit" class="btn btn-warning btn-sm rounded-end px-3">
                                    <i class="bi bi-pencil-fill"></i> Ubah Tanggal
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Hapus Pengajuan -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-semibold text-secondary">Hapus Histori Pengajuan</label>
                        <form action="{{ route('pengajuan.adminDelete', $pengajuan->id) }}" method="POST" onsubmit="return confirm('PERINGATAN KRITIS!\n\nAnda akan menghapus pengajuan {{ $pengajuan->no_pengajuan }} secara permanen.\n\nTindakan ini TIDAK DAPAT dibatalkan.\n\nApakah Anda yakin?');">
                            @csrf
                            @method('DELETE')
                                <i class="bi bi-trash3-fill me-1"></i> Hapus Pengajuan Ini
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
            </div> <!-- End #splitScreenLeft -->

            <!-- SISI KANAN: GOOGLE DRIVE EMBEDDED VIEWER (SPLIT SCREEN PANEL) -->
            <div class="col-lg-6 d-none" id="splitScreenRight">
                <div class="card border-0 shadow-lg rounded-4 bg-dark text-white p-3 h-100 sticky-top" style="top: 80px; z-index: 100;">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-secondary">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-display text-warning fs-5"></i>
                            <span class="fw-bold small text-white">Prinjau Berkas Google Drive</span>
                        </div>
                        <div class="d-flex gap-1 align-items-center">
                            <a id="splitDriveOpenNewTab" href="{{ $pengajuan->link_google_drive }}" target="_blank" class="btn btn-sm btn-outline-light rounded-pill py-0 px-2.5 small" style="font-size: 11px;">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Tab Baru
                            </a>
                            <button type="button" class="btn-close btn-close-white ms-2" onclick="toggleSplitScreen()"></button>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label text-white-50 extra-small fw-semibold mb-1" style="font-size: 10.5px;">PILIH BERKAS UNTUK DIPERIKSA:</label>
                        <select id="splitScreenDocSelect" class="form-select form-select-sm bg-secondary text-white border-0 small fw-semibold" onchange="changeSplitScreenDoc(this.value)">
                            <option value="{{ $pengajuan->link_google_drive }}" selected>📂 Folder Utama Google Drive SPJ</option>
                            @if(isset($dataDukungList) && count($dataDukungList) > 0)
                                @foreach($dataDukungList as $doc)
                                    @if(!empty($doc['link_drive']))
                                        <option value="{{ $doc['link_drive'] }}">📄 {{ $doc['nama_dokumen'] ?? 'Dokumen Dukung' }}</option>
                                    @endif
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="flex-grow-1 position-relative rounded overflow-hidden" style="min-height: 650px; background: #0f172a;">
                        <iframe id="splitScreenIframe" src="" style="width: 100%; height: 100%; min-height: 650px; border: none;" allow="autoplay"></iframe>
                    </div>
                </div>
            </div>
        </div> <!-- End #splitScreenRow -->

    </div>

    <script>
        function toggleSplitScreen() {
            const leftCol = document.getElementById('splitScreenLeft');
            const rightCol = document.getElementById('splitScreenRight');
            const btn = document.getElementById('btnToggleSplitScreen');
            const docSelect = document.getElementById('splitScreenDocSelect');

            if (!leftCol || !rightCol || !btn) return;

            const isSplit = !rightCol.classList.contains('d-none');

            if (isSplit) {
                rightCol.classList.add('d-none');
                leftCol.className = 'col-12';
                btn.classList.remove('btn-primary', 'text-white');
                btn.classList.add('btn-outline-primary');
                btn.innerHTML = '<i class="bi bi-layout-split me-1"></i> Mode Split-Screen';
                localStorage.setItem('simonKeu_splitScreen', 'off');
            } else {
                leftCol.className = 'col-lg-6 col-12';
                rightCol.classList.remove('d-none');
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-primary', 'text-white');
                btn.innerHTML = '<i class="bi bi-layout-split me-1"></i> Mode Layar Penuh';
                
                if (docSelect && docSelect.value) {
                    changeSplitScreenDoc(docSelect.value);
                }
                localStorage.setItem('simonKeu_splitScreen', 'on');
            }
        }

        function changeSplitScreenDoc(url) {
            const iframe = document.getElementById('splitScreenIframe');
            const openBtn = document.getElementById('splitDriveOpenNewTab');
            if (!url || !iframe) return;
            
            if (openBtn) openBtn.href = url;
            
            let embedUrl = url;
            const folderMatch = url.match(/\/folders\/([a-zA-Z0-9_-]+)/);
            if (folderMatch && folderMatch[1]) {
                embedUrl = 'https://drive.google.com/embeddedfolderview?id=' + folderMatch[1] + '#list';
            } else {
                const fileMatch = url.match(/\/file\/d\/([a-zA-Z0-9_-]+)/);
                if (fileMatch && fileMatch[1]) {
                    embedUrl = 'https://drive.google.com/file/d/' + fileMatch[1] + '/preview';
                }
            }
            iframe.src = embedUrl;
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('simonKeu_splitScreen') === 'on') {
                toggleSplitScreen();
            }
        });

    <script>
        function selectAllVerificationCheckboxes(state) {
            const checkboxes = document.querySelectorAll('.verify-checkbox');
            checkboxes.forEach(cb => cb.checked = state);
        }

        function printBuktiPenyerahanVoucher() {
            window.open("{{ route('pengajuan.cetak_bukti', $pengajuan->id) }}", "_blank");
        }
    </script>
@endsection