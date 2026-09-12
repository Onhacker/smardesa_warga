# Deployment Hostinger

Dokumen ini memasang PWA warga dan API sinkronisasi pada dua subdomain terpisah:

- `warga-smartdesa.mediaverse.co.id` -> `/home/USER/domains/warga-smartdesa.mediaverse.co.id/public_html`
- `api-warga-smartdesa.mediaverse.co.id` -> `/home/USER/domains/api-warga-smartdesa.mediaverse.co.id/public_html`

Ganti `USER` dengan nama akun Hostinger. Link hPanel bukan URL aplikasi; gunakan URL subdomain di atas setelah SSL aktif.

## 1. Deploy source PWA dari Git

Clone repository ke folder repository privat akun Hostinger, lalu pasang dependensi:

```bash
mkdir -p "$HOME/repositories"
cd "$HOME/repositories"
git clone git@github.com:Onhacker/smardesa_warga.git
cd smardesa_warga
composer install --no-dev --prefer-dist --optimize-autoloader
```

Gunakan `GIT_SSH_COMMAND` dengan path kunci tertentu hanya jika file kunci tersebut
memang sudah ada di server. Jika tidak, perintah clone di atas akan memakai kunci SSH
default atau SSH agent yang tersedia.

Salin source ke document root tanpa membawa konfigurasi runtime:

```bash
PWA_ROOT="$HOME/domains/warga-smartdesa.mediaverse.co.id/public_html"
mkdir -p "$PWA_ROOT"
rsync -a --delete \
  --exclude='.git/' \
  --exclude='.env' \
  --exclude='application/cache/*' \
  --exclude='application/logs/*' \
  --exclude='application/sessions/*' \
  --exclude='storage/*' \
  --exclude='uploads/requests/*' \
  ./ "$PWA_ROOT/"
```

Untuk deployment berikutnya, jalankan `git pull --ff-only`, `composer install`, lalu perintah
`rsync` yang sama. File `.env` produksi dibuat langsung pada document root dan tidak pernah
disimpan dalam Git.

Setelah source tersalin, pastikan Digital Asset Links ikut tersedia pada document root PWA:

```bash
ASSETLINKS_ROOT="$HOME/domains/warga-smartdesa.mediaverse.co.id/public_html/.well-known"
mkdir -p "$ASSETLINKS_ROOT"
cp .well-known/assetlinks.json "$ASSETLINKS_ROOT/assetlinks.json"
chmod 644 "$ASSETLINKS_ROOT/assetlinks.json"
```

File tersebut harus dipublikasikan tanpa redirect dan berisi dua relasi serta empat fingerprint
yang disiapkan Google Play. Verifikasi dari komputer Anda:

```bash
curl -i https://warga-smartdesa.mediaverse.co.id/.well-known/assetlinks.json
```

Respons harus `HTTP/2 200`, `Content-Type: application/json`, dan memuat
`delegate_permission/common.get_login_creds`. Jika LiteSpeed masih mengembalikan ETag lama,
purge cache domain atau tunggu cache kedaluwarsa sebelum menguji ulang di Play Console.

Setelah API dan PWA selesai dipasang pertama kali, deployment rutin keduanya dapat dijalankan
dari repository API dengan satu perintah. Skrip tersebut juga membuat backup dan menjalankan
migrasi database yang aman diulang:

```bash
cd "$HOME/repositories/api_warga"
bash scripts/deploy-hostinger.sh
```

## 2. Upload source API

Upload atau clone source `/Users/onhacker/htdocs/smartdesa-warga-api` ke root API. Jangan
menggabungkan PWA dan API dalam satu document root. Repository pada panduan ini hanya berisi
PWA; source API akan memakai repository/deployment terpisah. `.env` dibuat langsung melalui
SSH dan tidak diunggah melalui browser.

Buat penyimpanan berkas di luar web root:

```bash
mkdir -p "$HOME/smartdesa-private/warga"
chmod 750 "$HOME/smartdesa-private" "$HOME/smartdesa-private/warga"
```

## 3. Database pusat

Buat satu database `smartdesa_warga` dan dua user database bila panel mendukungnya. Beri hak minimal yang diperlukan kepada PWA dan API. Impor sekali:

```text
smartdesa-warga/database/schema.sql
smartdesa-warga/database/seed.sql
```

Jika database sudah pernah dibuat, impor berkas pada `database/migrations` sesuai urutan dan
catat migration yang sudah pernah dijalankan. Jangan mengimpor ulang migration lama yang tidak
idempoten. Rangkaian yang relevan saat ini berjalan dari `001_*.sql` sampai `021_*.sql` dan
menambahkan autentikasi sinkron,
seluruh wilayah Jayawijaya, aktivasi otomatis, katalog Master Surat, direktori penduduk,
pengaman satu akun per penduduk, metadata PDF resmi, kunci snapshot sepanjang 120 karakter,
penyimpanan terenkripsi NIK dan No. KK untuk ditampilkan kepada pemilik akun, serta tabel
Pasar Digital untuk toko, produk, kategori, dan gambar privat. Untuk rilis ini, migration
`016_marketplace.sql` wajib dijalankan lebih dahulu, lalu `017_marketplace_reviews.sql` untuk
rating/ulasan. `018_global_nik_uniqueness.sql` dan `019_monitoring_auth.sql` dijalankan sesuai
kebutuhan instalasi setelah migration pendahulunya selesai. `020_announcement_attachments.sql`
wajib dijalankan untuk fitur lampiran pengumuman. `021_marketplace_categories.sql` menambahkan
kategori Pasar Digital terbaru pada instalasi yang sudah menjalankan migration marketplace.

Contoh menjalankan migration Pasar Digital dari root repository (password dimasukkan pada
prompt `mysql`, tidak ditulis di terminal history):

```bash
DB_HOST='localhost'
DB_USER='USER_DATABASE'
DB_NAME='smartdesa_warga'
REPO="$HOME/repositories/smardesa_warga"

cd "$REPO" || exit 1
mysql --default-character-set=utf8mb4 -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" \
  < "$REPO/database/migrations/016_marketplace.sql"
mysql --default-character-set=utf8mb4 -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" \
  < "$REPO/database/migrations/017_marketplace_reviews.sql"

# Lampiran pengumuman (wajib untuk fitur upload dan modal lampiran)
mysql --default-character-set=utf8mb4 -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" \
  < "$REPO/database/migrations/020_announcement_attachments.sql"
mysql --default-character-set=utf8mb4 -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" \
  < "$REPO/database/migrations/021_marketplace_categories.sql"
```

Kode aplikasi tidak lagi menjalankan `CREATE TABLE`, `ALTER TABLE`, atau pemeriksaan metadata
berulang pada setiap request. Karena itu, selesaikan migration sebelum membuka aplikasi untuk
pengguna; bila schema belum lengkap, request terkait akan gagal secara aman dan deployment harus
diperbaiki, bukan mengandalkan DDL runtime.

## 4. Konfigurasi API

Pada root API, salin `.env.example` menjadi `.env`, lalu isi:

```text
APP_ENV=production
APP_URL=https://api-warga-smartdesa.mediaverse.co.id/
APP_KEY=<hasil random_bytes, minimal 32 karakter>
API_DEMO_MODE=0
WARGA_ALLOWED_ORIGIN=https://warga-smartdesa.mediaverse.co.id
DB_HOST=<host database dari Hostinger>
DB_USER=<user API>
DB_PASS=<password API>
DB_NAME=smartdesa_warga
```

API membutuhkan `PRIVATE_STORAGE_PATH` yang sama-sama dapat dibaca oleh PHP API:

```text
PRIVATE_STORAGE_PATH=/home/USER/smartdesa-private/warga
```

Path ini berada di luar `public_html`. Jangan membuat symlink berkas ke document root.

Buat kunci acak di server:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Kunci API ini tidak boleh sama dengan kunci PWA. Set permission:

```bash
chmod 600 .env
```

Uji dari server atau komputer Anda:

```bash
curl -fsS https://api-warga-smartdesa.mediaverse.co.id/v1/health
curl -i https://api-warga-smartdesa.mediaverse.co.id/v1/unknown
```

Health produksi harus mengembalikan `success: true` dan database `ready`. Endpoint yang tidak dikenal harus mengembalikan JSON 404.

## 5. Konfigurasi PWA

Pada root PWA, salin `.env.example` menjadi `.env`, lalu isi:

```text
APP_ENV=production
APP_URL=https://warga-smartdesa.mediaverse.co.id/
APP_KEY=<kunci acak berbeda dari API>
WARGA_DEMO_MODE=0
WARGA_CENTRAL_API_URL=https://api-warga-smartdesa.mediaverse.co.id/v1/
PRIVATE_STORAGE_PATH=/home/USER/smartdesa-private/warga
DB_HOST=<host database dari Hostinger>
DB_USER=<user PWA>
DB_PASS=<password PWA>
DB_NAME=smartdesa_warga
```

Set permission `.env` menjadi `600`. Pastikan folder `PRIVATE_STORAGE_PATH` writable oleh PHP. Folder `application/sessions` juga harus writable.

### Optimasi gambar Pasar Digital

Upload foto produk diproses di server sebelum disimpan: sisi terpanjang dibatasi 1.600 px,
file utama ditulis ulang sebagai WebP kualitas 84, thumbnail kartu dibuat sebagai WebP 640 px
kualitas 82, dan metadata EXIF tidak ikut terbawa. Gambar lama tetap dapat ditampilkan; thumbnail
WebP-nya dibuat otomatis saat pertama kali diminta. Endpoint gambar mengirim URL berversi dan
service worker menyimpan respons gambar publik pada Cache Storage.

Pastikan PHP production memiliki GD dengan dukungan WebP sebelum membuka menu tambah produk:

```bash
php -r 'var_export(array("gd" => extension_loaded("gd"), "webp" => function_exists("imagewebp"), "jpeg" => function_exists("imagecreatefromjpeg"), "png" => function_exists("imagecreatefrompng"))); echo PHP_EOL;'
```

Hasil `gd` dan `webp` harus `true`. Fitur ini tidak memerlukan migrasi database tambahan karena
thumbnail disimpan sebagai berkas saudara (`nama-thumb.webp`) di folder private yang sama.

## 6. Hubungkan instalasi desa secara otomatis

Seed dan migrasi wilayah sudah memuat seluruh 332 kampung/kelurahan Kabupaten Jayawijaya.
Warga memilih distrik dan kampung/kelurahan pada formulir; mereka tidak perlu mengetik kode
wilayah. Desa juga tidak menerima kode atau mengatur API secara manual. Konfigurasikan satu
bootstrap universal pada API pusat, lalu bawa hanya konfigurasi builder privat ke komputer
yang membangun installer SmartDesa.

Setelah tenant desa resmi ada di `village_tenants`, jalankan dari root API:

```bash
API_ENV="$HOME/domains/api-warga-smartdesa.mediaverse.co.id/public_html/.env"
php tools/configure_auto_enrollment.php \
  --env="$API_ENV" \
  --builder-output="$HOME/smartdesa-private/warga-builder.env" \
  --write
```

File `warga-builder.env` tidak boleh masuk Git atau `public_html`. Impor nilainya ke
`.env.build` pada workstation builder. Saat Administrator pertama kali membuka aplikasi
dengan internet tersedia, SmartDesa membaca kode desa dari Identitas Desa, meminta
kredensial instalasi unik, menyimpannya lokal, lalu menghapus bootstrap dari `.env` lokal.
Tool provisioning dan kode sekali pakai hanya dipakai sebagai pemulihan instalasi lama.

## 7. Checklist sebelum dibuka

- DNS kedua subdomain mengarah ke hosting dan SSL aktif.
- `API_DEMO_MODE=0` dan `WARGA_DEMO_MODE=0`.
- `.env` kedua aplikasi berada di root masing-masing dan permission `600`.
- `PRIVATE_STORAGE_PATH` berada di luar `public_html`.
- Nilai `PRIVATE_STORAGE_PATH` API dan PWA sama persis dan writable oleh kedua aplikasi.
- Database API dan PWA terhubung, tetapi user database tetap terpisah bila memungkinkan.
- Migration prasyarat `001` sampai `015` sudah selesai; `016` dan `017` sudah dijalankan untuk
  Pasar Digital; `018` dan `019` selesai bila fitur terkait diaktifkan.
- Akun demo tidak digunakan di produksi.
- Backup database dan folder privat dibuat sebelum onboarding desa pertama.

## 8. Catatan performa PWA

Rilis ini memakai bundle CSS Bootstrap yang sudah dipangkas, runtime AppKit hanya untuk fitur
yang dipakai, bundle notifikasi terpisah, gambar hero responsif, dan URL aset berversi. Header
cache satu tahun hanya diberikan pada URL yang memiliki `?v=...`; berkas lama tanpa versi tetap
mengikuti TTL host. Service worker melakukan navigation preload, menyimpan shell minimum untuk
mode offline, dan mencache gambar produk publik berdasarkan URL versi/varian. Setelah deploy,
verifikasi worker dan header aset:

```bash
PWA_ROOT="$HOME/domains/warga-smartdesa.mediaverse.co.id/public_html"
curl -fsSI "https://warga-smartdesa.mediaverse.co.id/service-worker.js" | sed -n '1,12p'
curl -fsSI "https://warga-smartdesa.mediaverse.co.id/assets/js/warga.min.js?v=1" | sed -n '1,12p'
curl -fsS "https://warga-smartdesa.mediaverse.co.id/.well-known/assetlinks.json" >/dev/null
```

`service-worker.js` harus mengirim `Cache-Control: no-cache`/`no-store`, sedangkan aset berversi
boleh mengirim `immutable`. Bila worker lama masih terlihat, tutup semua tab PWA lalu buka ulang
sekali agar worker baru mengambil alih.
