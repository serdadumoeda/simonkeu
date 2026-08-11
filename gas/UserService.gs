/**
 * UserService.gs - Kelola Data Pengguna (Hanya Admin Keuangan)
 */

var UserService = {
  /**
   * Mengambil semua daftar pengguna dari sheet DaftarPengguna
   */
  getAllUsers: function () {
    Auth.checkPermission(['Admin Keuangan']);
    var users = Utils.sheetToObjects('DaftarPengguna');
    return users.map(function (u) {
      return {
        email: u.email,
        nama: u.nama,
        role: u.role,
        bidang: u.bidang,
        aktif: (u.aktif === true || u.aktif === 'TRUE' || u.aktif === 1 || u.aktif === '1' || u.aktif === 'Aktif' || u.aktif === '')
      };
    });
  },

  /**
   * Menambahkan pengguna baru
   */
  addUser: function (data) {
    Auth.checkPermission(['Admin Keuangan']);

    if (!data.email || !data.nama || !data.role) {
      throw new Error('Email, Nama, dan Role wajib diisi.');
    }

    var db = getDb();
    var sheet = db.getSheetByName('DaftarPengguna');
    if (!sheet) throw new Error('Sheet DaftarPengguna tidak ditemukan.');

    // Cek duplikasi email
    var existing = Utils.sheetToObjects('DaftarPengguna');
    for (var i = 0; i < existing.length; i++) {
      if (existing[i].email && existing[i].email.toString().toLowerCase() === data.email.toLowerCase()) {
        throw new Error('Email ' + data.email + ' sudah terdaftar.');
      }
    }

    sheet.appendRow([
      data.email.trim(),
      data.nama.trim(),
      data.role,
      data.bidang || 'None',
      true // Aktif default
    ]);

    return { success: true, message: 'User baru berhasil ditambahkan.' };
  },

  /**
   * Memperbarui data pengguna
   */
  updateUser: function (targetEmail, data) {
    Auth.checkPermission(['Admin Keuangan']);

    var db = getDb();
    var sheet = db.getSheetByName('DaftarPengguna');
    if (!sheet) throw new Error('Sheet DaftarPengguna tidak ditemukan.');

    var rowIndex = Utils.findRowIndexById('DaftarPengguna', 0, targetEmail); // Col 0 = email
    if (rowIndex === -1) {
      throw new Error('Pengguna dengan email ' + targetEmail + ' tidak ditemukan.');
    }

    sheet.getRange(rowIndex, 2).setValue(data.nama);   // Col 2 = nama
    sheet.getRange(rowIndex, 3).setValue(data.role);   // Col 3 = role
    sheet.getRange(rowIndex, 4).setValue(data.bidang); // Col 4 = bidang
    sheet.getRange(rowIndex, 5).setValue(data.aktif !== false); // Col 5 = aktif

    return { success: true, message: 'Data pengguna berhasil diperbarui.' };
  },

  /**
   * Menghapus / Menonaktifkan pengguna
   */
  deleteUser: function (targetEmail) {
    var currentUser = Auth.checkPermission(['Admin Keuangan']);

    if (targetEmail.toLowerCase() === currentUser.email.toLowerCase()) {
      throw new Error('Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan.');
    }

    var db = getDb();
    var sheet = db.getSheetByName('DaftarPengguna');
    if (!sheet) throw new Error('Sheet DaftarPengguna tidak ditemukan.');

    var rowIndex = Utils.findRowIndexById('DaftarPengguna', 0, targetEmail);
    if (rowIndex > -1) {
      sheet.deleteRow(rowIndex);
      return { success: true, message: 'User berhasil dihapus.' };
    }

    throw new Error('User tidak ditemukan.');
  }
};

/**
 * Global functions for client RPC
 */
function apiGetAllUsers() {
  return UserService.getAllUsers();
}
function apiAddUser(data) {
  return UserService.addUser(data);
}
function apiUpdateUser(targetEmail, data) {
  return UserService.updateUser(targetEmail, data);
}
function apiDeleteUser(targetEmail) {
  return UserService.deleteUser(targetEmail);
}
