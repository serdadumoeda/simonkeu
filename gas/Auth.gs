/**
 * Auth.gs - Layanan Otentikasi & Otorisasi RBAC Berbasis Google Account & Sheet DaftarPengguna
 */

var Auth = {
  /**
   * Mendapatkan data user yang sedang mengakses aplikasi
   */
  getCurrentUserAccess: function (e) {
    var email = Session.getActiveUser().getEmail();

    // Fallback: Jika Google menyembunyikan email aktif konsumen (@gmail.com),
    // periksa apakah email dikirim via parameter ?user_email=...
    if (!email && e && e.parameter && e.parameter.user_email) {
      email = e.parameter.user_email;
    }
    
    // Fallback terakhir: email pemilik script
    if (!email) {
      email = Session.getEffectiveUser().getEmail();
    }

    var result = {
      email: email ? email.trim() : '',
      name: email ? email.split('@')[0] : 'Guest',
      role: 'Guest',
      bidang: 'None',
      isRegistered: false,
      aktif: false,
      errorMessage: null
    };

    if (!email) {
      result.errorMessage = 'Email pengguna tidak terdeteksi dari sesi Google.';
      return result;
    }

    try {
      var db = getDb();
      var sheet = db.getSheetByName('DaftarPengguna');
      if (!sheet) {
        result.errorMessage = "Sheet 'DaftarPengguna' tidak ditemukan dalam Spreadsheet.";
        return result;
      }

      var data = sheet.getDataRange().getValues();
      if (data.length < 2) {
        result.errorMessage = "Tabel 'DaftarPengguna' masih kosong (belum ada data pengguna di baris 2).";
        return result;
      }

      var headers = data[0].map(function (h) { return h.toString().toLowerCase().trim(); });
      var idxEmail = headers.indexOf('email');
      var idxNama = headers.indexOf('nama');
      var idxRole = headers.indexOf('role');
      var idxBidang = headers.indexOf('bidang');
      var idxAktif = headers.indexOf('aktif');

      if (idxEmail === -1) {
        result.errorMessage = "Header kolom 'email' tidak ditemukan pada baris 1 sheet DaftarPengguna.";
        return result;
      }

      var targetEmail = email.trim().toLowerCase();

      for (var i = 1; i < data.length; i++) {
        var rowEmail = data[i][idxEmail] ? data[i][idxEmail].toString().trim().toLowerCase() : '';
        if (rowEmail === targetEmail) {
          result.isRegistered = true;
          result.name = (idxNama > -1 && data[i][idxNama]) ? data[i][idxNama].toString().trim() : email.split('@')[0];
          result.role = (idxRole > -1 && data[i][idxRole]) ? data[i][idxRole].toString().trim() : 'Guest';
          result.bidang = (idxBidang > -1 && data[i][idxBidang]) ? data[i][idxBidang].toString().trim() : 'None';
          
          var valAktif = (idxAktif > -1) ? data[i][idxAktif] : true;
          var valAktifStr = (valAktif !== null && valAktif !== undefined) ? valAktif.toString().trim().toUpperCase() : 'TRUE';
          
          result.aktif = (valAktifStr === 'TRUE' || valAktifStr === '1' || valAktifStr === 'AKTIF' || valAktifStr === '');
          break;
        }
      }
    } catch (err) {
      result.errorMessage = err.message || err.toString();
      Logger.log('Error Auth.getCurrentUserAccess: ' + err.toString());
    }

    return result;
  },

  /**
   * Pengecekan Otorisasi / Permission Check
   */
  checkPermission: function (allowedRoles) {
    var user = Auth.getCurrentUserAccess();
    if (!user.isRegistered || !user.aktif) {
      throw new Error('Akses Ditolak: Anda tidak terdaftar atau akun tidak aktif.');
    }
    if (allowedRoles && allowedRoles.length > 0) {
      if (allowedRoles.indexOf(user.role) === -1 && user.role !== 'Admin Keuangan') {
        throw new Error('Akses Ditolak: Role Anda (' + user.role + ') tidak memiliki hak untuk tindakan ini.');
      }
    }
    return user;
  }
};
