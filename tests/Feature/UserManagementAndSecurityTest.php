<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PengajuanLs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can access users index and create a new user with role and bidang.
     */
    public function test_admin_can_manage_users(): void
    {
        // 1. Create admin user
        $admin = User::create([
            'name' => 'Siti_Admin',
            'email' => 'admin@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Admin Keuangan',
            'bidang' => 'Keuangan',
        ]);

        $this->actingAs($admin);

        // 2. Access users page
        $response = $this->get(route('users.index'));
        $response->assertStatus(200);

        // 3. Post to create a user
        $newUserData = [
            'name' => 'New_Operator',
            'email' => 'operator@bpvp.go.id',
            'password' => 'password123',
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
            'no_wa' => '628123456789',
        ];

        $response = $this->post(route('users.store'), $newUserData);
        $response->assertStatus(302); // Redirect back

        // 4. Verify user exists in database and has correct role/bidang
        $this->assertDatabaseHas('users', [
            'name' => 'New_Operator',
            'email' => 'operator@bpvp.go.id',
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);
    }

    /**
     * Test non-admin cannot access users index.
     */
    public function test_operator_cannot_access_users_management(): void
    {
        $operator = User::create([
            'name' => 'Budi_Penyelenggara',
            'email' => 'budi@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);

        $this->actingAs($operator);

        $response = $this->get(route('users.index'));
        $response->assertStatus(403);

        $response = $this->post(route('users.store'), [
            'name' => 'Unauthorized_User',
            'email' => 'unauth@bpvp.go.id',
            'password' => 'password123',
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);
        $response->assertStatus(403);
    }

    /**
     * Test role-based protection on verification.
     */
    public function test_operator_cannot_verify_pengajuan(): void
    {
        $operator = User::create([
            'name' => 'Budi_Penyelenggara',
            'email' => 'budi@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);

        $pengajuan = PengajuanLs::create([
            'no_pengajuan' => 'LS-202606-001',
            'tgl_pengajuan' => now(),
            'user_id' => $operator->id,
            'bidang' => 'Penyelenggara',
            'nama_kegiatan' => 'Kegiatan Test',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 1000000,
            'nilai_neto' => 900000,
            'link_google_drive' => 'https://drive.google.com/test',
            'status' => 'Menunggu Verifikasi',
        ]);

        $this->actingAs($operator);

        // Try to verify
        $response = $this->post(route('pengajuan.verifikasi', $pengajuan->id), [
            'action' => 'setuju',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test admin can view edit page and update user info.
     */
    public function test_admin_can_edit_and_update_user(): void
    {
        $admin = User::create([
            'name' => 'Siti_Admin',
            'email' => 'admin@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Admin Keuangan',
            'bidang' => 'Keuangan',
        ]);

        $targetUser = User::create([
            'name' => 'Old_Name',
            'email' => 'old@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);

        $this->actingAs($admin);

        // Edit page
        $response = $this->get(route('users.edit', $targetUser->id));
        $response->assertStatus(200);

        // Update action
        $response = $this->put(route('users.update', $targetUser->id), [
            'name' => 'New_Name',
            'email' => 'new@bpvp.go.id',
            'role' => 'Verifikator Keuangan',
            'bidang' => 'Keuangan',
            'no_wa' => '628123456789',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'New_Name',
            'email' => 'new@bpvp.go.id',
            'role' => 'Verifikator Keuangan',
            'bidang' => 'Keuangan',
        ]);
    }

    /**
     * Test admin can delete other user but cannot delete self.
     */
    public function test_admin_can_delete_user_but_not_self(): void
    {
        $admin = User::create([
            'name' => 'Siti_Admin',
            'email' => 'admin@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Admin Keuangan',
            'bidang' => 'Keuangan',
        ]);

        $targetUser = User::create([
            'name' => 'Budi_Penyelenggara',
            'email' => 'budi@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);

        $this->actingAs($admin);

        // Delete other user
        $response = $this->delete(route('users.destroy', $targetUser->id));
        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id,
        ]);

        // Delete self
        $response = $this->delete(route('users.destroy', $admin->id));
        $response->assertStatus(302); // Redirect back with error
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    /**
     * Test non-admin cannot update or delete users.
     */
    public function test_operator_cannot_update_or_delete_users(): void
    {
        $operator = User::create([
            'name' => 'Budi_Penyelenggara',
            'email' => 'budi@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);

        $targetUser = User::create([
            'name' => 'Siti_Admin',
            'email' => 'admin@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Admin Keuangan',
            'bidang' => 'Keuangan',
        ]);

        $this->actingAs($operator);

        // Edit page
        $response = $this->get(route('users.edit', $targetUser->id));
        $response->assertStatus(403);

        // Update action
        $response = $this->put(route('users.update', $targetUser->id), [
            'name' => 'Hack_Name',
            'email' => 'hack@bpvp.go.id',
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);
        $response->assertStatus(403);

        // Delete action
        $response = $this->delete(route('users.destroy', $targetUser->id));
        $response->assertStatus(403);
    }

    /**
     * Test login works with both name and email.
     */
    public function test_user_can_login_with_email_or_username(): void
    {
        $user = User::create([
            'name' => 'Test_User',
            'email' => 'test@bpvp.go.id',
            'password' => bcrypt('password123'),
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);

        // Attempt login via username
        $response = $this->post(route('login'), [
            'name' => 'Test_User',
            'password' => 'password123',
        ]);
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'));

        // Attempt login via email
        $response = $this->post(route('login'), [
            'name' => 'test@bpvp.go.id',
            'password' => 'password123',
        ]);
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test google drive url and notification generation.
     */
    public function test_google_drive_url_and_notifications(): void
    {
        $operator = User::create([
            'name' => 'Budi_Penyelenggara',
            'email' => 'budi@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);

        $verifikator = User::create([
            'name' => 'Rina_Verifikator',
            'email' => 'rina@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Verifikator Keuangan',
            'bidang' => 'Keuangan',
        ]);

        $this->actingAs($operator);

        // Submit form with Google Drive URL
        $response = $this->post(route('pengajuan.store'), [
            'no_pengajuan' => 'KU-26062026-999',
            'nama_kegiatan' => 'Kegiatan Test',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 1000000,
            'nilai_neto' => 900000,
            'uraian_pembayaran' => 'Sewa alat',
            'link_google_drive' => 'https://drive.google.com/file/d/test-file-id/view',
            'action' => 'ajukan',
            'kategori_pengajuan' => 'LS Kontrak',
        ]);

        $response->assertRedirect(route('pengajuan.index'));

        // Check link exists in database
        $pengajuan = PengajuanLs::where('no_pengajuan', 'KU-26062026-999')->first();
        $this->assertNotNull($pengajuan);
        $this->assertEquals('https://drive.google.com/file/d/test-file-id/view', $pengajuan->link_google_drive);

        // Check notification created for verifikator
        $this->assertDatabaseHas('notifications', [
            'user_id' => $verifikator->id,
            'title' => 'Pengajuan Baru Menunggu Verifikasi',
        ]);
    }

    /**
     * Test SPM submission records tgl_spm and Bendahara cashing calculates duration correctly.
     */
    public function test_spm_duration_and_cashing(): void
    {
        $operator = User::create([
            'name' => 'Randi_Sakti',
            'email' => 'randi@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Pembayaran',
            'bidang' => 'Keuangan',
        ]);

        $bendahara = User::create([
            'name' => 'Ibu_Diana_Bendahara',
            'email' => 'diana@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Bendahara',
            'bidang' => 'Keuangan',
        ]);

        $pengajuan = PengajuanLs::create([
            'no_pengajuan' => 'KU-26062026-111',
            'tgl_pengajuan' => now()->subDays(5),
            'user_id' => $operator->id,
            'bidang' => 'Keuangan',
            'nama_kegiatan' => 'Kegiatan Test',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 1000000,
            'nilai_neto' => 900000,
            'link_google_drive' => 'https://drive.google.com/test',
            'status' => 'Diajukan ke SAKTI',
            'kategori_pengajuan' => 'LS Kontrak',
        ]);

        // 1. Log in as Operator Pembayaran and submit SPM
        $this->actingAs($operator);
        $response = $this->post(route('pengajuan.realisasi', $pengajuan->id), [
            'no_spm' => 'SPM-2606',
        ]);
        $response->assertRedirect(route('pengajuan.index'));

        // Verify tgl_spm is stored
        $pengajuan->refresh();
        $this->assertEquals(date('Y-m-d'), $pengajuan->tgl_spm);
        $this->assertEquals('LS Kontrak', $pengajuan->kategori_pengajuan);
        $this->assertEquals('Belum Terbit SP2D', $pengajuan->status);

        // 2. Log in as Bendahara and cash the SPM
        $this->actingAs($bendahara);
        $response = $this->post(route('pengajuan.realisasi', $pengajuan->id), [
            'no_sp2d' => 'SP2D-2606',
            'tgl_cair' => date('Y-m-d'),
            'spj_sp2d_link' => 'https://drive.google.com/file/d/sp2d-test/view',
        ]);
        $response->assertRedirect(route('pengajuan.index'));

        // Verify status is Dicairkan
        $pengajuan->refresh();
        $this->assertEquals('Dicairkan', $pengajuan->status);
        $this->assertEquals('SP2D-2606', $pengajuan->no_sp2d);
        $this->assertEquals(date('Y-m-d'), $pengajuan->tgl_cair);

        // 3. Log in as Bendahara and submit proof of handover
        $response = $this->post(route('pengajuan.realisasi', $pengajuan->id), [
            'bukti_penyerahan' => 'https://drive.google.com/file/d/receipt/view',
        ]);
        $response->assertRedirect(route('pengajuan.index'));

        // Verify status is Selesai and proof is saved
        $pengajuan->refresh();
        $this->assertEquals('Selesai', $pengajuan->status);
        $this->assertEquals('https://drive.google.com/file/d/receipt/view', $pengajuan->bukti_penyerahan);
    }

    /**
     * Test UPTD submission goes directly to Verifikator Keuangan.
     */
    public function test_uptd_submission_workflow_direct_to_verifikator_keuangan(): void
    {
        $operatorUptd = User::create([
            'name' => 'Operator_Cilacap',
            'email' => 'operator.cilacap@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'UPTD Cilacap',
        ]);

        $verifikator = User::create([
            'name' => 'Rina_Verifikator',
            'email' => 'rina@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Verifikator Keuangan',
            'bidang' => 'Keuangan',
        ]);

        // 1. Operator UPTD submits new pengajuan
        $this->actingAs($operatorUptd);
        $response = $this->post(route('pengajuan.store'), [
            'no_pengajuan' => 'KU-UPTD-001',
            'nama_kegiatan' => 'Pelatihan UPTD Cilacap',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 5000000,
            'nilai_neto' => 4500000,
            'uraian_pembayaran' => 'Honor instruktur UPTD',
            'link_google_drive' => 'https://drive.google.com/file/d/uptd-test/view',
            'action' => 'ajukan',
            'kategori_pengajuan' => 'GU/UP/TUP',
        ]);
        $response->assertRedirect(route('pengajuan.index'));

        $pengajuan = PengajuanLs::where('no_pengajuan', 'KU-UPTD-001')->first();
        $this->assertNotNull($pengajuan);
        $this->assertEquals('Menunggu Verifikasi', $pengajuan->status);

        // 2. Verifikator Keuangan approves
        $this->actingAs($verifikator);
        $response = $this->post(route('pengajuan.verifikasi', $pengajuan->id), [
            'action' => 'setuju',
        ]);
        $response->assertRedirect(route('pengajuan.index'));

        $pengajuan->refresh();
        $this->assertEquals('Proses Persetujuan PPK', $pengajuan->status);
    }

    /**
     * Test UPTD operator acting as pemohon can create, store, view, and resubmit pengajuan without 500 error.
     */
    public function test_uptd_pemohon_can_create_and_submit_pengajuan_without_error(): void
    {
        $verifikator = User::create([
            'name' => 'Verifikator_Pusat',
            'email' => 'verifikator.pusat@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Verifikator Keuangan',
            'bidang' => 'Keuangan',
        ]);

        $uptdPemohon = User::create([
            'name' => 'Pemohon_UPTD_Semarang',
            'email' => 'semarang@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'UPTD Semarang',
        ]);

        $this->actingAs($uptdPemohon);

        // 1. Can access create page
        $response = $this->get(route('pengajuan.create'));
        $response->assertStatus(200);

        // 2. Can store new pengajuan (verifies no MassAssignmentException on Notification)
        $response = $this->post(route('pengajuan.store'), [
            'no_pengajuan' => 'KU-UPTD-SMR-001',
            'nama_kegiatan' => 'Kegiatan UPTD Semarang',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 2500000,
            'nilai_neto' => 2250000,
            'uraian_pembayaran' => 'Pembayaran UPTD',
            'link_google_drive' => 'https://drive.google.com/file/d/smr-test/view',
            'action' => 'ajukan',
            'kategori_pengajuan' => 'GU/UP/TUP',
        ]);
        $response->assertRedirect(route('pengajuan.index'));

        $pengajuan = PengajuanLs::where('no_pengajuan', 'KU-UPTD-SMR-001')->first();
        $this->assertNotNull($pengajuan);
        $this->assertEquals('Menunggu Verifikasi', $pengajuan->status);
        $this->assertEquals($uptdPemohon->id, $pengajuan->user_id);

        // 3. Can view detail of own pengajuan
        $response = $this->get(route('pengajuan.show', $pengajuan->id));
        $response->assertStatus(200);

        // 4. Check notification was created successfully for Verifikator Keuangan
        $this->assertDatabaseHas('notifications', [
            'user_id' => $verifikator->id,
            'title' => 'Pengajuan Baru Menunggu Verifikasi',
        ]);
    }

    /**
     * Test Admin Keuangan and Operator Pembayaran can record legacy SPM/SP2D pengajuan lampau.
     */
    public function test_admin_and_operator_pembayaran_can_record_legacy_pengajuan(): void
    {
        $admin = User::create([
            'name' => 'Admin_Super',
            'email' => 'admin.legacy@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Admin Keuangan',
            'bidang' => 'Keuangan',
        ]);

        $this->actingAs($admin);

        // 1. Can access create lampau form
        $response = $this->get(route('pengajuan.createLampau'));
        $response->assertStatus(200);

        // 2. Can submit legacy pengajuan
        $response = $this->post(route('pengajuan.storeLampau'), [
            'no_pengajuan' => 'KU-LAMPAU-001',
            'tgl_pengajuan' => '2026-01-15',
            'bidang' => 'Purworejo',
            'kategori_pengajuan' => 'LS Bendahara',
            'nama_kegiatan' => 'Pencairan SPJ Purworejo Masa Lalu',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 15000000,
            'potongan_pajak' => 750000,
            'nilai_neto' => 14250000,
            'uraian_pembayaran' => 'Pembayaran SPJ Purworejo Januari 2026',
            'no_spm' => 'SPM-2601-PUR',
            'tgl_spm' => '2026-01-20',
            'no_sp2d' => 'SP2D-2601-PUR',
            'tgl_cair' => '2026-01-22',
            'status' => 'Dicairkan',
            'link_google_drive' => 'https://drive.google.com/folderview?id=legacy-purworejo',
            'spj_status' => 'SPJ Lengkap',
        ]);

        $response->assertRedirect(route('pengajuan.index'));

        // 3. Verify recorded data in database
        $pengajuan = PengajuanLs::where('no_pengajuan', 'KU-LAMPAU-001')->first();
        $this->assertNotNull($pengajuan);
        $this->assertEquals('Purworejo', $pengajuan->bidang);
        $this->assertEquals('Dicairkan', $pengajuan->status);
        $this->assertEquals('SPM-2601-PUR', $pengajuan->no_spm);
        $this->assertEquals('2026-01-20', $pengajuan->tgl_spm);
        $this->assertEquals('SP2D-2601-PUR', $pengajuan->no_sp2d);
        $this->assertEquals('2026-01-22', $pengajuan->tgl_cair);
    }

    /**
     * Test Bendahara money handover sets 2-day verifikator deadline and sends notifications to Verifikator Keuangan.
     */
    public function test_verifikator_2_day_deadline_and_notifications(): void
    {
        $bendahara = User::create([
            'name' => 'Bendahara_Test',
            'email' => 'bendahara.test@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Bendahara',
            'bidang' => 'Keuangan',
        ]);

        $verifikator = User::create([
            'name' => 'Verifikator_Test',
            'email' => 'verifikator.test@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Verifikator Keuangan',
            'bidang' => 'Keuangan',
        ]);

        $pengajuan = PengajuanLs::create([
            'no_pengajuan' => 'KU-TEST-2DAY-01',
            'tgl_pengajuan' => now(),
            'user_id' => $bendahara->id,
            'bidang' => 'Keuangan',
            'nama_kegiatan' => 'Kegiatan Handover Test',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 2000000,
            'nilai_neto' => 1800000,
            'link_google_drive' => 'https://drive.google.com/test',
            'status' => 'Dicairkan',
            'kategori_pengajuan' => 'LS Kontrak',
        ]);

        // Bendahara submits proof of handover
        $this->actingAs($bendahara);
        $response = $this->post(route('pengajuan.realisasi', $pengajuan->id), [
            'bukti_penyerahan' => 'https://drive.google.com/file/d/receipt-test/view',
        ]);

        $response->assertRedirect(route('pengajuan.index'));

        // Verify verifikator_spm_deadline is set to 2 days from now
        $pengajuan->refresh();
        $this->assertEquals('Selesai', $pengajuan->status);
        $this->assertNotNull($pengajuan->verifikator_spm_deadline);

        // Verify notification sent to Verifikator Keuangan
        $this->assertDatabaseHas('notifications', [
            'user_id' => $verifikator->id,
            'title' => '⏱️ Tenggat 2 Hari Upload SPM/SP2D/SPP',
        ]);
    }

    /**
     * Test Verifikator SPM upload sets 5-day Pemohon SPJ deadline and sends notification to Pemohon.
     */
    public function test_pemohon_5_day_spj_deadline_and_green_red_indicators(): void
    {
        $pemohon = User::create([
            'name' => 'Pemohon_Test_5D',
            'email' => 'pemohon.5d@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);

        $verifikator = User::create([
            'name' => 'Verifikator_Test_5D',
            'email' => 'verifikator.5d@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Verifikator Keuangan',
            'bidang' => 'Keuangan',
        ]);

        $pengajuan = PengajuanLs::create([
            'no_pengajuan' => 'KU-TEST-5DAY-01',
            'tgl_pengajuan' => now(),
            'user_id' => $pemohon->id,
            'bidang' => 'Penyelenggara',
            'nama_kegiatan' => 'Kegiatan SPJ 5D Test',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 3000000,
            'nilai_neto' => 2700000,
            'link_google_drive' => 'https://drive.google.com/test',
            'status' => 'Selesai',
            'spj_status' => 'Belum Upload',
            'kategori_pengajuan' => 'LS Kontrak',
        ]);

        // Verifikator Keuangan uploads SPM/SP2D/SPP
        $this->actingAs($verifikator);
        $response = $this->post(route('pengajuan.uploadSpjVerifikator', $pengajuan->id), [
            'spj_sp2d_link' => 'https://drive.google.com/file/d/sp2d/view',
            'spj_spm_link' => 'https://drive.google.com/file/d/spm/view',
            'spj_spp_link' => 'https://drive.google.com/file/d/spp/view',
        ]);

        $response->assertRedirect(route('pengajuan.show', $pengajuan->id));

        // Verify spj_deadline is set to 5 days from now
        $pengajuan->refresh();
        $this->assertEquals('Menunggu Upload Pemohon', $pengajuan->spj_status);
        $this->assertNotNull($pengajuan->spj_deadline);
        
        $diffDays = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($pengajuan->spj_deadline));
        $this->assertTrue($diffDays >= 4 && $diffDays <= 5);

        // Verify notification sent to Pemohon
        $this->assertDatabaseHas('notifications', [
            'user_id' => $pemohon->id,
            'title' => '⏱️ Tenggat 5 Hari Upload SPJ Lengkap',
        ]);
    }

    /**
     * Test Pemohon SPJ upload sets 2-day verifikator SPJ verification deadline and sends notification to Verifikator Keuangan.
     */
    public function test_verifikator_2_day_spj_verification_deadline_and_indicators(): void
    {
        $pemohon = User::create([
            'name' => 'Pemohon_Verif_Test',
            'email' => 'pemohon.verif@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);

        $verifikator = User::create([
            'name' => 'Verifikator_Verif_Test',
            'email' => 'verifikator.verif@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Verifikator Keuangan',
            'bidang' => 'Keuangan',
        ]);

        $pengajuan = PengajuanLs::create([
            'no_pengajuan' => 'KU-TEST-VERIF-01',
            'tgl_pengajuan' => now(),
            'user_id' => $pemohon->id,
            'bidang' => 'Penyelenggara',
            'nama_kegiatan' => 'Kegiatan Verif SPJ 2D Test',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 4000000,
            'nilai_neto' => 3600000,
            'link_google_drive' => 'https://drive.google.com/test',
            'status' => 'Selesai',
            'spj_status' => 'Menunggu Upload Pemohon',
            'kategori_pengajuan' => 'LS Kontrak',
        ]);

        // Pemohon uploads SPJ Lengkap
        $this->actingAs($pemohon);
        $response = $this->post(route('pengajuan.uploadSpjPemohon', $pengajuan->id), [
            'spj_lengkap_link' => 'https://drive.google.com/file/d/spj-lengkap-test/view',
        ]);

        $response->assertRedirect(route('pengajuan.show', $pengajuan->id));

        // Verify spj_verifikator_deadline is set to 2 days from now
        $pengajuan->refresh();
        $this->assertEquals('Menunggu Verifikasi SPJ', $pengajuan->spj_status);
        $this->assertNotNull($pengajuan->spj_verifikator_deadline);

        // Verify notification sent to Verifikator Keuangan
        $this->assertDatabaseHas('notifications', [
            'user_id' => $verifikator->id,
            'title' => '⏱️ Tenggat 2 Hari Verifikasi SPJ Lengkap',
        ]);
    }

    /**
     * Test Kepala Balai role creation, login, executive dashboard access, and read-only pengajuan view.
     */
    public function test_kepala_balai_role_and_executive_dashboard(): void
    {
        $kepalaBalai = User::create([
            'name' => 'Bapak_Kepala_Balai',
            'email' => 'kepala.balai@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Kepala Balai',
            'bidang' => 'None',
        ]);

        $pemohon = User::create([
            'name' => 'Operator_Penyelenggara',
            'email' => 'operator.penyelenggara@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);

        $pengajuan = PengajuanLs::create([
            'no_pengajuan' => 'KU-EXEC-TEST-001',
            'tgl_pengajuan' => now(),
            'user_id' => $pemohon->id,
            'bidang' => 'Penyelenggara',
            'nama_kegiatan' => 'Pelatihan Executive Monitoring Test',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 10000000,
            'nilai_neto' => 9000000,
            'link_google_drive' => 'https://drive.google.com/exec-test',
            'status' => 'Menunggu Verifikasi',
            'kategori_pengajuan' => 'GU/UP/TUP',
        ]);

        $this->actingAs($kepalaBalai);

        // 1. Can access Executive Dashboard
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard Kepala Balai');
        $response->assertSee('Skor Kepatuhan SLA');

        // 2. Can view all pengajuan in index
        $response = $this->get(route('pengajuan.index'));
        $response->assertStatus(200);
        $response->assertSee('KU-EXEC-TEST-001');

        // 3. Can view detail of any pengajuan (Executive Oversight)
        $response = $this->get(route('pengajuan.show', $pengajuan->id));
        $response->assertStatus(200);
        $response->assertSee('Pelatihan Executive Monitoring Test');
    }

    /**
     * Test UPTD SPP multi-step flow: PPK approval -> LINA issues SPP link -> UPTD uploads signed SPP -> LINA validates -> SAKTI.
     */
    public function test_uptd_spp_multistep_flow(): void
    {
        $ppk = User::create([
            'name' => 'PPK_User',
            'email' => 'ppk@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'PPK',
            'bidang' => 'Keuangan',
        ]);

        $lina = User::create([
            'name' => 'Lina_Operator_Pembayaran',
            'email' => 'lina@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Pembayaran',
            'bidang' => 'Keuangan',
        ]);

        $uptdUser = User::create([
            'name' => 'Operator_UPTD_Kendal',
            'email' => 'uptd.kendal@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'UPTD Kendal',
        ]);

        $pengajuan = PengajuanLs::create([
            'no_pengajuan' => 'KU-UPTD-SPP-001',
            'tgl_pengajuan' => now(),
            'user_id' => $uptdUser->id,
            'bidang' => 'UPTD Kendal',
            'nama_kegiatan' => 'Kegiatan SPP UPTD Kendal',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 5000000,
            'nilai_neto' => 4500000,
            'link_google_drive' => 'https://drive.google.com/test',
            'status' => 'Proses Persetujuan PPK',
            'kategori_pengajuan' => 'GU/UP/TUP',
        ]);

        // Step 1: PPK Approves UPTD Pengajuan -> Status becomes 'Penerbitan SPP'
        $this->actingAs($ppk);
        $response = $this->post(route('pengajuan.ppkApproval', $pengajuan->id), [
            'action' => 'setuju',
        ]);
        $response->assertRedirect(route('pengajuan.index'));
        $pengajuan->refresh();
        $this->assertEquals('Penerbitan SPP', $pengajuan->status);

        // Step 2: Lina (Operator Pembayaran) issues SPP link -> Status becomes 'SPP Menunggu TTD UPTD'
        $this->actingAs($lina);
        $response = $this->post(route('pengajuan.penerbitanSpp', $pengajuan->id), [
            'no_spp' => 'SPP-001/2026',
            'tgl_spp' => date('Y-m-d'),
            'spp_link' => 'https://drive.google.com/spp-original-link',
        ]);
        $response->assertRedirect(route('pengajuan.show', $pengajuan->id));
        $pengajuan->refresh();
        $this->assertEquals('SPP Menunggu TTD UPTD', $pengajuan->status);
        $this->assertEquals('https://drive.google.com/spp-original-link', $pengajuan->spp_link);

        // Step 3: UPTD User uploads signed SPP link -> Status remains 'SPP Menunggu TTD UPTD' but spp_signed_link is populated
        $this->actingAs($uptdUser);
        $response = $this->post(route('pengajuan.uploadSppUptd', $pengajuan->id), [
            'spp_signed_link' => 'https://drive.google.com/spp-signed-by-uptd',
        ]);
        $response->assertRedirect(route('pengajuan.show', $pengajuan->id));
        $pengajuan->refresh();
        $this->assertEquals('SPP Menunggu TTD UPTD', $pengajuan->status);
        $this->assertEquals('https://drive.google.com/spp-signed-by-uptd', $pengajuan->spp_signed_link);

        // Step 4: Lina validates signed SPP -> Status becomes 'Diajukan ke SAKTI'
        $this->actingAs($lina);
        $response = $this->post(route('pengajuan.validasiSppUptd', $pengajuan->id));
        $response->assertRedirect(route('pengajuan.show', $pengajuan->id));
        $pengajuan->refresh();
        $this->assertEquals('Diajukan ke SAKTI', $pengajuan->status);
    }

    /**
     * Test Satpel (Pusat) goes directly to SAKTI upon PPK approval (skips multi-step SPP).
     */
    public function test_satpel_goes_directly_to_sakti_on_ppk_approval(): void
    {
        $ppk = User::create([
            'name' => 'PPK_User_2',
            'email' => 'ppk2@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'PPK',
            'bidang' => 'Keuangan',
        ]);

        $satpelUser = User::create([
            'name' => 'Operator_SATPEL_Batam',
            'email' => 'satpel.batam@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'SATPEL Batam',
        ]);

        $pengajuan = PengajuanLs::create([
            'no_pengajuan' => 'KU-SATPEL-001',
            'tgl_pengajuan' => now(),
            'user_id' => $satpelUser->id,
            'bidang' => 'SATPEL Batam',
            'nama_kegiatan' => 'Kegiatan SATPEL Batam',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 5000000,
            'nilai_neto' => 4500000,
            'link_google_drive' => 'https://drive.google.com/test',
            'status' => 'Proses Persetujuan PPK',
            'kategori_pengajuan' => 'GU/UP/TUP',
        ]);

        // PPK Approves Satpel (Pusat) Pengajuan -> Status becomes 'Diajukan ke SAKTI' directly (skips SPP multi-tahap)
        $this->actingAs($ppk);
        $response = $this->post(route('pengajuan.ppkApproval', $pengajuan->id), [
            'action' => 'setuju',
        ]);
        $response->assertRedirect(route('pengajuan.index'));
        $pengajuan->refresh();
        $this->assertEquals('Diajukan ke SAKTI', $pengajuan->status);
    }

    /**
     * Test comments and rejection validation across all stages + histori_catatan_json audit trail.
     */
    public function test_stage_comments_and_rejection_validation(): void
    {
        $verifikator = User::create([
            'name' => 'Verifikator_Catatan_Test',
            'email' => 'verif.catatan@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Verifikator Keuangan',
            'bidang' => 'Keuangan',
        ]);

        $pemohon = User::create([
            'name' => 'Pemohon_Catatan_Test',
            'email' => 'pemohon.catatan@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'Penyelenggara',
        ]);

        $pengajuan = PengajuanLs::create([
            'no_pengajuan' => 'KU-CATATAN-001',
            'tgl_pengajuan' => now(),
            'user_id' => $pemohon->id,
            'bidang' => 'Penyelenggara',
            'nama_kegiatan' => 'Kegiatan Catatan Test',
            'no_akun' => '521211',
            'jenis_belanja' => 'Honorarium',
            'nilai_bruto' => 1000000,
            'nilai_neto' => 900000,
            'link_google_drive' => 'https://drive.google.com/test',
            'status' => 'Menunggu Verifikasi',
            'kategori_pengajuan' => 'GU/UP/TUP',
        ]);

        // 1. Verifikasi Keuangan without required comment on rejection should fail
        $this->actingAs($verifikator);
        $response = $this->post(route('pengajuan.verifikasi', $pengajuan->id), [
            'action' => 'perbaiki',
            'catatan_koreksi' => '',
        ]);
        $response->assertSessionHas('error');

        // 2. Verifikasi Keuangan with comment on approval records history
        $response = $this->post(route('pengajuan.verifikasi', $pengajuan->id), [
            'action' => 'setuju',
            'catatan' => 'Berkas lengkap dan verified.',
        ]);
        $response->assertRedirect(route('pengajuan.index'));
        $pengajuan->refresh();
        $this->assertEquals('Proses Persetujuan PPK', $pengajuan->status);
        $this->assertNotEmpty($pengajuan->histori_catatan_json);
        $this->assertEquals('Verifikasi Keuangan', $pengajuan->histori_catatan_json[0]['tahap']);
        $this->assertEquals('Disetujui', $pengajuan->histori_catatan_json[0]['action']);
        $this->assertEquals('Berkas lengkap dan verified.', $pengajuan->histori_catatan_json[0]['catatan']);

        // 3. Test SPJ Verification with comment
        $pengajuan->status = 'Selesai';
        $pengajuan->spj_status = 'Menunggu Verifikasi SPJ';
        $pengajuan->save();

        // Rejection without comment should fail
        $response = $this->post(route('pengajuan.verifikasiSpj', $pengajuan->id), [
            'action' => 'perbaiki',
            'catatan_spj' => '',
        ]);
        $response->assertSessionHas('error');

        // Approval with comment should succeed & store catatan_spj
        $response = $this->post(route('pengajuan.verifikasiSpj', $pengajuan->id), [
            'action' => 'setuju',
            'catatan_spj' => 'Kuitansi & SPJ Lengkap 100%',
        ]);
        $response->assertRedirect(route('pengajuan.show', $pengajuan->id));
        $pengajuan->refresh();
        $this->assertEquals('SPJ Lengkap', $pengajuan->spj_status);
        $this->assertEquals('Kuitansi & SPJ Lengkap 100%', $pengajuan->catatan_spj);
    }

    /**
     * Test admin can search and filter users with pagination.
     */
    public function test_admin_can_search_and_filter_users_with_pagination(): void
    {
        $admin = User::create([
            'name' => 'Siti_Admin',
            'email' => 'admin@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Admin Keuangan',
            'bidang' => 'Keuangan',
        ]);

        User::create([
            'name' => 'Lina Payment',
            'email' => 'lina@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Pembayaran',
            'bidang' => 'Keuangan',
            'no_wa' => '08123456789',
        ]);

        User::create([
            'name' => 'Doni UPTD Banda Aceh',
            'email' => 'doni@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Pemohon UPTD',
            'bidang' => 'BPVP Banda Aceh',
            'no_wa' => '08987654321',
        ]);

        $this->actingAs($admin);

        // Search test
        $response = $this->get(route('users.index', ['search' => 'Lina']));
        $response->assertStatus(200);
        $response->assertSee('Lina Payment');
        $response->assertDontSee('Doni UPTD Banda Aceh');

        // Role filter test
        $response = $this->get(route('users.index', ['role' => 'Pemohon UPTD']));
        $response->assertStatus(200);
        $response->assertSee('Doni UPTD Banda Aceh');
        $response->assertDontSee('Lina Payment');

        // Bidang filter test
        $response = $this->get(route('users.index', ['bidang' => 'BPVP Banda Aceh']));
        $response->assertStatus(200);
        $response->assertSee('Doni UPTD Banda Aceh');
        $response->assertDontSee('Lina Payment');
    }
}
