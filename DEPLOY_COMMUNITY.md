# Deployment Layanan Warga

Database PWA dan API memakai database yang sama. Jalankan pada server Hostinger:

    cd ~/repositories/api_warga
    git pull --ff-only origin main
    bash scripts/deploy-hostinger.sh

Script mengambil source API/PWA, memasang dependensi, mencadangkan database,
menjalankan migrasi 006 sampai 014, lalu menyalin source tanpa menimpa `.env`
dan data runtime. Jangan hanya menjalankan migrasi 013: migrasi 014 juga
diperlukan untuk sinkronisasi akun petugas.

Pada repository PWA, buat VAPID satu kali jika belum dikonfigurasi:

    cd ~/repositories/smardesa_warga
    php tools/generate-vapid.php

Simpan tiga hasilnya ke `~/domains/warga-smartdesa.mediaverse.co.id/public_html/.env`.
Gunakan alamat kontak pengelola pada `WARGA_VAPID_SUBJECT`.
Jangan mengganti pasangan VAPID yang sudah aktif karena perangkat telah
berlangganan dengan kunci tersebut. `WARGA_VAPID_PRIVATE_KEY` hanya boleh berada
di `.env` privat, dengan permission `600`. Jalankan worker secara berkala dari
cron Hostinger:

    * * * * * cd /home/u680017518/domains/warga-smartdesa.mediaverse.co.id/public_html && php index.php push_worker run >/dev/null 2>&1

Warga mengaktifkan notifikasi dari menu Akun. Android kemudian menampilkan
notifikasi pada status bar; bunyi dan getar mengikuti pengaturan kanal notifikasi
Android/browser. Worker tidak menaruh NIK, isi pengaduan, atau isi surat pada
lock screen.
