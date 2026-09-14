{{-- File: resources/views/pengajuan/excel.blade.php --}}
<table border="1">
    <thead>
        <tr>
            <th colspan="9" style="font-size: 16px; font-weight: bold; text-align: center;">
                REKAPITULASI PENGAJUAN PEMBAYARAN - BPVP SURAKARTA
            </th>
        </tr>
        <tr>
            <th>No Pengajuan</th>
            <th>Tanggal</th>
            <th>Bidang</th>
            <th>Nama Kegiatan</th>
            <th>Jenis Belanja</th>
            <th>Nilai Bruto (Rp)</th>
            <th>Nilai Neto (Rp)</th>
            <th>No SPM</th>
            <th>Status Akhir</th>
        </tr>
    </thead>
    <tbody>
        @foreach($daftarPengajuan as $p)
            <tr>
                <td>{{ $p->no_pengajuan }}</td>
                <td>{{ $p->tgl_pengajuan }}</td>
                <td>{{ $p->bidang }}</td>
                <td>{{ $p->nama_kegiatan }}</td>
                <td>{{ $p->jenis_belanja }}</td>
                <td>{{ $p->nilai_bruto }}</td>
                <td>{{ $p->nilai_neto }}</td>
                <td>{{ $p->no_spm ?? '-' }}</td>
                <td>{{ $p->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>