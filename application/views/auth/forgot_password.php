<?php defined('BASEPATH') OR exit('No direct script access allowed');
$resetInstitution = trim((string) ($institutionLabel ?? ($footerVillage['institution'] ?? 'Kampung')));
if ($resetInstitution === '') $resetInstitution = 'Kampung';
$branding = isset($branding) && is_array($branding) ? $branding : array();
$resetBrand = trim((string) ($branding['nama_sistem'] ?? 'SIDAPULIK')) ?: 'SIDAPULIK';
$resetTagline = trim((string) ($branding['tagline'] ?? 'Layanan Digital Warga')) ?: 'Layanan Digital Warga';
$hasResetRequest = !empty($resetState['request_token']);
?>
<!DOCTYPE HTML>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="theme-color" content="#235fa4">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= e($resetBrand) ?>">
    <title><?= e($pageTitle) ?></title>
    <?php
    $shareTitle = $resetBrand . ' ' . trim((string) ($footerVillage['name'] ?? 'Jayawijaya')) . ' — ' . $resetTagline;
    $shareDescription = 'Akses layanan surat, info, pengaduan, pemberitahuan, dan Pasar Dapulik dalam satu aplikasi.';
    $shareUrl = base_url();
    $shareImage = warga_asset_url('assets/pwa/share-preview.png');
    $this->load->view('layouts/social_meta', array(
        'shareTitle' => $shareTitle,
        'shareDescription' => $shareDescription,
        'shareUrl' => $shareUrl,
        'shareImage' => $shareImage,
        'shareImageAlt' => $resetBrand . ', ' . strtolower($resetTagline),
        'branding' => $branding,
        'shareImageWidth' => 1200,
        'shareImageHeight' => 630
    ));
    ?>
    <link rel="stylesheet" href="<?= warga_asset_url('assets/v22/styles/bootstrap-warga.min.css') ?>">
    <link rel="stylesheet" href="<?= warga_asset_url('assets/v22/fonts/css/fontawesome-subset.min.css') ?>">
    <link rel="stylesheet" href="<?= warga_asset_url('assets/css/simp-v22.min.css') ?>">
    <link rel="stylesheet" href="<?= warga_asset_url('assets/css/warga.min.css') ?>">
    <link rel="stylesheet" href="<?= warga_asset_url('assets/css/warga-auth.min.css') ?>">
    <link rel="stylesheet" href="<?= warga_asset_url('assets/css/footer-share.css') ?>">
    <link rel="manifest" href="<?= site_url('manifest') ?>">
    <link rel="icon" href="<?= warga_asset_url('assets/pwa/icon-192.png') ?>">
    <link rel="apple-touch-icon" href="<?= warga_asset_url('assets/pwa/icon-180.png') ?>">
</head>
<body class="theme-light warga-auth-body" data-base-url="<?= e(base_url()) ?>">
<?php $this->load->view('layouts/page_skeleton'); ?>
<div id="page">
    <header class="header header-fixed header-logo-center"><a href="<?= site_url('login') ?>" class="header-title"><?= e($resetBrand) ?></a><a href="<?= site_url('login') ?>" class="header-icon header-icon-4" aria-label="Kembali ke halaman masuk"><i class="fa fa-arrow-left" aria-hidden="true"></i></a></header>
    <nav id="footer-bar" class="footer-bar-6 warga-footer" aria-label="Navigasi utama">
        <a href="<?= site_url('dashboard') ?>"><i class="fa fa-home"></i><span>Beranda</span></a>
        <a href="<?= site_url('surat') ?>"><i class="fa fa-envelope"></i><span>Surat</span></a>
        <a class="circle-nav" href="<?= site_url('pasar') ?>"><i class="fa fa-store"></i><span>Pasar</span><strong aria-hidden="true"><u></u></strong></a>
        <a href="<?= site_url('pengumuman') ?>"><i class="fa fa-bullhorn"></i><span>Info</span></a>
        <a class="active-nav" href="<?= site_url('login') ?>"><i class="fa fa-sign-in-alt"></i><span>Login</span></a>
    </nav>
    <main class="page-content header-clear-medium warga-auth-page">
        <section class="warga-auth-brand compact">
            <img src="<?= warga_asset_url('assets/pwa/icon-192.png') ?>" alt="Logo Kabupaten Jayawijaya">
            <div><p>AKUN LAYANAN WARGA</p><h1>Pulihkan Akun</h1><span><?= e($resetInstitution) ?> terhubung, layanan lebih dekat.</span></div>
        </section>
        <section class="card card-style warga-auth-card">
            <div class="content">
                <p class="font-600 color-highlight mb-n1"><?= $hasResetRequest ? 'Periksa email Anda' : 'Lupa kata sandi' ?></p>
                <h2 class="font-28 mb-2"><?= $hasResetRequest ? 'Masukkan kode' : 'Atur ulang kata sandi' ?></h2>
                <p class="mb-4"><?= $hasResetRequest ? 'Gunakan kode 6 digit dari email akun Anda. Periksa Inbox, Spam, atau Promosi.' : 'Masukkan email yang terdaftar pada akun layanan warga.' ?></p>

                <?php if (!empty($notice)): ?>
                    <?php $this->load->view('layouts/flash_alert', array('flashType' => 'success', 'flashTitle' => 'Periksa email', 'flashMessage' => $notice)); ?>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <?php $this->load->view('layouts/flash_alert', array('flashType' => 'error', 'flashTitle' => 'Belum dapat diproses', 'flashMessage' => $error)); ?>
                <?php endif; ?>

                <?php if (!$hasResetRequest): ?>
                    <form method="post" action="<?= site_url('lupa-password/kirim') ?>" autocomplete="on" data-disable-submit>
                        <?= csrf_field() ?>
                        <div class="input-style no-borders has-icon mb-4"><i class="fa fa-envelope"></i><input type="email" class="form-control" id="reset-email" name="email" value="<?= e(old('email')) ?>" placeholder="Email akun" maxlength="180" required autocomplete="email" inputmode="email"><i class="fa fa-times disabled invalid color-red-dark" aria-hidden="true"></i><i class="fa fa-check disabled valid color-green-dark" aria-hidden="true"></i><label for="reset-email" class="color-highlight">Email akun</label><em>*</em></div>
                        <button class="btn btn-full btn-l font-600 bg-teal-dark color-white rounded-s" type="submit"><span>Kirim Kode</span><i class="fa fa-paper-plane ms-2" aria-hidden="true"></i></button>
                    </form>
                <?php else: ?>
                    <div class="warga-reset-destination"><i class="fa fa-envelope" aria-hidden="true"></i><span>Kode dikirim ke <strong><?= e((string) ($resetState['email_masked'] ?? 'email akun')) ?></strong></span></div>
                    <form method="post" action="<?= site_url('lupa-password/selesaikan') ?>" autocomplete="off" data-disable-submit>
                        <?= csrf_field() ?>
                        <div class="input-style no-borders has-icon mb-4"><i class="fa fa-key"></i><input type="text" class="form-control warga-reset-otp" id="reset-otp" name="otp" placeholder="Kode 6 digit" required minlength="6" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code"><i class="fa fa-times disabled invalid color-red-dark" aria-hidden="true"></i><i class="fa fa-check disabled valid color-green-dark" aria-hidden="true"></i><label for="reset-otp" class="color-highlight">Kode verifikasi</label><em>*</em></div>
                        <div class="input-style no-borders has-icon validate-field mb-4 warga-auth-password-field"><i class="fa fa-lock"></i><input type="password" class="form-control" id="reset-password" name="new_password" placeholder="Kata sandi baru" required minlength="8" maxlength="72" autocomplete="new-password"><i class="fa fa-times disabled invalid color-red-dark" aria-hidden="true"></i><i class="fa fa-check disabled valid color-green-dark" aria-hidden="true"></i><button type="button" class="warga-password-toggle" data-password-toggle aria-controls="reset-password" aria-pressed="false" aria-label="Tampilkan kata sandi"><i class="fa fa-eye" aria-hidden="true"></i></button><label for="reset-password" class="color-highlight">Kata sandi baru</label><em>*</em></div>
                        <div class="input-style no-borders has-icon validate-field mb-4 warga-auth-password-field"><i class="fa fa-check-circle"></i><input type="password" class="form-control" id="reset-confirm" name="password_confirm" placeholder="Ulangi kata sandi" required minlength="8" maxlength="72" autocomplete="new-password"><i class="fa fa-times disabled invalid color-red-dark" aria-hidden="true"></i><i class="fa fa-check disabled valid color-green-dark" aria-hidden="true"></i><button type="button" class="warga-password-toggle" data-password-toggle aria-controls="reset-confirm" aria-pressed="false" aria-label="Tampilkan kata sandi"><i class="fa fa-eye" aria-hidden="true"></i></button><label for="reset-confirm" class="color-highlight">Ulangi kata sandi</label><em>*</em></div>
                        <button class="btn btn-full btn-l font-600 bg-teal-dark color-white rounded-s" type="submit"><span>Simpan Kata Sandi</span><i class="fa fa-check ms-2" aria-hidden="true"></i></button>
                    </form>
                    <div class="warga-reset-actions">
                        <form method="post" action="<?= site_url('lupa-password/kirim-ulang') ?>" data-disable-submit><?= csrf_field() ?><button type="submit" class="warga-reset-link"><i class="fa fa-redo" aria-hidden="true"></i><span>Kirim ulang kode</span></button></form>
                        <form method="post" action="<?= site_url('lupa-password/ulang') ?>"><?= csrf_field() ?><button type="submit" class="warga-reset-link"><i class="fa fa-edit" aria-hidden="true"></i><span>Ganti email</span></button></form>
                    </div>
                <?php endif; ?>
                <p class="text-center mt-4 mb-0"><a class="color-highlight font-600" href="<?= site_url('login') ?>"><i class="fa fa-arrow-left me-2" aria-hidden="true"></i>Kembali ke halaman masuk</a></p>
            </div>
        </section>
        <div class="card card-style warga-auth-install"><?php $this->load->view('layouts/pwa_install', array('branding' => $branding)); ?></div>
        <?php $this->load->view('layouts/site_footer', array('footerVillage' => $footerVillage, 'currentUser' => $currentUser, 'shareTitle' => $shareTitle, 'shareDescription' => $shareDescription, 'shareUrl' => $shareUrl, 'branding' => $branding)); ?>
    </main>
</div>
<script>window.SDW={baseUrl:<?= json_encode(base_url()) ?>,brandName:<?= json_encode($resetBrand) ?>,serviceWorkerUrl:<?= json_encode(warga_asset_url('service-worker.js')) ?>,serviceWorkerScope:<?= json_encode(base_url()) ?>};</script>
<script src="<?= warga_asset_url('assets/js/warga.min.js') ?>"></script>
<script>window.SDW.footerActionsUrl=<?= json_encode(warga_asset_url('assets/js/footer-actions.js')) ?>;</script>
<script src="<?= warga_asset_url('assets/js/footer-actions-loader.min.js') ?>"></script>
</body>
</html>
