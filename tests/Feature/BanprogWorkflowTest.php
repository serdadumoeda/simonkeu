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
            'kejuruan' => 'Menjahit', 'kategori_pengajuan' => 'Termin I',
            'nilai_banprog' => 10000000,
            'link_drive_termin1' => 'https://drive.google.com/t1',
            'dok' => [1 => 1, 2 => 1], 'action' => 'ajukan',
        ], $extra);
    }

    private function allChecks(array $list): array
    {
        return array_fill_keys(array_keys($list), 1);
    }

    public function test_full_banprog_workflow_same_as_pengajuan_ls(): void
    {
        $pemohon = $this->mk('SriSulis', 'Operator Bidang', 'Pemberdayaan', true);
        $verif = $this->mk('Verif_BP', 'Verifikator Keuangan');
        $ppk = $this->mk('Ppk_BP', 'PPK', 'None');
        $opbay = $this->mk('Opbay_BP', 'Operator Pembayaran');
        $bendahara = $this->mk('Bend_BP', 'Bendahara');

        // 1. Pemohon mengajukan (Termin I, nominal manual)
        $this->actingAs($pemohon)->get(route('banprog.create'))->assertOk();
        $this->actingAs($pemohon)->post(route('banprog.store'), $this->payload())->assertRedirect();
        $b = B::first();
        $this->assertEquals(B::S_VERIF, $b->status);
        $this->assertEquals('Termin I', $b->kategori_pengajuan);
        $this->assertEquals(10000000, (float) $b->nilai_banprog);
        $this->assertEquals(10000000, (float) $b->nilai_termin_1);
        $this->assertEquals(0, (float) $b->nilai_termin_2);
        $this->assertDatabaseHas('notifications', ['user_id' => $verif->id, 'title' => 'Banprog Baru Menunggu Verifikasi']);

        // 2. Verifikasi Keuangan
        $this->actingAs($verif)->get(route('banprog.show', $b->id))->assertOk()->assertSee('Setujui → PPK', false);
        $this->actingAs($verif)->post(route('banprog.verifikasi', $b->id), ['keputusan' => 'setuju', 'cek' => $this->allChecks(B::CHECKLIST_TERMIN1)]);
        $this->assertEquals(B::S_PPK, $b->fresh()->status);

        // 3. Persetujuan PPK
        $this->actingAs($ppk)->post(route('banprog.ppkApproval', $b->id), ['keputusan' => 'setuju']);
        $this->assertEquals(B::S_SPP, $b->fresh()->status);

        // 4. Penerbitan SPP (Operator Pembayaran)
        $this->actingAs($opbay)->post(route('banprog.penerbitanSpp', $b->id), ['no_spp' => 'SPP-01', 'tgl_spp' => date('Y-m-d')]);
        $this->assertEquals(B::S_SAKTI, $b->fresh()->status);

        // 5. SAKTI/SPM (Operator Pembayaran)
        $this->actingAs($opbay)->post(route('banprog.inputSpm', $b->id), ['no_spm' => 'SPM-T1', 'tgl_spm' => date('Y-m-d')]);
        $this->assertEquals(B::S_SP2D, $b->fresh()->status);

        // 6. Pencairan SP2D (Bendahara)
        $this->actingAs($bendahara)->post(route('banprog.pencairan', $b->id), ['no_sp2d' => 'SP2D-T1', 'tgl_cair' => date('Y-m-d')]);
        $this->assertEquals(B::S_CAIR, $b->fresh()->status);

        // 7. Penyerahan (Bendahara)
        $this->actingAs($bendahara)->post(route('banprog.penyerahan', $b->id), ['bukti_penyerahan' => 'Diserahkan ke LPKS']);
        $b->refresh();
        $this->assertEquals(B::S_SELESAI, $b->status);
        $this->assertEquals('Belum Upload', $b->spj_status);

        // 8. Upload SPM/SP2D (Verifikator / Operator Pembayaran)
        $this->actingAs($verif)->post(route('banprog.uploadSpjVerifikator', $b->id), ['spj_verifikator_link' => 'https://drive.google.com/spj-verif']);
        $this->assertEquals('Menunggu Upload Pemohon', $b->fresh()->spj_status);

        // 9. SPJ Pemohon
        $this->actingAs($pemohon)->post(route('banprog.uploadSpjPemohon', $b->id), ['spj_pemohon_link' => 'https://drive.google.com/spj-pemohon']);
        $this->assertEquals('Menunggu Verifikasi SPJ', $b->fresh()->spj_status);

        // 10. Verifikasi SPJ
        $this->actingAs($verif)->post(route('banprog.verifikasiSpj', $b->id), ['keputusan' => 'setuju', 'catatan' => 'Lengkap']);
        $b->refresh();
        $this->assertEquals('SPJ Lengkap', $b->spj_status);
        $this->assertEquals(100, $b->progress);

        // Halaman tetap bisa dibuka
        $this->actingAs($pemohon)->get(route('banprog.index'))->assertOk()->assertSee('LPKS Maju Jaya');
        $this->actingAs($pemohon)->get(route('banprog.show', $b->id))->assertOk();
        $this->actingAs($verif)->get(route('banprog.cetak', [$b->id, 'termin' => 1]))->assertOk()->assertSee('CHECKLIST PENGAJUAN TERMIN 1');
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

    public function test_manual_termin_nominals_and_kategori_termin_ii(): void
    {
        $pemohon = $this->mk('Herna', 'Operator Bidang', 'Pemberdayaan', true);
        $verif = $this->mk('Verif_BP_T2', 'Verifikator Keuangan');

        // 1. Pengajuan Termin I dengan nominal manual (tanpa nama kepala/no hp/tgl pelaksanaan)
        $this->actingAs($pemohon)->post(route('banprog.store'), $this->payload([
            'kategori_pengajuan' => 'Termin I',
            'nilai_banprog' => 32132660,
        ]))->assertRedirect();

        $b1 = B::latest('id')->first();
        $this->assertEquals('Termin I', $b1->kategori_pengajuan);
        $this->assertEquals(32132660, (float) $b1->nilai_banprog);
        $this->assertEquals(32132660, (float) $b1->nilai_termin_1);
        $this->assertEquals(0, (float) $b1->nilai_termin_2);
        $this->assertNull($b1->nama_kepala_lpks);
        $this->assertEquals(B::S_VERIF, $b1->status);

        // 2. Pengajuan Termin II terpisah dengan nominal manual
        $this->actingAs($pemohon)->post(route('banprog.store'), $this->payload([
            'kategori_pengajuan' => 'Termin II',
            'nilai_banprog' => 13771140,
            'link_drive_termin1' => 'https://drive.google.com/t2-langsung',
            'dok' => $this->allChecks(B::CHECKLIST_TERMIN2),
        ]))->assertRedirect();

        $b2 = B::latest('id')->first();
        $this->assertEquals('Termin II', $b2->kategori_pengajuan);
        $this->assertEquals(13771140, (float) $b2->nilai_banprog);
        $this->assertEquals(0, (float) $b2->nilai_termin_1);
        $this->assertEquals(13771140, (float) $b2->nilai_termin_2);
        $this->assertEquals(B::S_VERIF, $b2->status);
        $this->assertEquals('https://drive.google.com/t2-langsung', $b2->link_drive_laporan);
        $this->assertNull($b2->link_drive_termin1);
        $this->assertCount(count(B::CHECKLIST_TERMIN2), $b2->checklist_termin2_json);

        // Verifikator menyetujui Termin II -> masuk PPK
        $this->actingAs($verif)->post(route('banprog.verifikasi', $b2->id), [
            'keputusan' => 'setuju',
            'cek' => $this->allChecks(B::CHECKLIST_TERMIN2),
        ]);
        $this->assertEquals(B::S_PPK, $b2->fresh()->status);
    }
}
