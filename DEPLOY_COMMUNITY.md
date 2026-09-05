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

Warga mengaktifkan notifikasi dari menu Akun. Android kemudian menampilkan
notifikasi pada status bar; bunyi dan getar mengikuti pengaturan kanal notifikasi
Android/browser. Worker tidak menaruh NIK, isi pengaduan, atau isi surat pada
lock screen.
