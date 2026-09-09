# SmartDesa Warga

PWA layanan warga yang berjalan terpisah dari aplikasi SmartDesa lokal dan API update. Fondasi tampilannya mengikuti AppKit v22 yang digunakan oleh `htdocs/simp`.

PWA ini dipasang pada `https://warga-smartdesa.mediaverse.co.id/`. Backend sinkronisasi dipisahkan pada proyek `htdocs/smartdesa-warga-api` dan domain `https://api-warga-smartdesa.mediaverse.co.id/`.

## Pengembangan lokal

1. Pastikan Apache mengarah ke `/Users/onhacker/htdocs`.
2. Buat database `smartdesa_warga` bila ingin menguji mode database.
3. Impor `database/schema.sql`, lalu `database/seed.sql`.
4. Buka `http://localhost/smartdesa-warga/`.

Mode demo aktif pada `.env`, sehingga halaman dapat diuji tanpa database:

```text
Akun: warga
Kata sandi: demo12345
```

Untuk membuat ulang konfigurasi demo, salin `.env.local.example` menjadi `.env`.

## Produksi

- Salin `.env.example` menjadi `.env` di luar repository.
- Jalankan `composer install --no-dev --prefer-dist --optimize-autoloader` setelah clone/pull.
- Matikan `WARGA_DEMO_MODE`.
- Isi `APP_KEY` acak minimal 32 karakter.
- Isi kredensial database khusus aplikasi warga.
- Isi `PRIVATE_STORAGE_PATH` dengan folder absolut di luar `public_html`.
- Pastikan ekstensi GD PHP memiliki dukungan WebP (`imagewebp`) untuk optimasi foto produk.
- Arahkan `APP_URL` ke `https://warga-smartdesa.mediaverse.co.id/`.
- Arahkan `WARGA_CENTRAL_API_URL` ke `https://api-warga-smartdesa.mediaverse.co.id/v1/`.
- Gunakan document root dan `.env` terpisah untuk domain PWA dan domain API.

PWA menggunakan database pusat untuk halaman warga dan menyimpan berkas pada `PRIVATE_STORAGE_PATH` di luar `public_html`. API menggunakan database yang sama dengan user database yang dibatasi sesuai kebutuhan.
- Jangan memakai database atau API key milik `smartdesa.mediaverse.co.id`.

Foto produk baru otomatis diubah menjadi WebP tanpa metadata EXIF (maksimal 1.600 px),
disertai thumbnail 640 px untuk kartu produk. URL gambar memakai token versi dan gambar publik
dicache oleh service worker; gambar lama dibuatkan thumbnail saat pertama kali dipakai.

Panduan pemasangan dua subdomain tersedia pada [DEPLOY_HOSTINGER.md](DEPLOY_HOSTINGER.md).
Ringkasan temuan, optimasi tiga batch, dan pemeriksaan rilis tersedia pada
[PERFORMANCE_AUDIT.md](PERFORMANCE_AUDIT.md).

## Repository

Source PWA disimpan di `https://github.com/Onhacker/smardesa_warga` pada branch `main`.
File `.env`, session, log, upload warga, private storage, dan folder `vendor` tidak masuk Git.
Jalankan Composer pada server setiap selesai clone atau ketika `composer.lock` berubah.

## Alur awal

Warga mengajukan permohonan, lalu permohonan menunggu verifikasi Sekdes dan persetujuan Kepala Desa. Kolom `sync_messages` disiapkan untuk dikonsumsi oleh SmartDesa lokal saat perangkat desa kembali terhubung.
