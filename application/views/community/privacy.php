<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<article class="warga-legal-page">
    <header class="warga-legal-hero">
        <div class="warga-legal-hero-icon"><i class="fa fa-shield-alt" aria-hidden="true"></i></div>
        <div>
            <p class="warga-legal-kicker">Dokumen layanan</p>
            <h1>Kebijakan Privasi</h1>
            <p>Penjelasan singkat tentang data yang digunakan dalam layanan <?= e($village['institution'] ?? 'kampung/desa') ?>.</p>
        </div>
    </header>

    <div class="warga-legal-card">
        <p class="warga-legal-updated">Terakhir diperbarui: <?= e(tanggal_id('2026-09-05')) ?></p>
        <p>Smart Kampung membantu warga mengajukan surat dan menerima informasi layanan secara digital. Kami menghormati privasi warga dan menggunakan data hanya untuk menjalankan layanan pemerintahan kampung/desa.</p>

        <section>
            <h2>1. Data yang kami gunakan</h2>
            <p>Data dapat mencakup nama, NIK, nomor KK, alamat, nomor telepon, email, data akun, detail permohonan, serta berkas pendukung yang Anda kirim. Kami juga dapat menerima data teknis minimum seperti jenis perangkat dan log keamanan agar aplikasi tetap berfungsi.</p>
        </section>
        <section>
            <h2>2. Tujuan penggunaan</h2>
            <ul>
                <li>memverifikasi identitas dan kelayakan akun warga;</li>
                <li>memproses, memantau, dan menerbitkan permohonan surat;</li>
                <li>mengirim pemberitahuan status, pengumuman, dan tanggapan pengaduan; dan</li>
                <li>menjaga keamanan, mencegah penyalahgunaan, serta memperbaiki kualitas layanan.</li>
            </ul>
        </section>
        <section>
            <h2>3. Akses dan pembagian data</h2>
            <p>Data permohonan hanya dapat diakses oleh warga pemilik akun dan petugas kampung/desa yang berwenang sesuai kebutuhan pelayanan. Data tidak dijual atau dipakai untuk iklan. Jika penyedia teknis diperlukan untuk menjalankan infrastruktur, aksesnya dibatasi sesuai fungsi dan kewajiban keamanan.</p>
        </section>
        <section>
            <h2>4. Penyimpanan dan keamanan</h2>
            <p>Kami menerapkan pembatasan akses, perlindungan sesi, dan pemeriksaan berkas untuk mengurangi risiko kehilangan atau akses tanpa izin. NIK, nomor KK, dan dokumen layanan diperlakukan sebagai data terbatas. Tidak ada metode penyimpanan atau pengiriman yang dapat menjamin keamanan mutlak, sehingga mohon gunakan kata sandi yang kuat dan jangan membagikan kode akses.</p>
        </section>
        <section>
            <h2>5. Masa simpan</h2>
            <p>Data disimpan selama diperlukan untuk penyelesaian pelayanan, kewajiban administrasi, audit, atau penyelesaian sengketa. Setelah tidak lagi diperlukan, data akan dihapus, dianonimkan, atau disimpan sesuai ketentuan kearsipan yang berlaku.</p>
        </section>
        <section>
            <h2>6. Hak Anda</h2>
            <p>Anda dapat memeriksa dan memperbarui data kontak melalui menu Akun, meminta koreksi data yang keliru, serta menghubungi kantor kampung/desa untuk pertanyaan tentang pemrosesan data. Permintaan penghapusan dapat dibatasi jika data masih diperlukan untuk pelayanan atau kewajiban hukum.</p>
        </section>
        <section>
            <h2>7. Perubahan kebijakan</h2>
            <p>Kebijakan ini dapat diperbarui ketika fitur atau aturan layanan berubah. Versi terbaru akan ditampilkan di halaman ini. Untuk pertanyaan, gunakan halaman <a href="<?= site_url('kontak') ?>">Kontak</a> yang tersedia di aplikasi.</p>
        </section>
    </div>
</article>
