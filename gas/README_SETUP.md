# Panduan Setup Lengkap simonKeu di Google Apps Script

Panduan langkah demi langkah untuk menyetel dan mengaktifkan aplikasi **simonKeu (Sistem Monitoring Keuangan)** menggunakan Google Spreadsheet dan Google Apps Script. 100% Gratis dan Bebas Server.

---

## 📊 LANGKAH 1: Membuat Google Spreadsheet Database

1. Buka [Google Sheets](https://sheets.google.com) dan buat **Spreadsheet Baru**.
2. Beri nama file Spreadsheet: `simonKeu_Database`.
3. Buat **3 Tab Sheet** dengan nama persis sebagai berikut:

---

### Tab 1: `DaftarPengguna`
Buat header di Baris 1:
| A | B | C | D | E |
|---|---|---|---|---|
| **email** | **nama** | **role** | **bidang** | **aktif** |

> **Tambahkan Pengguna Pertama (Admin Keuangan):**
> Contoh pengisian pada baris 2:
> - `email`: `emailanda@gmail.com` (Email Google Anda yang akan digunakan untuk menguji)
> - `nama`: `Admin Keuangan Balai`
> - `role`: `Admin Keuangan`
> - `bidang`: `Keuangan`
> - `aktif`: `TRUE`

---

### Tab 2: `Pengajuan`
Buat header di Baris 1 (28 Kolom):
| Kolom | Nama Header |
|---|---|
| A | **id** |
| B | **no_pengajuan** |
| C | **tgl_pengajuan** |
| D | **email_pemohon** |
| E | **bidang** |
| F | **nama_kegiatan** |
| G | **no_akun** |
| H | **jenis_belanja** |
| I | **kategori_pengajuan** |
| J | **nilai_bruto** |
| K | **nilai_neto** |
| L | **uraian_pembayaran** |
| M | **link_google_drive** |
| N | **status** |
| O | **catatan_koreksi** |
| P | **email_verifikator** |
| Q | **email_ppk** |
| R | **no_spm** |
| S | **tgl_spm** |
| T | **email_operator_pembayaran** |
| U | **no_sp2d** |
| V | **tgl_cair** |
| W | **email_bendahara** |
| X | **bukti_penyerahan** |
| Y | **created_at** |
| Z | **updated_at** |
| AA | **potongan_pajak** |
| AB | **data_dukung_json** |

---

### Tab 3: `Notifikasi`
Buat header di Baris 1:
| A | B | C | D | E | F |
|---|---|---|---|---|---|
| **id** | **email_tujuan** | **title** | **message** | **is_read** | **created_at** |

---

### 🔑 Salin SPREADSHEET_ID
Dapatkan ID Spreadsheet dari URL di browser Anda.
Contoh URL: `https://docs.google.com/spreadsheets/d/1aBcDeFgHiJkLmNoPqRsTuVwXyZ12345/edit`
ID Spreadsheet Anda adalah string acak antara `/d/` dan `/edit`: **`1aBcDeFgHiJkLmNoPqRsTuVwXyZ12345`**

---

## ⚙️ LANGKAH 2: Membuat Proyek Google Apps Script

1. Di Google Sheets yang baru Anda buat, klik menu **Extensions** (Ekstensi) ➔ **Apps Script**.
2. Editor Apps Script akan terbuka. Ubah nama proyek di bagian atas dari "Untitled project" menjadi `simonKeu`.

3. **Buat File-file berikut di Apps Script Editor:**
   *(Klik tombol `+` di sebelah kanan Files ➔ Pilih Script untuk `.gs` atau HTML untuk `.html`)*

   - `Code.gs` ➔ Salin isi file `gas/Code.gs`
   - `Auth.gs` ➔ Salin isi file `gas/Auth.gs`
   - `Utils.gs` ➔ Salin isi file `gas/Utils.gs`
   - `NotifikasiService.gs` ➔ Salin isi file `gas/NotifikasiService.gs`
   - `UserService.gs` ➔ Salin isi file `gas/UserService.gs`
   - `AnggaranService.gs` ➔ Salin isi file `gas/AnggaranService.gs`
   - `DashboardService.gs` ➔ Salin isi file `gas/DashboardService.gs`
   - `PengajuanService.gs` ➔ Salin isi file `gas/PengajuanService.gs`
   - `CSS.html` ➔ Salin isi file `gas/CSS.html`
   - `Layout.html` ➔ Salin isi file `gas/Layout.html`
   - `Login.html` ➔ Salin isi file `gas/Login.html`
   - `Dashboard.html` ➔ Salin isi file `gas/Dashboard.html`
   - `PengajuanIndex.html` ➔ Salin isi file `gas/PengajuanIndex.html`
   - `PengajuanCreate.html` ➔ Salin isi file `gas/PengajuanCreate.html`
   - `PengajuanShow.html` ➔ Salin isi file `gas/PengajuanShow.html`
   - `PengajuanCetak.html` ➔ Salin isi file `gas/PengajuanCetak.html`
   - `UserIndex.html` ➔ Salin isi file `gas/UserIndex.html`
   - `AnggaranIndex.html` ➔ Salin isi file `gas/AnggaranIndex.html`

4. **Buka file `Code.gs`**:
   Ganti nilai `var SPREADSHEET_ID = "ISI_DENGAN_ID_GOOGLE_SPREADSHEET_ANDA";` dengan ID Spreadsheet yang Anda dapatkan di Langkah 1.

---

## 🚀 LANGKAH 3: Publish & Deploy sebagai Web App

1. Di pojok kanan atas editor Apps Script, klik tombol biru **Deploy** ➔ **New deployment**.
2. Klik ikon Roda Gigi (Select type) ➔ Pilih **Web app**.
3. Isi konfigurasi sebagai berikut:
   - **Description**: `simonKeu v1.1`
   - **Execute as**: **User accessing the web app** *(Penting: Agar identitas email Google user yang mengakses terbaca otomatis oleh sistem)*
   - **Who has access**: **Anyone with Google Account** (atau *Anyone within your domain* jika menggunakan Google Workspace)
4. Klik **Deploy**.
5. Google akan meminta **Authorize access**. Klik *Continue*, pilih akun Google Anda, klik *Advanced*, lalu klik *Go to simonKeu (unsafe)* ➔ *Allow*.
6. Setelah selesai, Anda akan mendapatkan **Web App URL** (Contoh: `https://script.google.com/macros/s/AKfycbx.../exec`).

---

## 🎉 SELAMAT! Aplikasi Siap Digunakan

- Buka URL Web App di browser.
- Gunakan menu **Kelola User** (sebagai Admin Keuangan) untuk mendaftarkan akun-akun Google pegawai Balai beserta Role masing-masing:
  - **Operator Bidang**: Dapat membuat pengajuan SPJ & melihat data bidangnya sendiri.
  - **Verifikator Keuangan**: Melakukan verifikasi berkas administrasi.
  - **PPK**: Memberikan persetujuan finansial komitmen.
  - **Operator Pembayaran**: Menginput nomor SPM SAKTI.
  - **Bendahara**: Konfirmasi pencairan SP2D KPPN & input bukti serah terima uang.
