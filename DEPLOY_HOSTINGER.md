# Deployment Hostinger

Dokumen ini memasang PWA warga dan API sinkronisasi pada dua subdomain terpisah:

- `warga-smartdesa.mediaverse.co.id` -> `/home/USER/domains/warga-smartdesa.mediaverse.co.id/public_html`
- `api-warga-smartdesa.mediaverse.co.id` -> `/home/USER/domains/api-warga-smartdesa.mediaverse.co.id/public_html`

Ganti `USER` dengan nama akun Hostinger. Link hPanel bukan URL aplikasi; gunakan URL subdomain di atas setelah SSL aktif.

Untuk situs yang sudah aktif, urutan upgrade yang aman adalah: backup database dan storage privat,
`git pull`, pasang dependensi dan jalankan tes, siapkan folder session privat, jalankan migration
baru pada kode database lama, perbarui `.env`, lalu terakhir `rsync` source baru. Dengan urutan ini,
pengaman session dan pendaftaran tidak pernah berjalan tanpa schema pendukungnya.

## 1. Deploy source PWA dari Git

Clone repository ke folder repository privat akun Hostinger, lalu pasang dependensi. Gunakan binary
PHP yang sama dengan versi domain di hPanel; pada Hostinger path biasanya seperti
`/opt/alt/php83/usr/bin/php` atau `/opt/alt/php84/usr/bin/php` (verifikasi path pada akun Anda):

```bash
mkdir -p "$HOME/repositories"
cd "$HOME/repositories"
git clone git@github.com:Onhacker/smardesa_warga.git
cd smardesa_warga
PHP_BIN=/opt/alt/php83/usr/bin/php
test -x "$PHP_BIN" || { echo "Binary PHP belum ditemukan"; exit 1; }
"$PHP_BIN" "$(command -v composer)" install --no-dev --prefer-dist --optimize-autoloader --no-interaction
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

Untuk deployment berikutnya, jangan langsung `rsync`. Jalankan backup, `git pull --ff-only`,
Composer dan tes dengan `PHP_BIN`, migration baru, lalu perintah `rsync` yang sama. File `.env`
produksi dibuat langsung pada document root dan tidak pernah disimpan dalam Git.

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
idempoten. Rangkaian yang relevan saat ini berjalan dari `001_*.sql` sampai `024_*.sql` dan
menambahkan autentikasi sinkron,
seluruh wilayah Jayawijaya, aktivasi otomatis, katalog Master Surat, direktori penduduk,
pengaman satu akun per penduduk, metadata PDF resmi, kunci snapshot sepanjang 120 karakter,
penyimpanan terenkripsi NIK dan No. KK untuk ditampilkan kepada pemilik akun, serta tabel
Pasar Dapulik untuk toko, produk, kategori, dan gambar privat. Untuk rilis ini, migration
`016_marketplace.sql` wajib dijalankan lebih dahulu, lalu `017_marketplace_reviews.sql` untuk
rating/ulasan. `018_global_nik_uniqueness.sql` dan `019_monitoring_auth.sql` dijalankan sesuai
kebutuhan instalasi setelah migration pendahulunya selesai. `020_announcement_attachments.sql`
wajib dijalankan untuk fitur lampiran pengumuman. `021_marketplace_categories.sql` menambahkan
kategori Pasar Dapulik terbaru pada instalasi yang sudah menjalankan migration marketplace.
`022_global_service_catalog.sql` mengalihkan daftar layanan PWA ke satu katalog global yang
diterbitkan SmartDesa pusat. Karena API dan PWA memakai database yang sama, migration `022`
cukup dijalankan satu kali. `023_security_hardening.sql` menambahkan pencabutan session
terpusat dan pembatasan percobaan pendaftaran; jalankan sekali setelah `022`.
`024_password_reset.sql` menambahkan OTP lupa kata sandi PWA; jalankan sekali setelah `023`.

Migration `023` menargetkan MariaDB Hostinger dan memakai `ADD ... IF NOT EXISTS`. Periksa versi
database lebih dahulu dan buat backup tepat sebelum DDL dijalankan. Contoh menjalankan migration
dari root repository (password dimasukkan pada prompt `mysql`, tidak ditulis di terminal history):

```bash
DB_HOST='localhost'
DB_USER='USER_DATABASE'
DB_NAME='smartdesa_warga'
REPO="$HOME/repositories/smardesa_warga"

cd "$REPO" || exit 1
mysql -h "$DB_HOST" -u "$DB_USER" -p -e 'SELECT VERSION();' "$DB_NAME"
mysql --default-character-set=utf8mb4 -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" \
  < "$REPO/database/migrations/016_marketplace.sql"
mysql --default-character-set=utf8mb4 -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" \
  < "$REPO/database/migrations/017_marketplace_reviews.sql"

# Lampiran pengumuman (wajib untuk fitur upload dan modal lampiran)
mysql --default-character-set=utf8mb4 -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" \
  < "$REPO/database/migrations/020_announcement_attachments.sql"
mysql --default-character-set=utf8mb4 -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" \
  < "$REPO/database/migrations/021_marketplace_categories.sql"

# Katalog layanan surat global (jalankan sekali pada database bersama API/PWA)
mysql --default-character-set=utf8mb4 -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" \
  < "$REPO/database/migrations/022_global_service_catalog.sql"
mysql --default-character-set=utf8mb4 -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" \
  < "$REPO/database/migrations/023_security_hardening.sql"
mysql --default-character-set=utf8mb4 -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" \
  < "$REPO/database/migrations/024_password_reset.sql"

# Verifikasi kolom, indeks, dan tabel reset password
mysql -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" \
  -e 'SHOW CREATE TABLE users\G SHOW CREATE TABLE registration_attempts\G SHOW CREATE TABLE warga_password_reset_requests\G'
```

Kode aplikasi tidak lagi menjalankan `CREATE TABLE` atau `ALTER TABLE` pada request. Beberapa
jalur autentikasi melakukan pemeriksaan metadata read-only satu kali per request untuk tetap
kompatibel selama rolling deployment; hasilnya tidak pernah dipakai untuk membuat atau mengubah
schema. Selesaikan migration sebelum membuka aplikasi untuk pengguna agar seluruh pengaman aktif.

## 3a. Uji runtime PHP sebelum production

Uji perubahan PHP pada staging/domain terpisah terlebih dahulu. Pastikan versi PHP domain di
hPanel sudah menunjuk 8.3 atau 8.4, lalu pakai binary CLI dari versi yang sama. `/usr/bin/php`
pada Hostinger dapat tetap menunjukkan PHP 8.0 meskipun versi domain sudah berbeda. Pastikan
runtime memuat `mysqli`, `curl`, `mbstring`, `openssl`, `zip`, `gd` (dengan WebP), `intl`, `xml`,
`dom`, `fileinfo`, dan `sodium`. Jalankan dari root repository:

```bash
REPO="$HOME/repositories/smardesa_warga"
PWA_ROOT="$HOME/domains/warga-smartdesa.mediaverse.co.id/public_html"
PHP_BIN=/opt/alt/php83/usr/bin/php

test -x "$PHP_BIN" || { echo "Sesuaikan PHP_BIN dengan versi PHP domain"; exit 1; }
cd "$REPO" || exit 1
"$PHP_BIN" -v
"$PHP_BIN" -m | sort
"$PHP_BIN" "$REPO/tools/check_runtime.php" --minimum=8.3 --strict-env --env="$PWA_ROOT/.env"
"$PHP_BIN" "$REPO/tools/tests/image_optimizer.php"
"$PHP_BIN" "$REPO/tools/tests/security_hardening.php"
"$PHP_BIN" "$(command -v composer)" check-platform-reqs --no-dev
"$PHP_BIN" "$(command -v composer)" validate --strict --no-check-publish
"$PHP_BIN" "$(command -v composer)" audit --locked --no-dev
```

Uji alur login/logout, pendaftaran, upload gambar permohonan, upload lampiran info, cetak
surat, push notification, dan marketplace sebelum mengganti versi PHP production. Upload gambar
di production akan ditolak bila GD WebP tidak tersedia (agar tidak ada berkas mentah yang lolos),
jadi pastikan seluruh baris fungsi `GD`, termasuk `GD imagewebp`, berstatus `OK`. Jangan
mengubah `config.platform.php` Composer ke 8.3 sebelum staging lolos; hal itu dapat membuat
server PHP 8.0 menginstal dependency yang tidak bisa dijalankan.

`check_runtime.php` hanya membaca daftar konfigurasi yang dibutuhkan dan tidak menampilkan
nilainya. `--env` harus menunjuk `.env` milik domain yang sedang diuji. Opsi `--strict-env`
membuat pemeriksaan gagal bila mode production, URL HTTPS, APP_KEY, private storage, URL API,
mode demo, folder session privat, atau mode CSP belum valid; status `WARN` tanpa opsi tersebut
bukan tanda bahwa production sudah siap. Pengecekan CLI tidak menggantikan pemilihan versi PHP
domain di hPanel, jadi uji juga halaman staging melalui HTTPS.

Pengaturan keamanan runtime yang disarankan pada `.env` production:

```text
WARGA_SESSION_EXPIRATION=604800
WARGA_SESSION_SAVE_PATH=/home/USER/smartdesa-private/sessions
WARGA_CSP_ENFORCE=0
```

Buat folder session privat dan atur permission sebelum mengaktifkan `WARGA_SESSION_SAVE_PATH`:

```bash
mkdir -p "$HOME/smartdesa-private/sessions"
chmod 750 "$HOME/smartdesa-private/sessions"
```

Path session harus absolut, sudah ada, writable, dan berada di luar `public_html`; aplikasi akan
menolak start dengan HTTP 503 bila syarat ini tidak terpenuhi. Setelah migration `023` diterapkan,
session lama yang tidak memiliki versi pencabutan akan diminta login ulang satu kali. Perubahan
password menaikkan versi tersebut dan mencabut session lain milik akun yang sama.

Biarkan CSP pada mode report-only selama QA. Setelah tidak ada pelanggaran dari resource yang
sah, ubah `WARGA_CSP_ENFORCE=1` dan deploy ulang.

## 3b. Urutan upgrade instalasi yang sudah aktif

Untuk rilis hardening ini, jalankan langkah berikut secara berurutan. Migration `023` aman bagi
kode lama, sehingga dipasang sebelum source baru. Jangan menunda migration sampai setelah rsync.

```bash
REPO="$HOME/repositories/smardesa_warga"
PWA_ROOT="$HOME/domains/warga-smartdesa.mediaverse.co.id/public_html"
PHP_BIN=/opt/alt/php83/usr/bin/php
DB_HOST='localhost'
DB_USER='USER_DATABASE'
DB_NAME='smartdesa_warga'

cd "$REPO" || exit 1
git switch main
git pull --ff-only origin main
"$PHP_BIN" "$(command -v composer)" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# Siapkan path yang sudah dicantumkan pada .env production.
mkdir -p "$HOME/smartdesa-private/sessions"
chmod 750 "$HOME/smartdesa-private/sessions"

# Buat backup database dan storage privat melalui fasilitas backup Hostinger
# atau mysqldump sebelum melanjutkan. Setelah backup terverifikasi:
mysql --default-character-set=utf8mb4 -h "$DB_HOST" -u "$DB_USER" -p "$DB_NAME" \
  < "$REPO/database/migrations/023_security_hardening.sql"

"$PHP_BIN" "$REPO/tools/check_runtime.php" --minimum=8.3 --strict-env --env="$PWA_ROOT/.env"
"$PHP_BIN" "$REPO/tools/tests/image_optimizer.php"
"$PHP_BIN" "$REPO/tools/tests/security_hardening.php"

rsync -a --delete \
  --exclude='.git/' \
  --exclude='.env' \
  --exclude='application/cache/*' \
  --exclude='application/logs/*' \
  --exclude='application/sessions/*' \
  --exclude='storage/*' \
  --exclude='uploads/requests/*' \
  "$REPO/" "$PWA_ROOT/"

mkdir -p "$PWA_ROOT/.well-known"
cp "$REPO/.well-known/assetlinks.json" "$PWA_ROOT/.well-known/assetlinks.json"
chmod 644 "$PWA_ROOT/.well-known/assetlinks.json"
```

Lakukan smoke test HTTPS untuk login, pendaftaran, unggah gambar, unggah PDF, Pasar Dapulik,
Info, permohonan surat, dan push notification. Pengguna dengan session dari sebelum migration
akan diminta login ulang satu kali; ini perilaku keamanan yang disengaja.

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

Set permission `.env` menjadi `600`. Pastikan `PRIVATE_STORAGE_PATH` dan path session yang aktif
writable oleh PHP. Jika `WARGA_SESSION_SAVE_PATH` digunakan, `application/sessions` tidak lagi
menjadi lokasi session production.

### Optimasi gambar Pasar Dapulik

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

Upload gambar permohonan dan lampiran info memakai pemeriksaan isi MIME, batas 10.000 px dan
16 megapiksel, lalu dikompresi ke WebP sisi terpanjang 1.600 px kualitas 84 tanpa metadata EXIF.
Pemeriksaan kapasitas GD dilakukan sebelum decode agar foto kamera yang terlalu besar tidak
menghabiskan memori worker. PDF tidak dikonversi; tetap disimpan sebagai PDF agar isi dan
kemampuan cetaknya tidak rusak.

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
  Pasar Dapulik; `018` sampai `023` sudah diterapkan sesuai urutan.
- Binary CLI yang dipakai saat tes sama dengan PHP 8.3/8.4 yang dipilih untuk domain di hPanel;
  runtime check, GD WebP, dan seluruh tes hardening berstatus `OK`.
- `WARGA_SESSION_SAVE_PATH` menunjuk folder privat yang writable di luar `public_html`.
- CSP tetap report-only (`WARGA_CSP_ENFORCE=0`) selama QA dan hanya dienforce setelah laporan
  pelanggaran resource sah sudah bersih.
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
