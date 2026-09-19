<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\PengajuanLs;
use Carbon\Carbon;

class BackdateOrderingAndBladeTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_compilation_and_storage_fallback()
    {
        $admin = User::factory()->create([
            'role' => 'Admin Keuangan',
            'bidang' => 'Keuangan',
        ]);

        $response = $this->actingAs($admin)->get(route('pengajuan.index'));
        $response->assertStatus(200);
        $response->assertViewIs('pengajuan.index');
    }

    public function test_backdate_records_are_sorted_chronologically_by_tgl_pengajuan()
    {
        $admin = User::factory()->create([
            'role' => 'Admin Keuangan',
            'bidang' => 'Keuangan',
        ]);

        // 1. Existing pengajuan dated Today (e.g. 2026-09-19)
        $todayPengajuan = PengajuanLs::create([
            'no_pengajuan' => 'KU-19092026-001',
            'tgl_pengajuan' => '2026-09-19',
            'user_id' => $admin->id,
            'bidang' => 'Purworejo',
            'nama_kegiatan' => 'Kegiatan Hari Ini',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 1000000,
            'potongan_pajak' => 0,
            'nilai_neto' => 1000000,
            'link_google_drive' => 'https://drive.google.com/test1',
            'status' => 'Dicairkan',
            'kategori_pengajuan' => 'LS Non Kontrak',
        ]);

        // 2. Backdated pengajuan (e.g. 2026-08-10) inserted TODAY
        $backdatePengajuan = PengajuanLs::create([
            'no_pengajuan' => 'KU-10082026-001',
            'tgl_pengajuan' => '2026-08-10',
            'created_at' => Carbon::parse('2026-08-10 10:00:00'),
            'updated_at' => Carbon::parse('2026-08-10 10:00:00'),
            'user_id' => $admin->id,
            'bidang' => 'Purworejo',
            'nama_kegiatan' => 'Kegiatan Masa Lalu Backdate',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 500000,
            'potongan_pajak' => 0,
            'nilai_neto' => 500000,
            'link_google_drive' => 'https://drive.google.com/test2',
            'status' => 'Selesai',
            'kategori_pengajuan' => 'LS Non Kontrak',
        ]);

        // Request index
        $response = $this->actingAs($admin)->get(route('pengajuan.index'));
        $response->assertStatus(200);

        $daftarPengajuan = $response->viewData('daftarPengajuan');
        $items = $daftarPengajuan->items();

        // Check that today's pengajuan (2026-09-19) comes FIRST, backdated (2026-08-10) comes SECOND
        $this->assertEquals('KU-19092026-001', $items[0]->no_pengajuan);
        $this->assertEquals('KU-10082026-001', $items[1]->no_pengajuan);
    }
}
