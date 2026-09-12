<?php defined('BASEPATH') OR exit('No direct script access allowed'); $supportEmail = isset($supportEmail) ? (string) $supportEmail : 'admin@mediaverse.co.id'; ?>
<article class="warga-legal-page">
    <header class="warga-legal-hero">
        <div class="warga-legal-hero-icon"><i class="fa fa-file-signature" aria-hidden="true"></i></div>
        <div>
            <p class="warga-legal-kicker">Dokumen layanan</p>
            <h1>Syarat &amp; Ketentuan</h1>
            <p>Aturan penggunaan SI DAPULIK untuk layanan administrasi warga.</p>
        </div>
    </header>

    <div class="warga-legal-card">
        <p class="warga-legal-updated">Diperbarui: <?= e(tanggal_id('2026-09-13')) ?></p>
        <p>Dengan membuat akun atau menggunakan SI DAPULIK, Anda menyetujui ketentuan berikut. Jika tidak setuju, jangan gunakan fitur pengajuan atau unggah berkas pada aplikasi.</p>

        <section>
            <h2>1. Akun warga</h2>
            <p>Anda wajib memberikan data yang benar, terbaru, dan dapat dipertanggungjawabkan. Satu akun digunakan oleh pemiliknya sendiri. Jaga kerahasiaan kata sandi dan segera laporkan penggunaan yang tidak sah melalui halaman Kontak.</p>
        </section>
        <section>
            <h2>2. Penggunaan layanan</h2>
            <ul>
                <li>gunakan aplikasi untuk keperluan administrasi dan informasi <?= e($institutionLower) ?>;</li>
                <li>isi tujuan surat dan data pendukung secara lengkap serta jujur;</li>
                <li>pastikan berkas yang dikirim milik Anda atau Anda berwenang menggunakannya; dan</li>
                <li>jangan mencoba mengganggu sistem, mengakses akun orang lain, atau mengirim konten yang melanggar hukum.</li>
            </ul>
        </section>
        <section>
            <h2>3. Permohonan dan dokumen</h2>
            <p>Pengiriman permohonan bukan jaminan bahwa surat akan diterbitkan. Petugas dapat meminta perbaikan, menolak, atau menunda permohonan jika data tidak lengkap, tidak sesuai, atau perlu verifikasi tambahan. Surat resmi hanya dianggap sah setelah diterbitkan melalui proses yang ditetapkan <?= e($institutionLower) ?>.</p>
        </section>
        <section>
            <h2>4. Pengaduan dan informasi</h2>
            <p>Pengaduan harus disampaikan dengan bahasa yang sopan dan informasi yang dapat diperiksa. Pengumuman, status layanan, dan waktu tanggapan dapat berubah sesuai kondisi lapangan dan keputusan pemerintah <?= e($institutionLower) ?>.</p>
        </section>
        <section id="pasar-digital">
            <h2>5. Pasar Digital dan produk terlarang</h2>
            <p>Penjual bertanggung jawab memastikan informasi, harga, foto, kepemilikan, keamanan, dan legalitas setiap produk atau jasa yang ditawarkan. Dilarang menerbitkan, mempromosikan, atau mengarahkan pengguna untuk membeli:</p>
            <ul>
                <li>rokok, cerutu, tembakau, vape, rokok elektronik, cairan vape, kantong nikotin, atau produk lain yang mengandung maupun mempromosikan nikotin;</li>
                <li>minuman beralkohol karena Pasar Digital ini tidak menyediakan verifikasi usia;</li>
                <li>ganja, produk THC/CBD, narkotika, psikotropika, obat terlarang, obat keras atau obat resep yang dijual tanpa kewenangan dan izin yang sah;</li>
                <li>senjata api, amunisi, bahan peledak, serta barang yang dibuat atau dipasarkan untuk melukai orang;</li>
                <li>barang curian, palsu, melanggar hak kekayaan intelektual, pornografi, layanan seksual, satwa dilindungi, atau barang lain yang dilarang oleh hukum; dan</li>
                <li>makanan, kosmetik, atau barang konsumsi yang kedaluwarsa, berbahaya, tidak layak, atau wajib berizin tetapi belum memiliki izin yang diperlukan.</li>
            </ul>
            <p>SI DAPULIK berhak menolak atau menghapus produk, membatasi akun penjual, serta meneruskan laporan kepada pihak berwenang jika ditemukan pelanggaran. Persetujuan pada formulir produk merupakan pernyataan penjual bahwa produk memenuhi ketentuan ini.</p>
        </section>
        <section>
            <h2>6. Ketersediaan layanan</h2>
            <p>Kami berupaya menjaga aplikasi tetap tersedia, tetapi layanan dapat dihentikan sementara untuk pemeliharaan, gangguan jaringan, atau keadaan di luar kendali. Simpan salinan informasi penting dan gunakan kanal kantor jika permohonan mendesak.</p>
        </section>
        <section>
            <h2>7. Perubahan ketentuan</h2>
            <p>Fitur dan ketentuan dapat diperbarui untuk menyesuaikan kebutuhan layanan atau aturan yang berlaku. Perubahan penting akan ditampilkan di aplikasi. Penggunaan setelah perubahan berarti Anda menerima versi terbaru.</p>
        </section>
        <section>
            <h2>8. Hubungi kami</h2>
            <p>Untuk pertanyaan, koreksi data, atau kendala layanan, kirim email ke <a href="mailto:<?= e($supportEmail) ?>"><?= e($supportEmail) ?></a>. Permintaan penghapusan akun dijelaskan pada halaman <a href="<?= site_url('permintaan-hapus-akun') ?>">Permintaan Penghapusan Akun</a>.</p>
        </section>
    </div>
</article>
