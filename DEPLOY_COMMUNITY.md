# Deployment Layanan Warga

Database PWA dan API memakai database yang sama. Jalankan pada server Hostinger:

    cd ~/repositories/api_warga
    git pull --ff-only origin main
    bash scripts/deploy-hostinger.sh

Script mengambil source API/PWA, memasang dependensi, mencadangkan database,
menjalankan migrasi 006 sampai 014, lalu menyalin source tanpa menimpa `.env`
dan data runtime. Jangan hanya menjalankan migrasi 013: migrasi 014 juga
diperlukan untuk sinkronisasi akun petugas.

Pasang VAPID satu kali dari repository PWA. Perintah berikut langsung
menyimpan konfigurasi ke `.env` produksi, membuat backup privat, dan tidak
menampilkan private key:

    cd ~/repositories/smardesa_warga
    php tools/configure-vapid.php \
      --env="$HOME/domains/warga-smartdesa.mediaverse.co.id/public_html/.env" \
      --backup-dir="$HOME/smartdesa-private/backups" \
      --subject="mailto:admin@mediaverse.co.id"

Gunakan alamat kontak pengelola pada `--subject`. Kunci yang lengkap tetap
dipertahankan saat perintah diulang. Jika hanya satu kunci tersimpan, perintah
berhenti agar tidak mengganti pasangan yang sudah digunakan perangkat.
`tools/generate-vapid.php` lama hanya mencetak kunci, bukan menyimpannya.

Tes melalui SSH (tanpa bintang jadwal cron):

    cd ~/domains/warga-smartdesa.mediaverse.co.id/public_html
    php index.php push_worker run --verbose

`--verbose` hanya menampilkan ID langganan dan delapan karakter terakhir hash
endpoint (bukan URL atau kunci rahasia). Output ini berguna untuk memastikan
push benar-benar dikirim ke perangkat yang sedang diuji. `Push terkirim: N`
berarti layanan push (misalnya FCM) menerima paket; Android masih dapat
menahannya bila izin aplikasi atau kanal notifikasi dimatikan.

Di hPanel website PWA, buka Cron Jobs, pilih Custom, dan isi Command to Run:

    cd /home/u680017518/domains/warga-smartdesa.mediaverse.co.id/public_html && /usr/bin/php index.php push_worker run >> /home/u680017518/smartdesa-private/push-worker.log 2>&1

Pilih jadwal setiap menit. Lima tanda `*` berada pada kolom jadwal cron, bukan
pada kolom perintah ataupun terminal SSH. Tidak perlu membuat cron kedua jika
worker tersebut sudah dijadwalkan.

Buat folder log sekali sebelum memakai perintah di atas:

    mkdir -p ~/smartdesa-private

Setelah menunggu satu sampai dua menit, cek:

    tail -n 30 ~/smartdesa-private/push-worker.log

Perintah lengkap dengan `cd` bersifat aman meskipun Cron menjalankan perintah
dari direktori lain. `index.php` juga memuat `.env` berdasarkan lokasi file,
sehingga tidak perlu menyalin `.env` ke repository.

Pada instalasi PWA pertama, aplikasi menampilkan informasi **Aktifkan
notifikasi layanan** setelah aplikasi dibuka. Dialog izin situs tetap diminta
setelah warga menekan tombol **Izinkan Notifikasi**; proses ini memang tidak
dapat ditampilkan oleh layar installer APK. Pada TWA Android versi 1.0.6
(versionCode 9), izin **Pemberitahuan** aplikasi juga diminta satu kali saat
aplikasi dibuka. Izinkan keduanya agar notifikasi dapat tampil ketika aplikasi
ditutup. Langganan yang dibuat sebelum login akan dikaitkan otomatis ke akun
setelah warga berhasil masuk. Menu Akun tetap menyediakan toggle sebagai jalur
pengaktifan ulang.

Untuk menguji ulang onboarding pada perangkat yang pernah menekan **Nanti
saja**, hapus data situs/aplikasi atau hapus `localStorage` key
`sdw-notification-onboarding-v1`. Pastikan `WARGA_VAPID_PUBLIC_KEY` dan pasangan
kunci VAPID di server sudah lengkap; tanpa konfigurasi itu dialog tidak akan
ditampilkan.

Android kemudian menampilkan notifikasi pada status bar; bunyi dan getar
mengikuti pengaturan kanal notifikasi Android/browser. Worker tidak menaruh NIK,
isi pengaduan, atau isi surat pada lock screen.

Untuk Android 13 atau lebih baru, izin situs dan izin aplikasi adalah dua hal
berbeda. Buka **Setelan → Aplikasi → SI DAPULIK → Notifikasi**, aktifkan
**Izinkan pemberitahuan**, lalu buka kanal yang dibuat SI DAPULIK dan pilih
**Default/Alert**, bukan **Senyap**. Jika ada beberapa perangkat, matikan lalu
nyalakan kembali sakelar pemberitahuan di menu Akun pada HP yang sedang diuji;
langkah ini mendaftarkan endpoint HP tersebut sebagai langganan paling baru.
Jalankan query berikut untuk melihat ID langganannya (jangan membagikan URL
endpoint):

    SELECT id,user_id,endpoint_hash,created_at,updated_at
    FROM warga_push_subscriptions ORDER BY updated_at DESC;

Setelah membuat satu pemberitahuan baru, cocokkan ID tersebut pada tabel
pengiriman:

    SELECT d.notification_id,d.subscription_id,d.status,d.attempts,
           d.next_attempt_at,n.title,n.created_at
    FROM warga_push_deliveries d
    JOIN notifications n ON n.id=d.notification_id
    ORDER BY n.created_at DESC LIMIT 30;

`status=sent` pada ID HP yang benar membuktikan provider menerima paket. Jika
status sudah `sent` tetapi tidak ada ikon di status bar, masalahnya berada pada
izin/kanal Android atau delegasi TWA, bukan Cron. Pastikan provider TWA adalah
Chrome dan gunakan rilis Android terbaru; perubahan native pada TWA memerlukan
`versionCode` baru di Play Store.

Jika aplikasi menampilkan **Izin diblokir**, izin sebelumnya pernah ditolak.
Pada PWA Android buka **Info aplikasi → Notifikasi → Izinkan** (dan pilih kanal
notifikasi yang tidak disetel Senyap), lalu kembali ke menu Akun dan aktifkan
toggle lagi. Pada browser desktop gunakan **Pengaturan situs → Notifikasi →
Izinkan**, kemudian pastikan suara notifikasi browser dan sistem operasi tidak
dibisukan. Web Push tidak dapat memilih file suara sendiri; suara/getar memang
dikendalikan oleh kanal notifikasi perangkat.
