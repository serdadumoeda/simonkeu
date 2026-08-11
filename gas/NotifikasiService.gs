/**
 * NotifikasiService.gs - Layanan Notifikasi In-App (Lonceng Notifikasi Navbar)
 */

var NotifikasiService = {
  /**
   * Mengambil notifikasi yang belum dibaca untuk user login
   */
  getUnreadNotifications: function (email) {
    if (!email) {
      var user = Auth.getCurrentUserAccess();
      email = user.email;
    }

    var all = NotifikasiService.getUserNotifications(email);
    return all.filter(function (n) { return !n.is_read; });
  },

  /**
   * Mengambil notifikasi user (paling baru di atas, max limit)
   */
  getUserNotifications: function (email, limit) {
    if (!email) {
      var user = Auth.getCurrentUserAccess();
      email = user.email;
    }
    limit = limit || 5;

    var list = Utils.sheetToObjects('Notifikasi');
    var filtered = list.filter(function (n) {
      return n.email_tujuan && n.email_tujuan.toString().toLowerCase() === email.toLowerCase();
    });

    // Sort descending by created_at / id
    filtered.sort(function (a, b) {
      return new Date(b.created_at || 0) - new Date(a.created_at || 0);
    });

    return filtered.slice(0, limit).map(function (n) {
      return {
        id: n.id,
        title: n.title || n.judul || '',
        message: n.message || n.pesan || '',
        is_read: (n.is_read === true || n.is_read === 'TRUE' || n.is_read === 1 || n.is_read === '1'),
        created_at: n.created_at ? Utils.formatDateId(n.created_at) : 'Baru saja'
      };
    });
  },

  /**
   * Menandai notifikasi sebagai dibaca
   */
  markAsRead: function (id) {
    var user = Auth.getCurrentUserAccess();
    var db = getDb();
    var sheet = db.getSheetByName('Notifikasi');
    if (!sheet) return { success: false, message: 'Sheet Notifikasi tidak ditemukan' };

    var rowIndex = Utils.findRowIndexById('Notifikasi', 0, id); // Index 0: id
    if (rowIndex > -1) {
      // Pastikan notifikasi milik user yang login
      var rowEmail = sheet.getRange(rowIndex, 2).getValue(); // Kolom 2: email_tujuan
      if (rowEmail.toString().toLowerCase() === user.email.toLowerCase() || user.role === 'Admin Keuangan') {
        sheet.getRange(rowIndex, 5).setValue(true); // Kolom 5: is_read
        return { success: true };
      }
    }
    return { success: false, message: 'Notifikasi tidak ditemukan' };
  },

  /**
   * Membuat notifikasi baru di sheet Notifikasi
   */
  createNotification: function (emailTujuan, title, message) {
    try {
      var db = getDb();
      var sheet = db.getSheetByName('Notifikasi');
      if (!sheet) return;

      var newId = Date.now().toString() + Math.floor(Math.random() * 100);
      sheet.appendRow([
        newId,
        emailTujuan,
        title,
        message,
        false,
        new Date()
      ]);
    } catch (e) {
      Logger.log('Error createNotification: ' + e.toString());
    }
  }
};

/**
 * Global function callable from HTML client side via google.script.run
 */
function apiMarkNotificationAsRead(id) {
  return NotifikasiService.markAsRead(id);
}
