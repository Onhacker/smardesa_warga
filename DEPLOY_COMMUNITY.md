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
    php index.php push_worker run

Di hPanel website PWA, buka Cron Jobs, pilih Custom, dan isi Command to Run:

    /usr/bin/php /home/u680017518/domains/warga-smartdesa.mediaverse.co.id/public_html/index.php push_worker run

Pilih jadwal setiap menit. Lima tanda `*` berada pada kolom jadwal cron, bukan
pada kolom perintah ataupun terminal SSH. Tidak perlu membuat cron kedua jika
worker tersebut sudah dijadwalkan.

Pada instalasi PWA/TWA pertama, aplikasi menampilkan informasi **Aktifkan
notifikasi layanan** setelah aplikasi dibuka. Dialog izin sistem baru diminta
setelah warga menekan tombol **Izinkan Notifikasi**; proses ini memang tidak
dapat ditampilkan oleh layar installer APK. Langganan yang dibuat sebelum
login akan dikaitkan otomatis ke akun setelah warga berhasil masuk. Menu Akun
tetap menyediakan toggle sebagai jalur pengaktifan ulang.

Untuk menguji ulang onboarding pada perangkat yang pernah menekan **Nanti
saja**, hapus data situs/aplikasi atau hapus `localStorage` key
`sdw-notification-onboarding-v1`. Pastikan `WARGA_VAPID_PUBLIC_KEY` dan pasangan
kunci VAPID di server sudah lengkap; tanpa konfigurasi itu dialog tidak akan
ditampilkan.

Android kemudian menampilkan notifikasi pada status bar; bunyi dan getar
mengikuti pengaturan kanal notifikasi Android/browser. Worker tidak menaruh NIK,
isi pengaduan, atau isi surat pada lock screen.

Jika aplikasi menampilkan **Izin diblokir**, izin sebelumnya pernah ditolak.
Pada PWA Android buka **Info aplikasi → Notifikasi → Izinkan** (dan pilih kanal
notifikasi yang tidak disetel Senyap), lalu kembali ke menu Akun dan aktifkan
toggle lagi. Pada browser desktop gunakan **Pengaturan situs → Notifikasi →
Izinkan**, kemudian pastikan suara notifikasi browser dan sistem operasi tidak
dibisukan. Web Push tidak dapat memilih file suara sendiri; suara/getar memang
dikendalikan oleh kanal notifikasi perangkat.
