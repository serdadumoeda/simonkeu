<?php

namespace Tests\Feature;

use App\Models\PengajuanBanprog as B;
use App\Models\PengajuanLs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BanprogWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function mk(string $name, string $role, string $bidang = 'Keuangan', bool $banprog = false): User
    {
        return User::create([
            'name' => $name, 'email' => strtolower($name) . '@bpvp.go.id', 'password' => bcrypt('password'),
            'role' => $role, 'bidang' => $bidang, 'akses_banprog' => $banprog,
        ]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'nama_lpks' => 'LPKS Maju Jaya', 'kabupaten_kota' => 'Sukoharjo',
            'nama_kepala_lpks' => 'Pak Budi', 'no_hp_kepala_lpks' => '081234567890',
            'kejuruan' => 'Menjahit', 'nilai_banprog' => 10000000,
            'link_drive_termin1' => 'https://drive.google.com/t1',
            'dok' => [1 => 1, 2 => 1], 'action' => 'ajukan',
        ], $extra);
    }

    private function allChecks(array $list): array
    {
        return array_fill_keys(array_keys($list), 1);
    }

    public function test_full_banprog_workflow_termin1_to_termin2(): void
    {
        $pemohon = $this->mk('Pemohon_BP', 'Operator Bidang', 'Pemberdayaan', true);
        $verif = $this->mk('Verif_BP', 'Verifikator Keuangan');
        $ppk = $this->mk('Ppk_BP', 'PPK', 'None');
        $opbay = $this->mk('Opbay_BP', 'Operator Pembayaran');
        $bendahara = $this->mk('Bend_BP', 'Bendahara');

        // Pengajuan
        $this->actingAs($pemohon)->get(route('banprog.create'))->assertOk();
        $this->actingAs($pemohon)->post(route('banprog.store'), $this->payload())->assertRedirect();
        $b = B::first();
        $this->assertEquals(B::S_VERIF_T1, $b->status);
        $this->assertEquals(7000000, (float) $b->nilai_termin_1);
        $this->assertEquals(3000000, (float) $b->nilai_termin_2);
        $this->assertDatabaseHas('notifications', ['user_id' => $verif->id, 'title' => 'Banprog Baru Menunggu Verifikasi']);

        // Verifikasi T1
        $this->actingAs($verif)->get(route('banprog.show', $b->id))->assertOk()->assertSee('Setujui → PPK', false);
        $this->actingAs($verif)->post(route('banprog.verifikasi', $b->id), ['keputusan' => 'setuju', 'cek' => $this->allChecks(B::CHECKLIST_TERMIN1)]);
        $this->assertEquals(B::S_PPK_T1, $b->fresh()->status);

        // PPK T1
        $this->actingAs($ppk)->post(route('banprog.ppkApproval', $b->id), ['keputusan' => 'setuju']);
        $this->assertEquals(B::S_SPM_T1, $b->fresh()->status);

        // SPM & SP2D T1
        $this->actingAs($opbay)->post(route('banprog.inputSpm', $b->id), ['no_spm' => 'SPM-T1', 'tgl_spm' => date('Y-m-d')]);
        $this->assertEquals(B::S_SP2D_T1, $b->fresh()->status);
        $this->actingAs($bendahara)->post(route('banprog.pencairan', $b->id), ['no_sp2d' => 'SP2D-T1', 'tgl_cair' => date('Y-m-d')]);
        $b->refresh();
        $this->assertEquals(B::S_LAPORAN, $b->status);
        $this->assertNotNull($b->tgl_cair_t1);
        $this->assertDatabaseHas('notifications', ['user_id' => $pemohon->id, 'title' => 'Banprog Termin 1 Cair']);

        // Laporan
        $this->actingAs($pemohon)->post(route('banprog.uploadLaporan', $b->id), [
            'link_drive_laporan' => 'https://drive.google.com/lap', 'dok' => $this->allChecks(B::CHECKLIST_TERMIN2),
        ]);
        $this->assertEquals(B::S_VERIF_T2, $b->fresh()->status);

        // Termin 2
        $this->actingAs($verif)->post(route('banprog.verifikasi', $b->id), ['keputusan' => 'setuju', 'cek' => $this->allChecks(B::CHECKLIST_TERMIN2)]);
        $this->assertEquals(B::S_PPK_T2, $b->fresh()->status);
        $this->actingAs($ppk)->post(route('banprog.ppkApproval', $b->id), ['keputusan' => 'setuju']);
        $this->assertEquals(B::S_SPM_T2, $b->fresh()->status);
        $this->actingAs($opbay)->post(route('banprog.inputSpm', $b->id), ['no_spm' => 'SPM-T2', 'tgl_spm' => date('Y-m-d')]);
        $this->actingAs($bendahara)->post(route('banprog.pencairan', $b->id), ['no_sp2d' => 'SP2D-T2', 'tgl_cair' => date('Y-m-d')]);
        $b->refresh();
        $this->assertEquals(B::S_SELESAI, $b->status);
        $this->assertEquals(100, $b->progress);
        $this->assertGreaterThanOrEqual(10, count($b->histori_json));

        // Halaman tetap bisa dibuka
        $this->actingAs($pemohon)->get(route('banprog.index'))->assertOk()->assertSee('LPKS Maju Jaya');
        $this->actingAs($pemohon)->get(route('banprog.show', $b->id))->assertOk();
        $this->actingAs($verif)->get(route('banprog.cetak', [$b->id, 'termin' => 2]))->assertOk()->assertSee('CHECKLIST PENGAJUAN TERMIN 2');
    }

    public function test_verifikator_can_return_with_partial_checklist_but_cannot_approve(): void
    {
        $pemohon = $this->mk('Pemohon_BP', 'Operator Bidang', 'Pemberdayaan', true);
        $verif = $this->mk('Verif_BP', 'Verifikator Keuangan');
        $this->actingAs($pemohon)->post(route('banprog.store'), $this->payload());
        $b = B::first();

        // Setujui dengan checklist tidak lengkap → ditolak
        $this->actingAs($verif)->post(route('banprog.verifikasi', $b->id), ['keputusan' => 'setuju', 'cek' => [1 => 1]])
            ->assertSessionHas('error');
        $this->assertEquals(B::S_VERIF_T1, $b->fresh()->status);

        // Kembalikan tanpa catatan → gagal validasi
        $this->actingAs($verif)->post(route('banprog.verifikasi', $b->id), ['keputusan' => 'revisi', 'cek' => [1 => 1]])
            ->assertSessionHasErrors('catatan');

        // Kembalikan dengan checklist sebagian + catatan → berhasil
        $this->actingAs($verif)->post(route('banprog.verifikasi', $b->id), ['keputusan' => 'revisi', 'cek' => [1 => 1, 2 => 1], 'catatan' => 'PKS belum ditandatangani']);
        $b->refresh();
        $this->assertEquals(B::S_PERBAIKAN, $b->status);
        $this->assertEquals([1, 2], $b->checklist_termin1_json);
        $this->assertDatabaseHas('notifications', ['user_id' => $pemohon->id, 'title' => 'Banprog Perlu Perbaikan']);

        // Pemohon ajukan ulang
        $this->actingAs($pemohon)->post(route('banprog.ajukan', $b->id), ['link_drive' => 'https://drive.google.com/rev']);
        $b->refresh();
        $this->assertEquals(B::S_VERIF_T1, $b->status);
        $this->assertEquals('https://drive.google.com/rev', $b->link_drive_termin1);
    }

    public function test_draft_and_access_control(): void
    {
        $pemohon = $this->mk('Pemohon_BP', 'Operator Bidang', 'Pemberdayaan', true);
        $tanpaAkses = $this->mk('Biasa_BP', 'Operator Bidang', 'Pemberdayaan', false);
        $lain = $this->mk('Lain_BP', 'Operator Bidang', 'Umum', true);
        $ppk = $this->mk('Ppk_BP', 'PPK', 'None');

        $this->actingAs($pemohon)->post(route('banprog.store'), $this->payload(['action' => 'draft']));
        $b = B::first();
        $this->assertEquals(B::S_DRAFT, $b->status);

        // Operator tanpa sub-role Banprog
        $this->actingAs($tanpaAkses)->get(route('banprog.index'))->assertForbidden();
        $this->actingAs($tanpaAkses)->get(route('banprog.create'))->assertForbidden();
        // Bidang lain tidak boleh lihat
        $this->actingAs($lain)->get(route('banprog.show', $b->id))->assertForbidden();
        // PPK tidak bisa verifikasi
        $this->actingAs($ppk)->post(route('banprog.verifikasi', $b->id), ['keputusan' => 'setuju'])->assertForbidden();
        // PPK tidak bisa approve saat status bukan tahap PPK
        $this->actingAs($ppk)->post(route('banprog.ppkApproval', $b->id), ['keputusan' => 'setuju'])->assertStatus(422);
        // Laporan tidak bisa sebelum Termin 1 cair
        $this->actingAs($pemohon)->post(route('banprog.uploadLaporan', $b->id), ['link_drive_laporan' => 'https://x.com'])->assertStatus(422);

        // Pemohon bisa hapus draft
        $this->actingAs($pemohon)->delete(route('banprog.destroy', $b->id))->assertRedirect(route('banprog.index'));
        $this->assertDatabaseCount('pengajuan_banprog', 0);
    }

    public function test_admin_can_toggle_banprog_subrole(): void
    {
        $admin = $this->mk('Admin_BP', 'Admin Keuangan');
        $op = $this->mk('Op_BP', 'Operator Bidang', 'Umum');

        $this->actingAs($admin)->put(route('users.update', $op->id), [
            'name' => 'Op_BP', 'email' => 'op_bp@bpvp.go.id', 'role' => 'Operator Bidang',
            'bidang' => 'Umum', 'no_wa' => '628111', 'akses_banprog' => '1',
        ])->assertRedirect(route('users.index'));
        $this->assertTrue($op->fresh()->akses_banprog);

        // Role non-Operator Bidang tidak menyimpan sub-role
        $this->actingAs($admin)->put(route('users.update', $op->id), [
            'name' => 'Op_BP', 'email' => 'op_bp@bpvp.go.id', 'role' => 'PPK',
            'bidang' => 'None', 'no_wa' => '628111', 'akses_banprog' => '1',
        ]);
        $this->assertFalse($op->fresh()->akses_banprog);
    }

    public function test_ls_verification_panel_checkboxes_not_required_for_return(): void
    {
        $pemohon = $this->mk('Pemohon_LS', 'Operator Bidang', 'Penyelenggara');
        $verif = $this->mk('Verif_LS', 'Verifikator Keuangan');
        $p = PengajuanLs::create([
            'no_pengajuan' => 'KU-LS-REV-01', 'tgl_pengajuan' => now(), 'user_id' => $pemohon->id,
            'bidang' => 'Penyelenggara', 'nama_kegiatan' => 'Kegiatan Revisi', 'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium', 'nilai_bruto' => 1000000, 'nilai_neto' => 900000,
            'link_google_drive' => 'https://drive.google.com/x', 'status' => 'Menunggu Verifikasi',
            'kategori_pengajuan' => 'LS Bendahara',
        ]);

        $html = $this->actingAs($verif)->get(route('pengajuan.show', $p->id))->assertOk()->getContent();
        $this->assertStringContainsString('id="verificationForm"', $html);
        // Checkbox verifikasi tidak lagi memiliki atribut required
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*verify-checkbox[^"]*"[^>]*required/', $html);
        $this->assertStringContainsString('Belum sesuai: ', $html);

        // Server: kembalikan ke bidang dengan catatan tetap berhasil
        $this->actingAs($verif)->post(route('pengajuan.verifikasi', $p->id), [
            'action' => 'perbaiki', 'catatan_koreksi' => 'salah kategori — Belum sesuai: SK/SPT.',
        ]);
        $this->assertEquals('Perlu Perbaikan', $p->fresh()->status);
    }
}
