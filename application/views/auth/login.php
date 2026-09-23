<?php defined('BASEPATH') OR exit('No direct script access allowed');
$branding = isset($branding) && is_array($branding) ? $branding : array();
$loginBrand = trim((string) ($branding['nama_sistem'] ?? 'SIDAPULIK')) ?: 'SIDAPULIK';
$loginTagline = trim((string) ($branding['tagline'] ?? 'Layanan Digital Warga')) ?: 'Layanan Digital Warga';
?>
<!DOCTYPE HTML>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="theme-color" content="#235fa4">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= e($loginBrand) ?>">
    <title><?= e($pageTitle) ?></title>
    <?php
    $shareTitle = $loginBrand . ' ' . trim((string) ($footerVillage['name'] ?? 'Jayawijaya')) . ' — ' . $loginTagline;
    $shareDescription = 'Akses layanan surat, info, pengaduan, pemberitahuan, dan Pasar Dapulik dalam satu aplikasi.';
    $shareUrl = base_url();
    $shareImage = warga_asset_url('assets/pwa/share-preview.png');
    $this->load->view('layouts/social_meta', array(
        'shareTitle' => $shareTitle,
        'shareDescription' => $shareDescription,
        'shareUrl' => $shareUrl,
        'shareImage' => $shareImage,
        'shareImageAlt' => $loginBrand . ', ' . strtolower($loginTagline),
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
    <link rel="stylesheet" href="<?= warga_asset_url('assets/css/passkey.css') ?>">
    <link rel="manifest" href="<?= site_url('manifest') ?>">
    <link rel="icon" href="<?= warga_asset_url('assets/pwa/icon-192.png') ?>">
    <link rel="apple-touch-icon" href="<?= warga_asset_url('assets/pwa/icon-180.png') ?>">
</head>
<body class="theme-light warga-auth-body" data-base-url="<?= e(base_url()) ?>">
<?php $this->load->view('layouts/page_skeleton'); ?>
<div id="page">
    <header class="header header-fixed header-logo-center"><a href="<?= site_url('login') ?>" class="header-title"><?= e($loginBrand) ?></a><a href="<?= site_url('login') ?>" class="header-icon header-icon-4 warga-header-notification" aria-label="Masuk untuk melihat pemberitahuan"><i class="fas fa-bell" aria-hidden="true"></i><span class="badge bg-red-dark" data-notification-count hidden></span></a></header>
    <nav id="footer-bar" class="footer-bar-6 warga-footer" aria-label="Navigasi utama">
        <a href="<?= site_url('dashboard') ?>"><i class="fa fa-home"></i><span>Beranda</span></a>
        <a href="<?= site_url('surat') ?>"><i class="fa fa-envelope"></i><span>Surat</span></a>
        <a class="circle-nav" href="<?= site_url('pasar') ?>"><i class="fa fa-store"></i><span>Pasar</span><strong aria-hidden="true"><u></u></strong></a>
        <a href="<?= site_url('pengumuman') ?>"><i class="fa fa-bullhorn"></i><span>Info</span></a>
        <a class="active-nav" href="<?= site_url('login') ?>"><i class="fa fa-sign-in-alt"></i><span>Login</span></a>
    </nav>
    <main class="page-content header-clear-medium warga-auth-page">
        <section class="warga-auth-brand warga-auth-login-brand">
            <img src="<?= warga_asset_url('assets/pwa/icon-192.png') ?>" alt="Logo <?= e($loginBrand) ?>">
            <div><p>LAYANAN DIGITAL WARGA</p><h1><?= e($loginBrand) ?></h1><span><?= e($loginTagline) ?></span></div>
        </section>
        <section class="card card-style warga-auth-card">
            <div class="content">
                <p class="font-600 color-highlight mb-n1">Selamat datang</p>
                <h2 class="font-28 mb-2">Masuk</h2>
                <p class="mb-4">Gunakan akun warga yang telah terdaftar.</p>
                <?php $loginSuccess = $this->session->flashdata('success'); ?>
                <?php if ($loginSuccess): ?>
                    <?php $this->load->view('layouts/flash_alert', array('flashType' => 'success', 'flashTitle' => $this->session->flashdata('success_title') ?: 'Pendaftaran berhasil', 'flashMessage' => $loginSuccess)); ?>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <?php $this->load->view('layouts/flash_alert', array('flashType' => 'error', 'flashTitle' => 'Tidak dapat masuk', 'flashMessage' => $error)); ?>
                <?php elseif (validation_errors()): ?>
                    <?php $this->load->view('layouts/flash_alert', array('flashType' => 'error', 'flashTitle' => 'Periksa data Anda', 'flashMessage' => validation_errors('<span class="warga-flash-line">', '</span>'), 'flashAllowHtml' => TRUE)); ?>
                <?php endif; ?>
                <form method="post" action="<?= site_url('login') ?>" autocomplete="on" data-disable-submit>
                    <?= csrf_field() ?>
                    <div class="input-style no-borders has-icon mb-4"><i class="fa fa-user"></i><input type="text" class="form-control" id="login-identity" name="identity" value="<?= e(old('identity', $demoMode ? 'warga' : '')) ?>" placeholder="Email atau Nomor Telepon" required autocomplete="username"><i class="fa fa-times disabled invalid color-red-dark" aria-hidden="true"></i><i class="fa fa-check disabled valid color-green-dark" aria-hidden="true"></i><label for="login-identity" class="color-highlight">Email atau Nomor Telepon</label><em>*</em></div>
                    <div class="input-style no-borders has-icon validate-field mb-4 warga-auth-password-field"><i class="fa fa-lock"></i><input type="password" class="form-control" id="login-password" name="password" value="<?= $demoMode ? 'demo12345' : '' ?>" placeholder="Kata Sandi" required autocomplete="current-password"><i class="fa fa-times disabled invalid color-red-dark" aria-hidden="true"></i><i class="fa fa-check disabled valid color-green-dark" aria-hidden="true"></i><button type="button" class="warga-password-toggle" data-password-toggle aria-controls="login-password" aria-pressed="false" aria-label="Tampilkan kata sandi"><i class="fa fa-eye" aria-hidden="true"></i></button><label for="login-password" class="color-highlight">Kata Sandi</label><em>*</em></div>
                    <div class="warga-auth-forgot"><a href="<?= site_url('lupa-password') ?>">Lupa kata sandi?</a></div>
                    <button class="btn btn-full btn-l font-600 bg-teal-dark color-white rounded-s" type="submit"><span>Masuk</span><i class="fa fa-arrow-right ms-2"></i></button>
                </form>
                <button type="button" class="btn btn-full btn-m rounded-s warga-passkey-login" data-passkey-login hidden><i class="fa fa-user-shield" aria-hidden="true"></i><span>Masuk dengan sidik jari/wajah</span></button>
                <button type="button" class="btn btn-full btn-m rounded-s warga-pin-login-toggle" data-pin-login-toggle aria-expanded="false" aria-controls="warga-pin-login-modal"><i class="fa fa-key" aria-hidden="true"></i><span>Masuk dengan PIN</span></button>
                <div class="warga-login-auth-message" data-login-auth-message role="status" aria-live="polite" hidden></div>
                <?php if ($demoMode): ?><div class="warga-demo-credentials"><i class="fa fa-flask"></i><span>Demo: <strong>warga</strong>, <strong>sekdes</strong>, atau <strong>kades</strong> · sandi <strong>demo12345</strong></span></div><?php endif; ?>
                <p class="text-center mt-4 mb-0">Belum memiliki akun? <a class="warga-auth-switch-link" href="<?= site_url('register') ?>">Daftar Warga</a></p>
            </div>
        </section>
        <div class="card card-style warga-auth-install" data-pwa-install-container aria-hidden="false"><?php $this->load->view('layouts/pwa_install', array('branding' => $branding)); ?></div>
        <?php $this->load->view('layouts/site_footer', array('footerVillage' => $footerVillage, 'currentUser' => $currentUser, 'shareTitle' => $shareTitle, 'shareDescription' => $shareDescription, 'shareUrl' => $shareUrl, 'branding' => $branding)); ?>
    </main>
</div>
<div class="warga-pin-login-modal" id="warga-pin-login-modal" data-pin-login-panel hidden aria-hidden="true">
    <button type="button" class="warga-pin-login-backdrop" data-pin-login-close tabindex="-1" aria-label="Tutup login dengan PIN"></button>
    <section class="warga-pin-login-dialog" role="dialog" aria-modal="true" aria-labelledby="warga-pin-login-title" aria-describedby="warga-pin-login-description">
        <header class="warga-pin-login-header">
            <span class="warga-pin-login-icon" aria-hidden="true"><i class="fa fa-key"></i></span>
            <div>
                <p>Login cepat</p>
                <h2 id="warga-pin-login-title">Masuk dengan PIN</h2>
            </div>
            <button type="button" class="warga-pin-login-close" data-pin-login-close aria-label="Tutup"><i class="fa fa-times" aria-hidden="true"></i></button>
        </header>
        <p class="warga-pin-login-description" id="warga-pin-login-description">Masukkan PIN 6 angka. Email tidak diperlukan pada perangkat yang sudah terhubung.</p>
        <form data-pin-login-form autocomplete="off">
            <label for="login-pin">PIN 6 angka</label>
            <div class="warga-pin-code" data-pin-code>
                <div class="warga-pin-code-boxes" aria-hidden="true">
                    <?php for ($pinDigit = 0; $pinDigit < 6; $pinDigit++): ?><span class="warga-pin-digit-box" data-pin-digit-box></span><?php endfor; ?>
                </div>
                <input type="password" id="login-pin" name="pin" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" aria-label="PIN 6 angka" aria-describedby="warga-pin-login-description warga-pin-login-error" data-pin-input required>
            </div>
            <p class="warga-pin-login-error" id="warga-pin-login-error" data-pin-login-error role="alert" hidden></p>
            <button type="submit" class="btn btn-full btn-s warga-pin-login-submit"><i class="fa fa-lock" aria-hidden="true"></i><span>Masuk dengan PIN</span></button>
        </form>
    </section>
</div>
<script>window.SDW={baseUrl:<?= json_encode(base_url()) ?>,brandName:<?= json_encode($loginBrand) ?>,csrfName:<?= json_encode($this->security->get_csrf_token_name()) ?>,csrfHash:<?= json_encode($this->security->get_csrf_hash()) ?>,serviceWorkerUrl:<?= json_encode(warga_asset_url('service-worker.js')) ?>,serviceWorkerScope:<?= json_encode(base_url()) ?>,passkeyEndpoints:<?= json_encode(array('loginOptions'=>site_url('passkey/login/options'),'login'=>site_url('passkey/login'),'pinLogin'=>site_url('passkey/pin/login'))) ?>};</script>
<script src="<?= warga_asset_url('assets/js/warga.min.js') ?>"></script>
<script src="<?= warga_asset_url('assets/js/passkey.js') ?>"></script>
<script>window.SDW.footerActionsUrl=<?= json_encode(warga_asset_url('assets/js/footer-actions.js')) ?>;</script>
<script src="<?= warga_asset_url('assets/js/footer-actions-loader.min.js') ?>"></script>
</body>
</html>
