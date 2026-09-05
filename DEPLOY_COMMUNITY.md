# Deployment Layanan Warga

Database PWA dan API memakai database yang sama. Setelah pull kedua repository,
jalankan migrasi pada database API:

    cat database/migrations/013_community_services.sql | mysql -h localhost -u USER_DB -p NAMA_DB

Pada root PWA, buat VAPID satu kali:

    php tools/generate-vapid.php

Simpan tiga hasilnya ke `.env` PWA. `WARGA_VAPID_PRIVATE_KEY` hanya boleh berada
di `.env` privat, dengan permission `600`. Jalankan worker secara berkala dari
cron Hostinger:

    * * * * * cd /home/USER/domains/warga-smartdesa.mediaverse.co.id/public_html && php index.php push_worker run >/dev/null 2>&1

Warga mengaktifkan notifikasi dari menu Akun. Android kemudian menampilkan
notifikasi pada status bar; bunyi dan getar mengikuti pengaturan kanal notifikasi
Android/browser. Worker tidak menaruh NIK, isi pengaduan, atau isi surat pada
lock screen.
