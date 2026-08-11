/**
 * AnggaranService.gs - Layanan Informasi Anggaran DIPA (PDF Google Drive)
 */

var AnggaranService = {
  /**
   * Mendapatkan Link URL PDF Informasi Anggaran
   */
  getAnggaranUrl: function () {
    var props = PropertiesService.getScriptProperties();
    var url = props.getProperty('ANGGARAN_PDF_URL');
    
    // Default fallback jika belum di-setting
    if (!url) {
      url = "https://drive.google.com/file/d/1samplePdfId/preview";
    }
    return url;
  },

  /**
   * Meng-update Link URL PDF Informasi Anggaran (Hanya Admin Keuangan)
   */
  updateAnggaranUrl: function (newUrl) {
    Auth.checkPermission(['Admin Keuangan']);

    if (!newUrl || newUrl.indexOf('http') !== 0) {
      throw new Error('Link Google Drive PDF tidak valid.');
    }

    // Mengubah link /view menjadi /preview untuk preview iframe yang optimal
    var embedUrl = newUrl.replace(/\/view(\?.*)?$/, '/preview');
    if (embedUrl.indexOf('/preview') === -1 && embedUrl.indexOf('drive.google.com') > -1) {
      embedUrl = embedUrl + '/preview';
    }

    var props = PropertiesService.getScriptProperties();
    props.setProperty('ANGGARAN_PDF_URL', embedUrl);

    return { success: true, message: 'Dokumen PDF Anggaran berhasil diperbarui!' };
  }
};

/**
 * Global RPC functions
 */
function apiGetAnggaranUrl() {
  return AnggaranService.getAnggaranUrl();
}
function apiUpdateAnggaranUrl(url) {
  return AnggaranService.updateAnggaranUrl(url);
}
