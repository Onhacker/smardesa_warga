<?php defined('BASEPATH') OR exit('No direct script access allowed');
$deletionInstitution = trim((string) ($village['institution'] ?? ($institutionLabel ?? 'Kampung')));
if ($deletionInstitution === '') $deletionInstitution = 'Kampung';
$deletionAppName = 'Smart ' . $deletionInstitution;
$deletionArea = trim((string) ($village['name'] ?? ''));
$deletionSubject = 'Permintaan penghapusan akun ' . $deletionAppName;
$deletionBody = "Saya meminta penghapusan akun dan data terkait pada aplikasi {$deletionAppName}.\n\n"
    . "Nama lengkap:\n"
    . "Email atau nomor telepon yang terdaftar:\n"
    . "Kampung/kelurahan yang dipilih saat mendaftar:\n"
    . "Alasan (opsional):\n\n"
    . "Saya memahami bahwa data pelayanan yang wajib menjadi arsip pemerintah dapat tetap disimpan sesuai ketentuan yang berlaku.";
$deletionMailto = 'mailto:' . $supportEmail
    . '?subject=' . rawurlencode($deletionSubject)
    . '&body=' . rawurlencode($deletionBody);
?>
<article class="warga-legal-page">
    <header class="warga-legal-hero">
        <div class="warga-legal-hero-icon"><i class="fa fa-user-times" aria-hidden="true"></i></div>
        <div>
            <p class="warga-legal-kicker">Kontrol data pengguna</p>
            <h1>Permintaan Penghapusan Akun</h1>
            <p>Ajukan penghapusan akun <?= e($deletionAppName) ?> dan data yang terkait dengannya.</p>
        </div>
    </header>

    <div class="warga-legal-card">
        <p class="warga-legal-updated">Terakhir diperbarui: <?= e(tanggal_id('2026-09-07')) ?></p>
        <p>Halaman publik ini berlaku untuk aplikasi <strong><?= e($deletionAppName) ?></strong><?= $deletionArea !== '' ? ' di wilayah ' . e($deletionArea) : '' ?> yang dikembangkan oleh <strong><?= e($developerName) ?></strong>. Anda tidak harus masuk ke aplikasi untuk membuka halaman atau mengirim permintaan.</p>

        <section aria-labelledby="deletion-steps-title">
            <h2 id="deletion-steps-title">1. Cara meminta penghapusan akun</h2>
            <ol>
                <li>Tekan tombol <strong>Kirim Permintaan Penghapusan</strong> di bawah atau kirim email ke <a href="mailto:<?= e($supportEmail) ?>"><?= e($supportEmail) ?></a>.</li>
                <li>Cantumkan nama lengkap, email atau nomor telepon yang terdaftar, serta kampung/kelurahan yang dipilih ketika mendaftar.</li>
                <li>Gunakan subjek <strong>Permintaan penghapusan akun <?= e($deletionAppName) ?></strong>. Alasan penghapusan boleh dikosongkan.</li>
                <li>Pengelola akan memverifikasi kepemilikan akun melalui kontak yang terdaftar. Permintaan yang telah terverifikasi diproses paling lambat 30 hari kalender.</li>
                <li>Konfirmasi hasil penghapusan atau alasan data tertentu harus dipertahankan akan dikirim melalui kontak yang telah diverifikasi.</li>
            </ol>
            <p><strong>Jangan pernah mengirim kata sandi, NIK lengkap, nomor KK lengkap, kode OTP, atau foto dokumen identitas melalui email.</strong></p>
            <p>
                <a class="btn btn-full btn-m bg-red-dark color-white rounded-s mt-3" href="<?= e($deletionMailto) ?>">
                    <i class="fa fa-paper-plane me-2" aria-hidden="true"></i>Kirim Permintaan Penghapusan
                </a>
            </p>
        </section>

        <section aria-labelledby="deletion-data-title">
            <h2 id="deletion-data-title">2. Data yang dihapus</h2>
            <p>Setelah permintaan disetujui, data yang tidak wajib menjadi arsip akan dihapus atau dianonimkan. Data ini mencakup kredensial untuk masuk, profil dan kontak akun, sesi serta langganan pemberitahuan perangkat, dan konten Pasar Digital yang masih berada di bawah kendali akun.</p>
        </section>

        <section aria-labelledby="retained-data-title">
            <h2 id="retained-data-title">3. Data yang mungkin tetap disimpan</h2>
            <p>Permohonan surat, surat yang telah diterbitkan, riwayat tindakan petugas, pengaduan resmi, dan dokumen lain yang menjadi arsip pemerintahan dapat tetap disimpan sesuai Jadwal Retensi Arsip instansi dan peraturan yang berlaku. Sebagian arsip dapat memiliki masa simpan panjang atau permanen. Data tersebut dibatasi aksesnya dan tidak lagi digunakan sebagai akun aktif.</p>
        </section>

        <section aria-labelledby="retention-title">
            <h2 id="retention-title">4. Masa pemrosesan dan retensi tambahan</h2>
            <ul>
                <li>permintaan yang telah terverifikasi diproses paling lambat 30 hari kalender;</li>
                <li>catatan permintaan dan bukti verifikasi disimpan paling lama 90 hari setelah proses selesai untuk keamanan dan penyelesaian keberatan; dan</li>
                <li>arsip pelayanan yang wajib dipertahankan mengikuti masa simpan pada Jadwal Retensi Arsip atau kewajiban hukum yang berlaku.</li>
            </ul>
        </section>

        <section aria-labelledby="deletion-help-title">
            <h2 id="deletion-help-title">5. Jika Anda tidak dapat masuk</h2>
            <p>Anda tetap dapat meminta penghapusan melalui email di atas. Sebutkan kontak yang pernah didaftarkan; pengelola akan meminta langkah verifikasi yang aman sebelum memproses permintaan. Penghapusan tidak dilakukan hanya berdasarkan nama agar akun orang lain tetap terlindungi.</p>
        </section>

        <section aria-labelledby="deletion-policy-title">
            <h2 id="deletion-policy-title">6. Informasi lebih lanjut</h2>
            <p>Pelajari cara data digunakan pada <a href="<?= site_url('kebijakan-privasi') ?>">Kebijakan Privasi</a> dan ketentuan penggunaan aplikasi pada <a href="<?= site_url('syarat-ketentuan') ?>">Syarat &amp; Ketentuan</a>.</p>
        </section>
    </div>
</article>
