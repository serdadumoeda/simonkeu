/**
 * simonKeu - Sistem Monitoring Keuangan (Google Apps Script Version)
 * BPVP Surakarta / Balai Ketenagakerjaan
 */

// SPREADSHEET ID: Google Spreadsheet simonKeu_Database
var SPREADSHEET_ID = "1eInFMd-0x4Yl15Z6nCl3iYRaSXUx6lZunRXvw1Q6-UI";

/**
 * Jalankan fungsi ini 1x di Editor Apps Script untuk memberikan Izin Otorisasi Spreadsheet
 */
function testAuth() {
  var db = SpreadsheetApp.openById(SPREADSHEET_ID);
  Logger.log("Berhasil terhubung ke Spreadsheet: " + db.getName());
}

/**
 * Handle HTTP GET request (Web App Root Entry Point)
 */
function doGet(e) {
  try {
    var page = (e && e.parameter && e.parameter.page) ? e.parameter.page : 'dashboard';
    var userAccess = Auth.getCurrentUserAccess(e);

    // Jika user belum terdaftar di sheet DaftarPengguna atau nonaktif
    if (!userAccess.isRegistered || !userAccess.aktif) {
      var template = HtmlService.createTemplateFromFile('Login');
      template.userEmail = userAccess.email;
      template.errorMessage = userAccess.errorMessage || null;
      return template.evaluate()
        .setTitle('Akses Ditolak - simonKeu')
        .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
    }

    // Routing halaman
    var templateName = 'Dashboard';
    var title = 'Dashboard - simonKeu';

    if (page === 'pengajuan') {
      templateName = 'PengajuanIndex';
      title = 'Daftar Pengajuan - simonKeu';
    } else if (page === 'buat-pengajuan') {
      templateName = 'PengajuanCreate';
      title = 'Buat Pengajuan - simonKeu';
    } else if (page === 'detail-pengajuan') {
      templateName = 'PengajuanShow';
      title = 'Detail Pengajuan - simonKeu';
    } else if (page === 'cetak-pengajuan') {
      // Halaman cetak tanpa layout navbar
      var cetakTemplate = HtmlService.createTemplateFromFile('PengajuanCetak');
      cetakTemplate.pengajuanId = e.parameter.id;
      return cetakTemplate.evaluate()
        .setTitle('Cetak Pengajuan - simonKeu')
        .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
    } else if (page === 'users') {
      templateName = 'UserIndex';
      title = 'Kelola User - simonKeu';
    } else if (page === 'anggaran') {
      templateName = 'AnggaranIndex';
      title = 'Informasi Anggaran - simonKeu';
    }

    var layout = HtmlService.createTemplateFromFile('Layout');
    layout.contentPage = templateName;
    layout.pageParam = page;
    layout.pageParams = e ? e.parameter : {};
    layout.user = userAccess;

    return layout.evaluate()
      .setTitle(title)
      .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');

  } catch (err) {
    return HtmlService.createHtmlOutput('<h3>Terjadi kesalahan pada aplikasi: ' + err.toString() + '</h3>');
  }
}

/**
 * Helper function untuk mengevaluasi & memasukkan (include) template HTML/CSS
 */
function include(filename, data) {
  try {
    var template = HtmlService.createTemplateFromFile(filename);
    if (data) {
      for (var key in data) {
        template[key] = data[key];
      }
    }
    return template.evaluate().getContent();
  } catch (e) {
    return HtmlService.createHtmlOutputFromFile(filename).getContent();
  }
}

/**
 * Mendapatkan Spreadsheet Database
 */
function getDb() {
  if (!SPREADSHEET_ID || SPREADSHEET_ID === "ISI_DENGAN_ID_GOOGLE_SPREADSHEET_ANDA") {
    var active = SpreadsheetApp.getActiveSpreadsheet();
    if (active) return active;
    throw new Error("SPREADSHEET_ID belum diisi di Code.gs! Silakan salin ID dari URL Google Sheets Anda.");
  }
  try {
    return SpreadsheetApp.openById(SPREADSHEET_ID.trim());
  } catch (err) {
    throw new Error("Gagal membuka Spreadsheet dengan ID '" + SPREADSHEET_ID + "'. Pastikan ID sudah benar dan izin akses terbuka. Error: " + err.toString());
  }
}
