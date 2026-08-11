/**
 * DashboardService.gs - Layanan Statistik & Grafik Dashboard simonKeu
 */

var DashboardService = {
  /**
   * Mengambil data statistik & chart untuk Dashboard
   */
  getDashboardStats: function () {
    var user = Auth.getCurrentUserAccess();
    var allRows = Utils.sheetToObjects('Pengajuan');

    // Filter berdasarkan role (Operator Bidang hanya lihat bidangnya)
    var list = allRows;
    if (user.role === 'Operator Bidang') {
      list = allRows.filter(function (r) {
        return r.bidang && r.bidang.toString().trim() === user.bidang.toString().trim();
      });
    }

    var totalPengajuan = list.length;
    var menungguVerifikasi = 0;
    var perluPerbaikan = 0;
    var disetujui = 0;
    var diajukanSakti = 0;
    var menungguSp2d = 0;
    var dicairkan = 0;

    var now = new Date();
    var currentMonth = now.getMonth();
    var currentYear = now.getFullYear();
    var totalNilaiBulanIni = 0;

    // Map hitung pengajuan per bidang untuk grafik
    var bidangCountMap = {};

    for (var i = 0; i < allRows.length; i++) {
      var row = allRows[i];
      var bidangName = row.bidang ? row.bidang.toString().trim() : 'Lainnya';
      if (bidangName && bidangName !== 'None' && bidangName !== 'Keuangan') {
        bidangCountMap[bidangName] = (bidangCountMap[bidangName] || 0) + 1;
      }
    }

    for (var j = 0; j < list.length; j++) {
      var item = list[j];
      var st = item.status ? item.status.toString().trim() : '';

      if (st === 'Menunggu Verifikasi') menungguVerifikasi++;
      else if (st === 'Perlu Perbaikan') perluPerbaikan++;
      else if (st === 'Disetujui PPK') disetujui++;
      else if (st === 'Diajukan ke SAKTI') diajukanSakti++;
      else if (st === 'Belum Terbit SP2D') menungguSp2d++;
      else if (st === 'Dicairkan' || st === 'Selesai') dicairkan++;

      // Hitung total nilai neto bulan ini
      var tglObj = item.tgl_pengajuan ? new Date(item.tgl_pengajuan) : null;
      if (tglObj && !isNaN(tglObj.getTime())) {
        if (tglObj.getMonth() === currentMonth && tglObj.getFullYear() === currentYear) {
          var neto = parseFloat(item.nilai_neto) || 0;
          totalNilaiBulanIni += neto;
        }
      }
    }

    var labelBidang = Object.keys(bidangCountMap);
    var angkaBidang = labelBidang.map(function (k) { return bidangCountMap[k]; });

    return {
      userRole: user.role,
      userBidang: user.bidang,
      totalPengajuan: totalPengajuan,
      menungguVerifikasi: menungguVerifikasi,
      perluPerbaikan: perluPerbaikan,
      disetujui: disetujui,
      diajukanSakti: diajukanSakti,
      menungguSp2d: menungguSp2d,
      dicairkan: dicairkan,
      totalNilaiBulanIniFormatted: Utils.formatRupiah(totalNilaiBulanIni),
      totalNilaiBulanIni: totalNilaiBulanIni,
      labelBidang: labelBidang,
      angkaBidang: angkaBidang
    };
  }
};

/**
 * Global RPC
 */
function apiGetDashboardStats() {
  return DashboardService.getDashboardStats();
}
