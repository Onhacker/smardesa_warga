# Database SmartDesa Warga

Database ini terpisah dari database SmartDesa lokal dan database API update. PWA warga dan API warga dapat memakai database ini melalui user database terpisah.

Urutan impor pada MariaDB:

```text
database/schema.sql
database/seed.sql
```

Pada Hostinger, pilih database `smartdesa_warga` lalu impor kedua berkas melalui phpMyAdmin. Jangan menjalankan `seed.sql` sebelum `schema.sql` selesai.

Jika database dibuat sebelum API warga dipisahkan, jalankan `migrations/001_sync_auth.sql` sekali. Migrasi menambahkan penyimpanan terenkripsi untuk secret instalasi dan tabel pencegah replay signature. Jika tenant awal masih memakai kode contoh lama, jalankan `migrations/002_set_araboda_official.sql` sekali. Setelah itu jalankan `migrations/003_seed_jayawijaya_villages.sql` untuk memasukkan seluruh 332 kampung/kelurahan pada 40 distrik di Kabupaten Jayawijaya.

Untuk database yang sudah berjalan, jalankan `migrations/006_service_catalog.sql` setelah migrasi sebelumnya. Migrasi ini menambahkan katalog layanan per desa, versi formulir, dan metadata lampiran.

Jalankan `migrations/007_resident_directory.sql` setelahnya untuk mengaktifkan direktori penduduk tersinkron dan verifikasi pendaftaran warga. NIK dan No. KK tidak disimpan mentah di server pusat.

Jika migration `007` sudah pernah dijalankan sebelum pengaman akun unik ditambahkan, jalankan `migrations/008_unique_citizen_source.sql`. Migrasi ini memastikan satu penduduk lokal hanya dapat memiliki satu akun PWA pada kampung/desanya.

Jalankan `migrations/009_official_documents.sql` untuk menambahkan hash dan ukuran PDF resmi yang dikirim kembali oleh SmartDesa lokal.

Jalankan `migrations/010_sync_aggregate_keys.sql` untuk memperpanjang kunci katalog/snapshot pada `sync_messages.aggregate_id` menjadi 120 karakter. Berkas migrasi PWA dan API sama; cukup dijalankan sekali pada database pusat yang dipakai bersama.

Jalankan `migrations/012_citizen_identity_details.sql` untuk menambahkan kolom terenkripsi NIK dan No. KK pada profil warga. Kolom ini hanya dibaca pada halaman akun milik warga yang sedang masuk; direktori penduduk dan riwayat sinkronisasi tetap hanya menyimpan HMAC.

Setelah seluruh migrasi sebelumnya selesai (termasuk `migrations/015_sync_integrity.sql`), jalankan `migrations/016_marketplace.sql` untuk mengaktifkan Pasar Digital: kategori, identitas toko per pengguna, produk, dan metadata gambar privat. Gambar produk disimpan pada `PRIVATE_STORAGE_PATH`, bukan di dalam `public_html`.

`seed.sql` berisi peran, jenis layanan, dan seluruh tenant wilayah Kabupaten Jayawijaya. Setiap baris aktif pada `village_tenants` mewakili satu kampung/kelurahan yang dapat dipilih warga. Password pengguna tidak disimpan di berkas seed. Buat akun administrator dan warga melalui endpoint administrasi yang akan dibuat pada tahap berikutnya.
