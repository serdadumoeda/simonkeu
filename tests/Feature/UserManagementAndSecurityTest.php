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
     * Test UPTD submission requires PIC UPTD verification before forwarding to Keuangan.
     */
    public function test_pic_uptd_verification_workflow(): void
    {
        $operatorUptd = User::create([
            'name' => 'Operator_Cilacap',
            'email' => 'operator.cilacap@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'Operator Bidang',
            'bidang' => 'UPTD Cilacap',
        ]);

        $picUptd = User::create([
            'name' => 'PIC_Cilacap',
            'email' => 'pic.cilacap@bpvp.go.id',
            'password' => bcrypt('password'),
            'role' => 'PIC UPTD',
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
        $this->assertEquals('Menunggu Verifikasi UPTD', $pengajuan->status);

        // 2. PIC UPTD approves the submission
        $this->actingAs($picUptd);
        $response = $this->post(route('pengajuan.verifikasiPicUptd', $pengajuan->id), [
            'action' => 'setuju',
        ]);
        $response->assertRedirect(route('pengajuan.index'));

        $pengajuan->refresh();
        $this->assertEquals('Menunggu Verifikasi', $pengajuan->status);
        $this->assertEquals($picUptd->id, $pengajuan->pic_uptd_id);

        // 3. Verifikator Keuangan approves
        $this->actingAs($verifikator);
        $response = $this->post(route('pengajuan.verifikasi', $pengajuan->id), [
            'action' => 'setuju',
        ]);
        $response->assertRedirect(route('pengajuan.index'));

        $pengajuan->refresh();
        $this->assertEquals('Disetujui PPK', $pengajuan->status);
    }
}
