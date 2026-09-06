<?php defined('BASEPATH') OR exit('No direct script access allowed');
$loginInstitution = trim((string) ($institutionLabel ?? ($footerVillage['institution'] ?? 'Kampung')));
if ($loginInstitution === '') $loginInstitution = 'Kampung';
$loginBrand = 'Smart ' . $loginInstitution;
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
    <link rel="stylesheet" href="<?= base_url('assets/v22/styles/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/v22/fonts/css/fontawesome-all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/simp-v22.min.css') ?>?v=1">
    <link rel="stylesheet" href="<?= base_url('assets/css/warga.min.css') ?>?v=98">
    <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
    <link rel="icon" href="<?= base_url('assets/pwa/icon-192.png') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/pwa/icon-180.png') ?>">
</head>
<body class="theme-light warga-auth-body" data-base-url="<?= e(base_url()) ?>">
<?php $this->load->view('layouts/page_skeleton'); ?>
<div id="page">
    <header class="header header-fixed header-logo-center"><a href="<?= site_url('login') ?>" class="header-title"><?= e($loginBrand) ?></a><a href="#" data-toggle-theme class="header-icon header-icon-4" aria-label="Ubah tema"><i class="fas fa-moon"></i></a></header>
    <nav id="footer-bar" class="footer-bar-6 warga-footer" aria-label="Navigasi utama">
        <a href="<?= site_url('dashboard') ?>"><i class="fa fa-home"></i><span>Beranda</span></a>
        <a href="<?= site_url('surat') ?>"><i class="fa fa-envelope"></i><span>Surat</span></a>
        <a class="circle-nav" href="<?= site_url('pasar') ?>"><i class="fa fa-store"></i><span>Pasar</span></a>
        <a href="<?= site_url('pengumuman') ?>"><i class="fa fa-bullhorn"></i><span>Pengumuman</span></a>
        <a class="active-nav" href="<?= site_url('login') ?>"><i class="fa fa-sign-in-alt"></i><span>Login</span></a>
    </nav>
    <main class="page-content header-clear-medium warga-auth-page">
        <section class="warga-auth-brand">
            <img src="<?= base_url('assets/pwa/icon-192.png') ?>" alt="Logo Kabupaten Jayawijaya">
            <div><p>LAYANAN DIGITAL WARGA</p><h1><?= e($loginBrand) ?></h1><span><?= e($loginInstitution) ?> terhubung, layanan lebih dekat.</span></div>
        </section>
        <section class="card card-style warga-auth-card">
            <div class="content">
                <p class="font-600 color-highlight mb-n1">Selamat datang</p>
                <h2 class="font-28 mb-2">Masuk</h2>
                <p class="mb-4">Gunakan akun warga yang telah terdaftar.</p>
                <?php $loginSuccess = $this->session->flashdata('success'); ?>
                <?php if ($loginSuccess): ?>
                    <?php $this->load->view('layouts/flash_alert', array('flashType' => 'success', 'flashTitle' => 'Pendaftaran berhasil', 'flashMessage' => $loginSuccess)); ?>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <?php $this->load->view('layouts/flash_alert', array('flashType' => 'error', 'flashTitle' => 'Tidak dapat masuk', 'flashMessage' => $error)); ?>
                <?php elseif (validation_errors()): ?>
                    <?php $this->load->view('layouts/flash_alert', array('flashType' => 'error', 'flashTitle' => 'Periksa data Anda', 'flashMessage' => validation_errors('<span class="warga-flash-line">', '</span>'), 'flashAllowHtml' => TRUE)); ?>
                <?php endif; ?>
                <form method="post" action="<?= site_url('login') ?>" autocomplete="on" data-disable-submit>
                    <?= csrf_field() ?>
                    <div class="input-style no-borders has-icon validate-field mb-4"><i class="fa fa-user"></i><input type="text" class="form-control" id="login-identity" name="identity" value="<?= e(old('identity', $demoMode ? 'warga' : '')) ?>" placeholder="Email atau Nomor Telepon" required autocomplete="username"><label for="login-identity" class="color-highlight">Email atau Nomor Telepon</label><em>*</em></div>
                    <div class="input-style no-borders has-icon validate-field mb-4"><i class="fa fa-lock"></i><input type="password" class="form-control" id="login-password" name="password" value="<?= $demoMode ? 'demo12345' : '' ?>" placeholder="Kata Sandi" required autocomplete="current-password"><label for="login-password" class="color-highlight">Kata Sandi</label><em>*</em></div>
                    <button class="btn btn-full btn-l font-600 bg-teal-dark color-white rounded-s" type="submit"><span>Masuk</span><i class="fa fa-arrow-right ms-2"></i></button>
                </form>
                <?php if ($demoMode): ?><div class="warga-demo-credentials"><i class="fa fa-flask"></i><span>Demo: <strong>warga</strong>, <strong>sekdes</strong>, atau <strong>kades</strong> · sandi <strong>demo12345</strong></span></div><?php endif; ?>
                <p class="text-center mt-4 mb-0">Belum memiliki akun? <a class="color-highlight font-600" href="<?= site_url('register') ?>">Daftar warga</a></p>
            </div>
        </section>
        <div class="card card-style warga-auth-install"><?php $this->load->view('layouts/pwa_install'); ?></div>
        <?php $this->load->view('layouts/site_footer', array('footerVillage' => $footerVillage, 'currentUser' => $currentUser)); ?>
    </main>
</div>
<script>window.SDW={baseUrl:<?= json_encode(base_url()) ?>,serviceWorkerUrl:<?= json_encode(base_url('service-worker.js') . '?v=56') ?>,serviceWorkerScope:<?= json_encode(base_url()) ?>};</script>
<script src="<?= base_url('assets/v22/scripts/bootstrap.min.js') ?>"></script><script src="<?= base_url('assets/v22/scripts/custom.min.js') ?>?v=3"></script><script src="<?= base_url('assets/js/warga.min.js') ?>?v=17"></script>
</body>
</html>
