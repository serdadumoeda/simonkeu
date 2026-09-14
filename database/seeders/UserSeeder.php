<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin Keuangan (Bisa kelola master data & user)
        User::create(['name' => 'Siti_Admin', 'email' => 'admin@bpvp.go.id', 'password' => Hash::make('12345'), 'role' => 'Admin Keuangan', 'bidang' => 'Keuangan', 'no_wa' => '6281200000001']);

        // 2. Operator Bidang (Yang membuat pengajuan)
        User::create(['name' => 'Budi_Penyelenggara', 'email' => 'budi@bpvp.go.id', 'password' => Hash::make('12345'), 'role' => 'Operator Bidang', 'bidang' => 'Penyelenggara', 'no_wa' => '6281234567890']);
        User::create(['name' => 'Ani_Pemberdayaan', 'email' => 'ani@bpvp.go.id', 'password' => Hash::make('12345'), 'role' => 'Operator Bidang', 'bidang' => 'Pemberdayaan', 'no_wa' => '6281398765432']);
        User::create(['name' => 'Joko_Umum', 'email' => 'joko@bpvp.go.id', 'password' => Hash::make('12345'), 'role' => 'Operator Bidang', 'bidang' => 'Umum', 'no_wa' => '6285712345678']);

        // 3. Verifikator Keuangan (Yang mengecek nota/SPJ)
        User::create(['name' => 'Rina_Verifikator', 'email' => 'rina@bpvp.go.id', 'password' => Hash::make('12345'), 'role' => 'Verifikator Keuangan', 'bidang' => 'Keuangan', 'no_wa' => '6282111223344']);

        // 4. PPK (Pejabat Pembuat Komitmen - Penyetuju akhir internal)
        User::create(['name' => 'Bapak_Agus_PPK', 'email' => 'agus.ppk@bpvp.go.id', 'password' => Hash::make('12345'), 'role' => 'PPK', 'bidang' => 'None', 'no_wa' => '6281255667788']);

        // 5. Operator Pembayaran (Yang input ke Aplikasi SAKTI Kemenkeu)
        User::create(['name' => 'Randi_Sakti', 'email' => 'randi@bpvp.go.id', 'password' => Hash::make('12345'), 'role' => 'Operator Pembayaran', 'bidang' => 'Keuangan', 'no_wa' => '6281344556677']);

        // 6. Bendahara (Yang mencairkan dan input SP2D)
        User::create(['name' => 'Ibu_Diana_Bendahara', 'email' => 'diana@bpvp.go.id', 'password' => Hash::make('12345'), 'role' => 'Bendahara', 'bidang' => 'Keuangan', 'no_wa' => '6281988776655']);
    }
}