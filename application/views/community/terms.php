<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<article class="warga-legal-page">
    <header class="warga-legal-hero">
        <div class="warga-legal-hero-icon"><i class="fa fa-file-signature" aria-hidden="true"></i></div>
        <div>
            <p class="warga-legal-kicker">Dokumen layanan</p>
            <h1>Syarat &amp; Ketentuan</h1>
            <p>Aturan penggunaan Smart Kampung untuk layanan administrasi warga.</p>
        </div>
    </header>

    <div class="warga-legal-card">
        <p class="warga-legal-updated">Berlaku mulai: <?= e(tanggal_id('2026-09-05')) ?></p>
        <p>Dengan membuat akun atau menggunakan Smart Kampung, Anda menyetujui ketentuan berikut. Jika tidak setuju, jangan gunakan fitur pengajuan atau unggah berkas pada aplikasi.</p>

        <section>
            <h2>1. Akun warga</h2>
            <p>Anda wajib memberikan data yang benar, terbaru, dan dapat dipertanggungjawabkan. Satu akun digunakan oleh pemiliknya sendiri. Jaga kerahasiaan kata sandi dan segera laporkan penggunaan yang tidak sah melalui halaman Kontak.</p>
        </section>
        <section>
            <h2>2. Penggunaan layanan</h2>
            <ul>
                <li>gunakan aplikasi untuk keperluan administrasi dan informasi kampung/desa;</li>
                <li>isi tujuan surat dan data pendukung secara lengkap serta jujur;</li>
                <li>pastikan berkas yang dikirim milik Anda atau Anda berwenang menggunakannya; dan</li>
                <li>jangan mencoba mengganggu sistem, mengakses akun orang lain, atau mengirim konten yang melanggar hukum.</li>
            </ul>
        </section>
        <section>
            <h2>3. Permohonan dan dokumen</h2>
            <p>Pengiriman permohonan bukan jaminan bahwa surat akan diterbitkan. Petugas dapat meminta perbaikan, menolak, atau menunda permohonan jika data tidak lengkap, tidak sesuai, atau perlu verifikasi tambahan. Surat resmi hanya dianggap sah setelah diterbitkan melalui proses yang ditetapkan kampung/desa.</p>
        </section>
        <section>
            <h2>4. Pengaduan dan informasi</h2>
            <p>Pengaduan harus disampaikan dengan bahasa yang sopan dan informasi yang dapat diperiksa. Pengumuman, status layanan, dan waktu tanggapan dapat berubah sesuai kondisi lapangan dan keputusan pemerintah kampung/desa.</p>
        </section>
        <section>
            <h2>5. Ketersediaan layanan</h2>
            <p>Kami berupaya menjaga aplikasi tetap tersedia, tetapi layanan dapat dihentikan sementara untuk pemeliharaan, gangguan jaringan, atau keadaan di luar kendali. Simpan salinan informasi penting dan gunakan kanal kantor jika permohonan mendesak.</p>
        </section>
        <section>
            <h2>6. Perubahan ketentuan</h2>
            <p>Fitur dan ketentuan dapat diperbarui untuk menyesuaikan kebutuhan layanan atau aturan yang berlaku. Perubahan penting akan ditampilkan di aplikasi. Penggunaan setelah perubahan berarti Anda menerima versi terbaru.</p>
        </section>
        <section>
            <h2>7. Hubungi kami</h2>
            <p>Untuk pertanyaan, koreksi data, atau kendala layanan, silakan buka halaman <a href="<?= site_url('kontak') ?>">Kontak</a> dan gunakan informasi kantor yang tercantum di sana.</p>
        </section>
    </div>
</article>
