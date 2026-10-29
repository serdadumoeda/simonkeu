{{-- File: resources/views/banprog/cetak.blade.php --}}
@php
    $list = $termin === 2 ? \App\Models\PengajuanBanprog::CHECKLIST_TERMIN2 : \App\Models\PengajuanBanprog::CHECKLIST_TERMIN1;
    $cek = ($termin === 2 ? $b->checklist_termin2_json : $b->checklist_termin1_json) ?? [];
    $highlight = $termin === 1 ? \App\Models\PengajuanBanprog::CHECKLIST_HIGHLIGHT : [];
    $tglPengajuan = $termin === 2 ? $b->tgl_pengajuan_termin2 : $b->tgl_pengajuan_termin1;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checklist Termin {{ $termin }} - {{ $b->nama_lpks }}</title>
    <style>
        @page { size: A4 portrait; margin: 15mm; }
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #000; }
        h3 { text-align: center; margin: 0 0 4px; font-size: 13pt; }
        .sub { text-align: center; margin-bottom: 16px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        .ident td { padding: 3px 4px; vertical-align: top; }
        .list th, .list td { border: 1px solid #000; padding: 5px 6px; }
        .list th { background: #e5e7eb; }
        .hl { background: #fef08a; }
        .c { text-align: center; }
        .ttd { margin-top: 30px; width: 40%; margin-left: auto; text-align: center; }
        .no-print { text-align: center; margin: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print"><button onclick="window.print()">🖨 Cetak</button></div>
    <h3>CHECKLIST PENGAJUAN TERMIN {{ $termin }}</h3>
    <div class="sub">BANPROG LPKS BINAAN BPVP SURAKARTA</div>

    <table class="ident">
        <tr><td width="35%">Nama LPKS</td><td width="2%">:</td><td>{{ $b->nama_lpks }}</td></tr>
        <tr><td>Kabupaten/Kota</td><td>:</td><td>{{ $b->kabupaten_kota }}</td></tr>
        <tr><td>Nama Kepala LPKS</td><td>:</td><td>{{ $b->nama_kepala_lpks }}</td></tr>
        <tr><td>No HP Kepala LPKS</td><td>:</td><td>{{ $b->no_hp_kepala_lpks }}</td></tr>
        <tr><td>Kejuruan</td><td>:</td><td>{{ $b->kejuruan }}</td></tr>
        <tr><td>Tanggal Pelaksanaan Pelatihan</td><td>:</td><td>{{ optional($b->tgl_pelaksanaan_mulai)->format('d/m/Y') }} @if($b->tgl_pelaksanaan_selesai) s.d. {{ $b->tgl_pelaksanaan_selesai->format('d/m/Y') }} @endif</td></tr>
        <tr><td>Tanggal Pengajuan Termin {{ $termin }}</td><td>:</td><td>{{ optional($tglPengajuan)->format('d/m/Y') }}</td></tr>
        <tr><td>Nilai Termin {{ $termin }}</td><td>:</td><td>Rp {{ number_format($termin === 2 ? $b->nilai_termin_2 : $b->nilai_termin_1, 0, ',', '.') }}</td></tr>
    </table>
    <br>
    <table class="list">
        <thead>
            <tr><th width="6%">No</th><th>Uraian</th><th width="10%">Ada</th><th width="10%">Tidak Ada</th><th width="12%">Jumlah</th><th width="10%">Ket</th></tr>
        </thead>
        <tbody>
            @foreach($list as $no => $ur)
                <tr class="{{ in_array($no, $highlight) ? 'hl' : '' }}">
                    <td class="c">{{ $no }}</td>
                    <td>{{ $ur }}</td>
                    <td class="c">{{ in_array($no, $cek) ? '✔' : '' }}</td>
                    <td class="c">{{ in_array($no, $cek) ? '' : '✘' }}</td>
                    <td class="c">2 Rangkap</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p><strong>Catatan:</strong> {{ $b->catatan_revisi }}</p>

    <div class="ttd">
        Surakarta, {{ now()->translatedFormat('d F Y') }}<br>
        Diperiksa oleh,<br><br><br><br><br>
        (..................................)
    </div>
</body>
</html>
