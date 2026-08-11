/**
 * Utils.gs - Helper & Utility Functions
 */

var Utils = {
  /**
   * Pemetaan Data Dukung Wajib per Kategori Pengajuan
   */
  DATA_DUKUNG_MAP: {
    'GU/UP/TUP': ['SPTB', 'Rincian POK', 'DRPP'],
    'LS Kontraktual': ['Surat pesanan', 'BA Serah Terima', 'Permintaan Pembayaran', 'BA Pembayaran', 'Kwitansi', 'SPTB'],
    'LS Non Kontraktual': ['Rincian POK', 'Npwp', 'Rekening', 'Surat pesanan', 'BA Serah Terima', 'Permintaan Pembayaran', 'BA Pembayaran', 'Kwitansi', 'SPTB'],
    'LS banyak penerima': ['SPTB', 'SK', 'Rincian POK', 'Pendaftaran suplier', 'Rekap pengajuan'],
    'LS Bendahara': ['SPTB', 'SK/SPT', 'Rincian POK', 'Daftar pembayaran']
  },

  /**
   * Mendapatkan daftar nama dokumen data dukung berdasarkan kategori
   */
  getDataDukungList: function (kategori) {
    if (!kategori) return [];
    var key = kategori.toString().trim();
    return Utils.DATA_DUKUNG_MAP[key] || [];
  },

  /**
   * Format Angka ke Rupiah IDR
   */
  formatRupiah: function (val) {
    var num = parseFloat(val);
    if (isNaN(num)) return 'Rp 0';
    return 'Rp ' + num.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  },

  /**
   * Format Tanggal ke DD Month YYYY (Indonesia)
   */
  formatDateId: function (dateInput) {
    if (!dateInput) return '-';
    var d = new Date(dateInput);
    if (isNaN(d.getTime())) return dateInput.toString();

    var bulanArr = [
      'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return d.getDate() + ' ' + bulanArr[d.getMonth()] + ' ' + d.getFullYear();
  },

  /**
   * Format Tanggal YYYY-MM-DD untuk Input Form
   */
  formatDateInput: function (dateInput) {
    if (!dateInput) return '';
    var d = new Date(dateInput);
    if (isNaN(d.getTime())) return '';
    var month = '' + (d.getMonth() + 1);
    var day = '' + d.getDate();
    var year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('-');
  },

  /**
   * Mengubah Sheet Data menjadi Array of Objects
   */
  sheetToObjects: function (sheetName) {
    var db = getDb();
    var sheet = db.getSheetByName(sheetName);
    if (!sheet) return [];

    var data = sheet.getDataRange().getValues();
    if (data.length < 2) return [];

    var headers = data[0].map(function (h) {
      return h.toString().trim();
    });

    var result = [];
    for (var i = 1; i < data.length; i++) {
      var row = data[i];
      var obj = { _rowIndex: i + 1 }; // Row number di Spreadsheet (1-indexed)
      var hasData = false;

      for (var j = 0; j < headers.length; j++) {
        var key = headers[j];
        var val = row[j];
        obj[key] = val;
        if (val !== '' && val !== null && val !== undefined) {
          hasData = true;
        }
      }
      if (hasData) {
        result.push(obj);
      }
    }
    return result;
  },

  /**
   * Auto-generate Nomor Pengajuan Baru (Contoh: KU-11082026-001)
   */
  generateNoPengajuan: function () {
    var today = new Date();
    var day = ('0' + today.getDate()).slice(-2);
    var month = ('0' + (today.getMonth() + 1)).slice(-2);
    var year = today.getFullYear();
    var tglKode = day + month + year;
    var prefix = 'KU-' + tglKode + '-';

    var db = getDb();
    var sheet = db.getSheetByName('Pengajuan');
    if (!sheet) return prefix + '001';

    var data = sheet.getDataRange().getValues();
    var count = 1;

    for (var i = 1; i < data.length; i++) {
      var noPengajuan = data[i][1] ? data[i][1].toString() : ''; // Kolom 1 index = no_pengajuan
      if (noPengajuan.indexOf(prefix) === 0) {
        var parts = noPengajuan.split('-');
        var lastNum = parseInt(parts[parts.length - 1], 10);
        if (!isNaN(lastNum) && lastNum >= count) {
          count = lastNum + 1;
        }
      }
    }

    var urutanStr = ('00' + count).slice(-3);
    return prefix + urutanStr;
  },

  /**
   * Mencari baris 1-indexed berdasarkan nilai kolom ID
   */
  findRowIndexById: function (sheetName, idColIndex, targetId) {
    var db = getDb();
    var sheet = db.getSheetByName(sheetName);
    if (!sheet) return -1;

    var data = sheet.getDataRange().getValues();
    for (var i = 1; i < data.length; i++) {
      if (data[i][idColIndex] && data[i][idColIndex].toString() === targetId.toString()) {
        return i + 1; // 1-indexed row number
      }
    }
    return -1;
  }
};
