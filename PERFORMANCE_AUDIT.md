# Audit performa PWA SmartDesa Warga

Dokumen ini mencatat perubahan performa yang masuk pada rilis 9 September 2026. Pengukuran
ukuran file dilakukan terhadap aset yang tersimpan di repository; angka jaringan aktual tetap
bergantung pada Brotli/gzip dan cache Hostinger.

## Temuan dan perbaikan

### Batch 1 — aset yang dikirim ke browser

- Bootstrap CSS penuh (263.592 byte) diganti subset yang dipakai warga (42.898 byte).
- Bootstrap JavaScript (146.963 byte) tidak lagi dikirim global. Splide dan LazyLoad dipisah
  menjadi `appkit-core.min.js` (36.646 byte), sementara halaman login/register hanya memuat
  `warga.min.js` dan aksi footer.
- Font Tabler 829 KB yang tidak diperlukan di shell warga dihapus dari layout; icon layanan
  memakai Font Awesome yang sudah ada.
- Script notifikasi dipisah dari bundle komunitas: shell non-komunitas cukup memuat
  `notifications.min.js` yang ringan untuk badge/push, sedangkan `community.min.js` tetap
  dipakai untuk beranda, pengumuman, dan pengaduan.
- Ilustrasi beranda memiliki `srcset` WebP (480/768/1200 atau 256/384/600) dan dimensi eksplisit
  untuk mencegah layout shift.
- Semua referensi aset server-side memakai `warga_asset_url()` (`?v=filemtime`). Header
  immutable satu tahun hanya aktif untuk URL berversi.

### Batch 2 — query dan schema

- DDL dan `SHOW INDEX`/`field_exists()` yang sebelumnya berjalan pada request dihapus. Schema
  wajib disiapkan melalui migration sebelum deployment.
- Ringkasan dashboard memakai satu aggregate SQL, bukan memuat seluruh riwayat permohonan.
- Query preview Pasar Digital dibatasi empat produk, melewati `COUNT(*)`, dan memakai cache file
  45 detik yang diinvalidasi ketika toko/produk/ulasan berubah.
- Query produk terkait juga melewati total count. Pemeriksaan kesiapan tabel dimemoisasi selama
  satu request.

### Batch 3 — PWA dan pengalaman jaringan lambat

- Precache service worker dipangkas menjadi shell offline minimum; navigation preload mengurangi
  latensi navigasi ketika worker sudah aktif.
- Gambar produk publik tetap dicache di Cache Storage berdasarkan ID, token versi, dan varian
  thumbnail/full; gambar privat/draft tidak dicache.
- Loader skeleton media menangani gambar cache dan clone slide Splide tanpa menampilkan shimmer
  ulang yang tidak perlu.
- Loader navigasi/form tetap bekerja pada bundle ringan, termasuk halaman autentikasi yang tidak
  memuat runtime besar.

## Verifikasi rilis

1. Jalankan lint PHP untuk file controller/model/view yang berubah dan `node --check` untuk semua
   JavaScript baru/terubah.
2. Uji login warga, sekdes, dan kades pada lebar 360, 390, 768, dan 1440 px; pastikan footer,
   menu, tema, slider, dan tidak ada overflow horizontal.
3. Pastikan migration `016_marketplace.sql` lalu `017_marketplace_reviews.sql` selesai di
   database produksi sebelum membuka Pasar Digital.
4. Setelah rsync, cek `service-worker.js`, `assetlinks.json`, URL penghapusan akun, dan header
   cache aset berversi. Tutup tab PWA sekali agar worker baru mengambil alih.
