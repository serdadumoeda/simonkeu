/**
 * PengajuanService.gs - Layanan Utama Alur Kerja Dokumen Pengajuan (simonKeu)
 */

var PengajuanService = {

  /**
   * 1. Mengambil daftar pengajuan dengan filter role & pagination
   */
  getPengajuanList: function (filterParams) {
    filterParams = filterParams || {};
    var user = Auth.getCurrentUserAccess();
    var allRows = Utils.sheetToObjects('Pengajuan');

    // Urutkan pengajuan terbaru di atas (created_at / id desc)
    allRows.sort(function (a, b) {
      return (b.id || b._rowIndex) - (a.id || a._rowIndex);
    });

    // Filtering berdasarkan Role User
    var filtered = allRows.filter(function (item) {
      var st = item.status ? item.status.toString().trim() : '';

      if (user.role === 'Operator Bidang') {
        return item.bidang && item.bidang.toString().trim() === user.bidang.toString().trim();
      } else if (user.role === 'PIC UPTD') {
        var bUser = user.bidang ? user.bidang.toString().trim() : '';
        var isOwn = item.email_pemohon && item.email_pemohon.toString().trim().toLowerCase() === user.email.toString().trim().toLowerCase();
        if (bUser !== 'UPTD' && bUser !== 'None' && bUser !== '') {
          return item.bidang && item.bidang.toString().trim() === bUser && (st !== 'Draft' || isOwn);
        }
        return st !== 'Draft' || isOwn;
      } else if (user.role === 'Verifikator Keuangan') {
        return st !== 'Draft' && st !== 'Menunggu Verifikasi UPTD';
      } else if (user.role === 'PPK') {
        return ['Disetujui PPK', 'Diajukan ke SAKTI', 'Belum Terbit SP2D', 'Dicairkan', 'Perlu Perbaikan', 'Selesai'].indexOf(st) > -1;
      } else if (user.role === 'Operator Pembayaran') {
        return ['Diajukan ke SAKTI', 'Belum Terbit SP2D', 'Dicairkan', 'Selesai'].indexOf(st) > -1;
      } else if (user.role === 'Bendahara') {
        return ['Belum Terbit SP2D', 'Dicairkan', 'Selesai'].indexOf(st) > -1;
      }
      // Admin Keuangan: Bisa melihat seluruh data
      return true;
    });

    // Filtering dari form dropdown search
    if (filterParams.bidang) {
      filtered = filtered.filter(function (item) {
        return item.bidang && item.bidang.toString().trim() === filterParams.bidang.toString().trim();
      });
    }
    if (filterParams.status) {
      filtered = filtered.filter(function (item) {
        return item.status && item.status.toString().trim() === filterParams.status.toString().trim();
      });
    }

    // Ambil daftar bidang unik untuk dropdown filter
    var allUsers = Utils.sheetToObjects('DaftarPengguna');
    var bidangSet = {};
    for (var i = 0; i < allUsers.length; i++) {
      if (allUsers[i].role === 'Operator Bidang' && allUsers[i].bidang) {
        var b = allUsers[i].bidang.toString().trim();
        if (b !== 'None' && b !== 'Keuangan') bidangSet[b] = true;
      }
    }
    for (var j = 0; j < allRows.length; j++) {
      if (allRows[j].bidang) {
        var b2 = allRows[j].bidang.toString().trim();
        if (b2 !== 'None' && b2 !== 'Keuangan') bidangSet[b2] = true;
      }
    }
    var daftarBidang = Object.keys(bidangSet).sort();

    // Formatting data untuk tampilan UI
    var formattedItems = filtered.map(function (item) {
      return {
        id: item.id || item._rowIndex,
        no_pengajuan: item.no_pengajuan,
        tgl_pengajuan: Utils.formatDateId(item.tgl_pengajuan),
        email_pemohon: item.email_pemohon,
        bidang: item.bidang,
        nama_kegiatan: item.nama_kegiatan,
        no_akun: item.no_akun,
        jenis_belanja: item.jenis_belanja,
        kategori_pengajuan: item.kategori_pengajuan || '',
        nilai_bruto: Utils.formatRupiah(item.nilai_bruto),
        potongan_pajak: Utils.formatRupiah(item.potongan_pajak || 0),
        nilai_neto: Utils.formatRupiah(item.nilai_neto),
        raw_neto: item.nilai_neto,
        link_google_drive: item.link_google_drive,
        status: item.status,
        no_spm: item.no_spm || '',
        no_sp2d: item.no_sp2d || ''
      };
    });

    // Simple Pagination (10 per page)
    var page = parseInt(filterParams.page || 1, 10);
    var pageSize = 10;
    var totalItems = formattedItems.length;
    var totalPages = Math.ceil(totalItems / pageSize) || 1;
    var startIndex = (page - 1) * pageSize;
    var paginatedItems = formattedItems.slice(startIndex, startIndex + pageSize);

    return {
      items: paginatedItems,
      totalItems: totalItems,
      currentPage: page,
      totalPages: totalPages,
      daftarBidang: daftarBidang
    };
  },

  /**
   * 2. Mengambil Detail Pengajuan Berdasarkan ID
   */
  getPengajuanDetail: function (id) {
    var user = Auth.getCurrentUserAccess();
    var allRows = Utils.sheetToObjects('Pengajuan');
    var item = null;

    for (var i = 0; i < allRows.length; i++) {
      var rowId = allRows[i].id || allRows[i]._rowIndex;
      if (rowId.toString() === id.toString()) {
        item = allRows[i];
        break;
      }
    }

    if (!item) throw new Error('Pengajuan dengan ID ' + id + ' tidak ditemukan.');

    // Cek Hak Akses
    var st = item.status ? item.status.toString().trim() : '';
    var isOwner = item.email_pemohon && item.email_pemohon.toString().trim().toLowerCase() === user.email.toString().trim().toLowerCase();
    if (user.role === 'Operator Bidang' && item.bidang !== user.bidang && !isOwner) {
      throw new Error('Akses Ditolak: Anda tidak berhak melihat pengajuan dari bidang lain.');
    } else if (user.role === 'PIC UPTD') {
      var bUser = user.bidang ? user.bidang.toString().trim() : '';
      if (!isOwner) {
        if (bUser !== 'UPTD' && bUser !== 'None' && bUser !== '' && item.bidang !== bUser) {
          throw new Error('Akses Ditolak: Anda tidak berhak melihat pengajuan dari UPTD lain.');
        }
        if (st === 'Draft') {
          throw new Error('Akses Ditolak: PIC UPTD tidak dapat melihat dokumen berstatus Draft.');
        }
      }
    } else if (user.role === 'Verifikator Keuangan' && (st === 'Draft' || st === 'Menunggu Verifikasi UPTD') && !isOwner) {
      throw new Error('Akses Ditolak: Dokumen belum diverifikasi oleh PIC UPTD.');
    } else if (user.role === 'PPK' && ['Disetujui PPK', 'Diajukan ke SAKTI', 'Belum Terbit SP2D', 'Dicairkan', 'Perlu Perbaikan', 'Selesai'].indexOf(st) === -1 && !isOwner) {
      throw new Error('Akses Ditolak: Dokumen belum diproses oleh Verifikator Keuangan.');
    } else if (user.role === 'Operator Pembayaran' && ['Diajukan ke SAKTI', 'Belum Terbit SP2D', 'Dicairkan', 'Selesai'].indexOf(st) === -1 && !isOwner) {
      throw new Error('Akses Ditolak: Dokumen belum disetujui oleh PPK.');
    } else if (user.role === 'Bendahara' && ['Belum Terbit SP2D', 'Dicairkan', 'Selesai'].indexOf(st) === -1 && !isOwner) {
      throw new Error('Akses Ditolak: Dokumen belum diproses ke tahap pembayaran.');
    }

    // Hitung Durasi Pencairan (SPM -> Cair)
    var selisihHari = null;
    if (item.tgl_spm) {
      var dSpm = new Date(item.tgl_spm);
      var dCair = item.tgl_cair ? new Date(item.tgl_cair) : new Date();
      var diffTime = dCair.getTime() - dSpm.getTime();
      selisihHari = Math.max(0, Math.floor(diffTime / (1000 * 3600 * 24)));
    }

    // Parse Data Dukung JSON
    var dataDukungList = [];
    if (item.data_dukung_json) {
      try {
        dataDukungList = JSON.parse(item.data_dukung_json);
      } catch (e) {
        Logger.log('Error parse data_dukung_json: ' + e.toString());
      }
    }

    // Fallback jika data_dukung_json kosong (misal data lama)
    if (dataDukungList.length === 0 && item.kategori_pengajuan) {
      var reqDocs = Utils.getDataDukungList(item.kategori_pengajuan);
      dataDukungList = reqDocs.map(function (docName) {
        return {
          nama_dokumen: docName,
          link_drive: item.link_google_drive || ''
        };
      });
    }

    // Lookup Nama User Pemohon, Verifikator, PPK, Operator, Bendahara
    var allUsers = Utils.sheetToObjects('DaftarPengguna');
    var findNama = function (email) {
      if (!email) return '';
      for (var u = 0; u < allUsers.length; u++) {
        if (allUsers[u].email && allUsers[u].email.toString().toLowerCase() === email.toString().toLowerCase()) {
          return allUsers[u].nama || email.split('@')[0];
        }
      }
      return email.split('@')[0];
    };

    var brutoVal = parseFloat(item.nilai_bruto) || 0;
    var pajakVal = parseFloat(item.potongan_pajak) || 0;
    var netoVal = parseFloat(item.nilai_neto) || (brutoVal - pajakVal);

    return {
      id: item.id || item._rowIndex,
      _rowIndex: item._rowIndex,
      no_pengajuan: item.no_pengajuan,
      tgl_pengajuan: Utils.formatDateId(item.tgl_pengajuan),
      email_pemohon: item.email_pemohon,
      nama_pemohon: findNama(item.email_pemohon),
      bidang: item.bidang,
      nama_kegiatan: item.nama_kegiatan,
      no_akun: item.no_akun,
      jenis_belanja: item.jenis_belanja,
      kategori_pengajuan: item.kategori_pengajuan || '',
      nilai_bruto: brutoVal,
      potongan_pajak: pajakVal,
      nilai_neto: netoVal,
      nilai_bruto_formatted: Utils.formatRupiah(brutoVal),
      potongan_pajak_formatted: Utils.formatRupiah(pajakVal),
      nilai_neto_formatted: Utils.formatRupiah(netoVal),
      uraian_pembayaran: item.uraian_pembayaran || '',
      link_google_drive: item.link_google_drive,
      data_dukung_list: dataDukungList,
      status: item.status,
      catatan_koreksi: item.catatan_koreksi || '',
      email_verifikator: item.email_verifikator || '',
      nama_verifikator: findNama(item.email_verifikator),
      email_ppk: item.email_ppk || '',
      nama_ppk: findNama(item.email_ppk),
      no_spm: item.no_spm || '',
      tgl_spm: item.tgl_spm ? Utils.formatDateId(item.tgl_spm) : '',
      email_operator_pembayaran: item.email_operator_pembayaran || '',
      nama_operator_pembayaran: findNama(item.email_operator_pembayaran),
      no_sp2d: item.no_sp2d || '',
      tgl_cair: item.tgl_cair ? Utils.formatDateId(item.tgl_cair) : '',
      email_bendahara: item.email_bendahara || '',
      nama_bendahara: findNama(item.email_bendahara),
      bukti_penyerahan: item.bukti_penyerahan || '',
      selisih_hari: selisihHari
    };
  },

  /**
   * 3. Menyimpan Pengajuan Baru (Operator Bidang)
   */
  storePengajuan: function (data) {
    var user = Auth.checkPermission(['Operator Bidang', 'PIC UPTD']);

    if (!data.nama_kegiatan || !data.no_akun || !data.jenis_belanja || !data.nilai_bruto || !data.link_google_drive || !data.kategori_pengajuan) {
      throw new Error('Semua kolom bertanda * wajib diisi.');
    }

    var noPengajuan = data.no_pengajuan || Utils.generateNoPengajuan();
    var userBidang = user.bidang ? user.bidang.toString() : '';
    var isUptd = userBidang.toUpperCase().indexOf('UPTD') > -1 || userBidang.toUpperCase().indexOf('SATPEL') > -1;
    var statusAwal = data.action === 'draft' ? 'Draft' : (isUptd ? 'Menunggu Verifikasi UPTD' : 'Menunggu Verifikasi');

    var db = getDb();
    var sheet = db.getSheetByName('Pengajuan');
    if (!sheet) throw new Error('Sheet Pengajuan tidak ditemukan.');

    var newId = Date.now().toString();
    var bruto = parseFloat(data.nilai_bruto) || 0;
    var pajak = parseFloat(data.potongan_pajak) || 0;
    var neto = parseFloat(data.nilai_neto) || Math.max(0, bruto - pajak);

    sheet.appendRow([
      newId,
      noPengajuan,
      new Date(),
      user.email,
      user.bidang,
      data.nama_kegiatan,
      data.no_akun,
      data.jenis_belanja,
      data.kategori_pengajuan,
      bruto,
      neto,
      data.uraian_pembayaran || '',
      data.link_google_drive,
      statusAwal,
      '', // catatan_koreksi
      '', // email_verifikator
      '', // email_ppk
      '', // no_spm
      '', // tgl_spm
      '', // email_operator_pembayaran
      '', // no_sp2d
      '', // tgl_cair
      '', // email_bendahara
      '', // bukti_penyerahan
      new Date(), // created_at
      new Date(), // updated_at
      pajak, // potongan_pajak (Col 27)
      data.data_dukung_json || '[]' // data_dukung_json (Col 28)
    ]);

    if (statusAwal === 'Menunggu Verifikasi UPTD') {
      var allUsers = Utils.sheetToObjects('DaftarPengguna');
      for (var i = 0; i < allUsers.length; i++) {
        if (allUsers[i].role === 'PIC UPTD') {
          NotifikasiService.createNotification(
            allUsers[i].email,
            'Pengajuan Baru Menunggu Verifikasi UPTD',
            'Berkas ' + noPengajuan + ' (' + data.nama_kegiatan + ') menunggu verifikasi PIC UPTD Anda.'
          );
        }
      }
    } else if (statusAwal === 'Menunggu Verifikasi') {
      var allUsers = Utils.sheetToObjects('DaftarPengguna');
      for (var i = 0; i < allUsers.length; i++) {
        if (allUsers[i].role === 'Verifikator Keuangan') {
          NotifikasiService.createNotification(
            allUsers[i].email,
            'Pengajuan Baru Menunggu Verifikasi',
            'Berkas ' + noPengajuan + ' (' + data.nama_kegiatan + ') menunggu verifikasi Anda.'
          );
        }
      }
    }

    return { success: true, message: 'Pengajuan berhasil diproses.', id: newId };
  },

  /**
   * 4. Proses Verifikasi oleh Verifikator Keuangan
   */
  verifikasi: function (id, action, catatanKoreksi) {
    var user = Auth.checkPermission(['Verifikator Keuangan']);
    var detail = PengajuanService.getPengajuanDetail(id);

    var db = getDb();
    var sheet = db.getSheetByName('Pengajuan');
    var rowIndex = Utils.findRowIndexById('Pengajuan', 0, id);
    if (rowIndex === -1) rowIndex = detail._rowIndex;

    var statusBaru = '';
    if (action === 'setuju') {
      statusBaru = 'Disetujui PPK';
      // Notifikasi ke semua PPK
      var allUsers = Utils.sheetToObjects('DaftarPengguna');
      for (var i = 0; i < allUsers.length; i++) {
        if (allUsers[i].role === 'PPK') {
          NotifikasiService.createNotification(
            allUsers[i].email,
            'Persetujuan Dokumen Baru',
            'Berkas ' + detail.no_pengajuan + ' telah diverifikasi Keuangan dan menunggu persetujuan Anda.'
          );
        }
      }
    } else if (action === 'perbaiki') {
      statusBaru = 'Perlu Perbaikan';
      NotifikasiService.createNotification(
        detail.email_pemohon,
        'Revisi Pengajuan Berkas',
        'Berkas ' + detail.no_pengajuan + ' perlu diperbaiki: ' + (catatanKoreksi || 'Periksa kelengkapan.')
      );
    } else {
      statusBaru = 'Draft';
      NotifikasiService.createNotification(
        detail.email_pemohon,
        'Pengajuan Berkas Ditolak',
        'Berkas ' + detail.no_pengajuan + ' ditolak total dan dikembalikan ke Draft.'
      );
    }

    sheet.getRange(rowIndex, 14).setValue(statusBaru);
    sheet.getRange(rowIndex, 15).setValue(catatanKoreksi || '');
    sheet.getRange(rowIndex, 16).setValue(user.email);
    sheet.getRange(rowIndex, 26).setValue(new Date());

    return { success: true, message: 'Status pengajuan berhasil diperbarui oleh Verifikator.' };
  },

  /**
   * 5. Proses Approval PPK
   */
  ppkApproval: function (id, action, catatanKoreksi) {
    var user = Auth.checkPermission(['PPK']);
    var detail = PengajuanService.getPengajuanDetail(id);

    var db = getDb();
    var sheet = db.getSheetByName('Pengajuan');
    var rowIndex = Utils.findRowIndexById('Pengajuan', 0, id);
    if (rowIndex === -1) rowIndex = detail._rowIndex;

    var statusBaru = '';
    if (action === 'setuju') {
      statusBaru = 'Diajukan ke SAKTI';
      // Notifikasi ke Operator Pembayaran
      var allUsers = Utils.sheetToObjects('DaftarPengguna');
      for (var i = 0; i < allUsers.length; i++) {
        if (allUsers[i].role === 'Operator Pembayaran') {
          NotifikasiService.createNotification(
            allUsers[i].email,
            'Pengajuan SPM SAKTI',
            'Berkas ' + detail.no_pengajuan + ' telah disetujui PPK, silakan ajukan SPM di Aplikasi SAKTI.'
          );
        }
      }
    } else {
      statusBaru = 'Perlu Perbaikan';
      NotifikasiService.createNotification(
        detail.email_pemohon,
        'Revisi Berkas oleh PPK',
        'Berkas ' + detail.no_pengajuan + ' perlu diperbaiki berdasarkan keputusan PPK: ' + (catatanKoreksi || '')
      );
    }

    sheet.getRange(rowIndex, 14).setValue(statusBaru);
    sheet.getRange(rowIndex, 15).setValue(catatanKoreksi || '');
    sheet.getRange(rowIndex, 17).setValue(user.email);
    sheet.getRange(rowIndex, 26).setValue(new Date());

    return { success: true, message: 'Keputusan PPK berhasil disimpan.' };
  },

  /**
   * 6. Realisasi (Operator Pembayaran & Bendahara)
   */
  realisasi: function (id, data) {
    var user = Auth.getCurrentUserAccess();
    var detail = PengajuanService.getPengajuanDetail(id);

    var db = getDb();
    var sheet = db.getSheetByName('Pengajuan');
    var rowIndex = Utils.findRowIndexById('Pengajuan', 0, id);
    if (rowIndex === -1) rowIndex = detail._rowIndex;

    if (user.role === 'Operator Pembayaran') {
      if (!data.no_spm) throw new Error('Nomor SPM SAKTI wajib diisi.');

      sheet.getRange(rowIndex, 14).setValue('Belum Terbit SP2D');
      sheet.getRange(rowIndex, 18).setValue(data.no_spm);
      sheet.getRange(rowIndex, 19).setValue(new Date());
      sheet.getRange(rowIndex, 20).setValue(user.email);
      sheet.getRange(rowIndex, 26).setValue(new Date());

      // Notifikasi ke Bendahara
      var allUsers = Utils.sheetToObjects('DaftarPengguna');
      for (var i = 0; i < allUsers.length; i++) {
        if (allUsers[i].role === 'Bendahara') {
          NotifikasiService.createNotification(
            allUsers[i].email,
            'Pencairan SP2D Baru',
            'Nomor SPM untuk ' + detail.no_pengajuan + ' telah terbit, mohon konfirmasi pencairan jika SP2D terbit.'
          );
        }
      }

    } else if (user.role === 'Bendahara') {
      if (data.bukti_penyerahan) {
        // Sub-proses: Input Bukti Penyerahan Uang
        sheet.getRange(rowIndex, 14).setValue('Selesai');
        sheet.getRange(rowIndex, 24).setValue(data.bukti_penyerahan);
        sheet.getRange(rowIndex, 26).setValue(new Date());

        NotifikasiService.createNotification(
          detail.email_pemohon,
          'Uang Diserahkan & Proses Selesai',
          'Bendahara telah menyerahkan uang untuk pengajuan ' + detail.no_pengajuan + '. Silakan periksa bukti penyerahan Google Drive.'
        );

      } else {
        // Sub-proses: Input SP2D & Tgl Cair
        if (!data.no_sp2d || !data.tgl_cair) throw new Error('Nomor SP2D dan Tanggal Cair wajib diisi.');

        sheet.getRange(rowIndex, 14).setValue('Dicairkan');
        sheet.getRange(rowIndex, 21).setValue(data.no_sp2d);
        sheet.getRange(rowIndex, 22).setValue(new Date(data.tgl_cair));
        sheet.getRange(rowIndex, 23).setValue(user.email);
        sheet.getRange(rowIndex, 26).setValue(new Date());

        NotifikasiService.createNotification(
          detail.email_pemohon,
          'Dana Berhasil Cair',
          'Selamat! Dana pengajuan berkas ' + detail.no_pengajuan + ' telah dicairkan oleh Bendahara. Menunggu proses penyerahan uang.'
        );
      }
    } else {
      throw new Error('Akses Ditolak: Anda tidak memiliki hak akses untuk memproses realisasi.');
    }

    return { success: true, message: 'Data realisasi berhasil diperbarui.' };
  },

  verifikasiPicUptd: function (id, action, catatanKoreksi) {
    var user = Auth.checkPermission(['PIC UPTD']);
    var detail = PengajuanService.getPengajuanDetail(id);

    var db = getDb();
    var sheet = db.getSheetByName('Pengajuan');
    var rowIndex = Utils.findRowIndexById('Pengajuan', 0, id);
    if (rowIndex === -1) rowIndex = detail._rowIndex;

    var statusBaru = '';
    if (action === 'setuju') {
      statusBaru = 'Menunggu Verifikasi';
      var allUsers = Utils.sheetToObjects('DaftarPengguna');
      for (var i = 0; i < allUsers.length; i++) {
        if (allUsers[i].role === 'Verifikator Keuangan') {
          NotifikasiService.createNotification(
            allUsers[i].email,
            'Pengajuan Lolos Verifikasi UPTD',
            'Berkas ' + detail.no_pengajuan + ' telah diverifikasi oleh PIC UPTD dan menunggu verifikasi Keuangan Pusat.'
          );
        }
      }
    } else if (action === 'perbaiki') {
      statusBaru = 'Perlu Perbaikan';
      NotifikasiService.createNotification(
        detail.email_pemohon,
        'Revisi Berkas oleh PIC UPTD',
        'Berkas ' + detail.no_pengajuan + ' perlu diperbaiki: ' + (catatanKoreksi || 'Periksa kelengkapan berkas UPTD.')
      );
    } else {
      statusBaru = 'Draft';
      NotifikasiService.createNotification(
        detail.email_pemohon,
        'Pengajuan Berkas Ditolak PIC UPTD',
        'Berkas ' + detail.no_pengajuan + ' ditolak total oleh PIC UPTD dan dikembalikan ke Draft.'
      );
    }

    sheet.getRange(rowIndex, 14).setValue(statusBaru);
    sheet.getRange(rowIndex, 15).setValue(catatanKoreksi || '');
    sheet.getRange(rowIndex, 26).setValue(new Date());

    return { success: true, message: 'Status pengajuan berhasil diperbarui oleh PIC UPTD.' };
  }
};

/**
 * Global RPC Client Functions
 */
function apiGetPengajuanList(filterParams) {
  return PengajuanService.getPengajuanList(filterParams);
}
function apiGetPengajuanDetail(id) {
  return PengajuanService.getPengajuanDetail(id);
}
function apiStorePengajuan(formData) {
  return PengajuanService.storePengajuan(formData);
}
function apiVerifikasiPicUptd(id, action, catatanKoreksi) {
  return PengajuanService.verifikasiPicUptd(id, action, catatanKoreksi);
}
function apiVerifikasi(id, action, catatanKoreksi) {
  return PengajuanService.verifikasi(id, action, catatanKoreksi);
}
function apiPpkApproval(id, action, catatanKoreksi) {
  return PengajuanService.ppkApproval(id, action, catatanKoreksi);
}
function apiRealisasi(id, formData) {
  return PengajuanService.realisasi(id, formData);
}
function apiGenerateNoPengajuan() {
  return Utils.generateNoPengajuan();
}
