<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Penyerahan Uang - {{ $pengajuan->no_pengajuan }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            padding: 40px;
            font-size: 13px;
            color: #111;
            line-height: 1.5;
            background: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 25px;
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h3 {
            margin: 4px 0 0 0;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .header p {
            margin: 4px 0 0 0;
            font-size: 11px;
            color: #444;
        }
        .title-box {
            text-align: center;
            margin-bottom: 25px;
            text-decoration: underline;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 0.5px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 10px 14px;
            text-align: left;
        }
        .table th {
            background: #f8fafc;
            width: 32%;
            font-weight: 600;
            color: #334155;
        }
        .sig-table {
            width: 100%;
            text-align: center;
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .sig-table td {
            width: 50%;
            vertical-align: top;
        }
        .sig-space {
            height: 75px;
        }
        .btn-print {
            background: #0f172a;
            color: #fff;
            border: none;
            padding: 10px 22px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 30px;
            margin-bottom: 25px;
            font-size: 13px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-print:hover {
            background: #1e293b;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="text-align: right; margin-bottom: 15px;">
        <button onclick="window.print()" class="btn-print">🖨️ Cetak / Simpan PDF</button>
    </div>

    <div class="header">
        <h2>KEMENTERIAN KETENAGAKERJAAN REPUBLIK INDONESIA</h2>
        <h3>BALAI BESAR PELATIHAN VOKASI DAN PRODUKTIVITAS (BPVP) SURAKARTA</h3>
        <p>Jl. Brosot No.18, Serengan, Surakarta, Jawa Tengah 57156</p>
    </div>

    <div class="title-box">BUKTI TANDA TERIMA / PENYERAHAN UANG</div>

    <table class="table">
        <tr>
            <th>Nomor Pengajuan</th>
            <td><strong style="font-size: 14px;">{{ $pengajuan->no_pengajuan }}</strong></td>
        </tr>
        <tr>
            <th>Nomor SP2D</th>
            <td>{{ $pengajuan->no_sp2d ?? '-' }}</td>
        </tr>
        <tr>
            <th>Nomor SPM</th>
            <td>{{ $pengajuan->no_spm ?? '-' }}</td>
        </tr>
        <tr>
            <th>Bidang / Unit Kerja</th>
            <td>{{ $pengajuan->bidang }}</td>
        </tr>
        <tr>
            <th>Nama Kegiatan</th>
            <td>{{ $pengajuan->nama_kegiatan }}</td>
        </tr>
        <tr>
            <th>Jumlah Pembayaran (Neto)</th>
            <td><strong style="font-size: 16px; color: #15803d;">Rp {{ number_format($pengajuan->nilai_neto, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <th>Tanggal Penyerahan / Cair</th>
            <td>{{ $pengajuan->tgl_cair ? \Carbon\Carbon::parse($pengajuan->tgl_cair)->format('d F Y') : date('d F Y') }}</td>
        </tr>
        @if($pengajuan->catatan_koreksi)
        <tr>
            <th>Catatan Pencairan</th>
            <td>{{ $pengajuan->catatan_koreksi }}</td>
        </tr>
        @endif
    </table>

    <p style="margin-bottom: 30px;">Telah diserahkan dana sejumlah tersebut di atas untuk keperluan pelaksanaan kegiatan sebagaimana uraian di atas.</p>

    <table class="sig-table">
        <tr>
            <td>
                <p>Yang Menyerahkan,<br><strong>Bendahara Pengeluaran</strong></p>
                <div class="sig-space"></div>
                <p><strong>({{ $pengajuan->bendahara->name ?? 'Bendahara' }})</strong></p>
            </td>
            <td>
                <p>Yang Menerima,<br><strong>Pemohon / Penanggung Jawab</strong></p>
                <div class="sig-space"></div>
                <p><strong>({{ $pengajuan->user->name ?? 'Pemohon' }})</strong></p>
            </td>
        </tr>
    </table>
</body>
</html>
