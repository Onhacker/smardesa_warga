# Database SmartDesa Warga

Database ini terpisah dari database SmartDesa lokal dan database API update. PWA warga dan API warga dapat memakai database ini melalui user database terpisah.

Urutan impor pada MariaDB:

```text
database/schema.sql
database/seed.sql
```

Pada Hostinger, pilih database `smartdesa_warga` lalu impor kedua berkas melalui phpMyAdmin. Jangan menjalankan `seed.sql` sebelum `schema.sql` selesai.

Jika database dibuat sebelum API warga dipisahkan, jalankan `migrations/001_sync_auth.sql` sekali. Migrasi menambahkan penyimpanan terenkripsi untuk secret instalasi dan tabel pencegah replay signature. Jika tenant awal masih memakai kode contoh lama, jalankan `migrations/002_set_araboda_official.sql` sekali. Kode resmi Kampung Araboda adalah `95.01.03.2003`.

`seed.sql` hanya berisi peran, jenis layanan, dan tenant awal resmi. Setiap baris aktif pada `village_tenants` mewakili satu kampung/desa yang dapat dipilih warga. Password pengguna tidak disimpan di berkas seed. Buat akun administrator dan warga melalui endpoint administrasi yang akan dibuat pada tahap berikutnya.
