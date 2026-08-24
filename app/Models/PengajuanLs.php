<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanLs extends Model
{
    use HasFactory;

    // Supaya Laravel tahu nama tabel kita
    protected $table = 'pengajuan_ls';

    // Kolom-kolom yang boleh diisi (Mass Assignment protection)
    protected $fillable = [
        'no_pengajuan',
        'tgl_pengajuan',
        'user_id',
        'bidang',
        'nama_kegiatan',
        'no_akun',
        'jenis_belanja',
        'nilai_bruto',
        'potongan_pajak',
        'nilai_neto',
        'uraian_pembayaran',
        'link_google_drive',
        'data_dukung_json',
        'pic_uptd_id',
        'status',
        'tgl_spm',
        'bukti_penyerahan',
        'kategori_pengajuan'
    ];

    // Relasi ke tabel User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function picUptd()
    {
        return $this->belongsTo(User::class, 'pic_uptd_id');
    }
    public function verifikator()
    {
        return $this->belongsTo(User::class, 'verifikator_id');
    }
    public function ppk()
    {
        return $this->belongsTo(User::class, 'ppk_id');
    }
    public function operatorPembayaran()
    {
        return $this->belongsTo(User::class, 'operator_pembayaran_id');
    }
    public function bendahara()
    {
        return $this->belongsTo(User::class, 'bendahara_id');
    }
}
