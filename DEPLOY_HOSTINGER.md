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

Jika database sudah pernah dibuat, impor semua berkas pada `database/migrations` dari
`001_*.sql` sampai `016_*.sql` sesuai urutan. Rangkaian ini menambahkan autentikasi sinkron,
seluruh wilayah Jayawijaya, aktivasi otomatis, katalog Master Surat, direktori penduduk,
pengaman satu akun per penduduk, metadata PDF resmi, kunci snapshot sepanjang 120 karakter,
penyimpanan terenkripsi NIK dan No. KK untuk ditampilkan kepada pemilik akun, serta tabel
Pasar Digital untuk toko, produk, kategori, dan gambar privat.

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
- Migrasi `001` sampai `010` sudah selesai.
- Akun demo tidak digunakan di produksi.
- Backup database dan folder privat dibuat sebelum onboarding desa pertama.
