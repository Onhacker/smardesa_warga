<?php defined('BASEPATH') OR exit('No direct script access allowed');
$showBackButton = !empty($showBackButton);
$backUrl = isset($backUrl) && trim((string) $backUrl) !== ''
    ? (string) $backUrl
    : site_url(!empty($staffMode) ? 'petugas' : 'permohonan');
$themeHeaderClass = $showBackButton ? 'header-icon-3' : 'header-icon-4';
$navSection = $this->uri->segment(1) ?: 'dashboard';
?>
<!DOCTYPE HTML>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#235fa4">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SmartDesa Warga">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="SmartDesa Warga">
    <title><?= e($pageTitle) ?> | SmartDesa Warga</title>
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/v22/styles/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/v22/fonts/css/fontawesome-all.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/vendor/tabler-icons/tabler-warga.min.css') ?>?v=1">
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/simp-v22.min.css') ?>?v=1">
    <link rel="stylesheet" type="text/css" href="<?= base_url('assets/css/warga.min.css') ?>?v=102">
    <link rel="stylesheet" href="<?= base_url('assets/css/community.min.css') ?>?v=18">
    <link rel="stylesheet" href="<?= base_url('assets/css/market.css') ?>?v=18">
    <style id="warga-letters-icon-override">
        body #page .page-content .warga-letters-head .warga-intro-icon,
        body #page .page-content .warga-letters-head .warga-intro-icon > i {
            color: #fff !important;
        }
    </style>
    <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('assets/pwa/icon-192.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/pwa/icon-180.png') ?>">
</head>
<body class="theme-light" data-highlight="highlight-teal" data-base-url="<?= e(base_url()) ?>" data-csrf-name="<?= e($this->security->get_csrf_token_name()) ?>" data-csrf-hash="<?= e($this->security->get_csrf_hash()) ?>">
<?php $this->load->view('layouts/page_skeleton'); ?>
<div id="page">
    <header class="header header-fixed header-logo-center header-auto-show">
        <?php if ($showBackButton): ?>
            <a href="<?= e($backUrl) ?>" class="header-icon header-icon-1" aria-label="Kembali"><i class="fas fa-chevron-left"></i></a>
        <?php else: ?>
            <a href="#" data-menu="menu-main" class="header-icon header-icon-1" aria-label="Buka menu"><i class="fas fa-bars"></i></a>
        <?php endif; ?>
        <a href="<?= site_url(warga_home_route($currentUser)) ?>" class="header-title"><?= e($pageTitle) ?></a>
        <?php if ($showBackButton): ?><a href="#" data-menu="menu-main" class="header-icon header-icon-4" aria-label="Buka menu"><i class="fas fa-bars"></i></a><?php endif; ?>
        <a href="#" data-toggle-theme class="header-icon <?= $themeHeaderClass ?> show-on-theme-dark" aria-label="Gunakan mode terang"><i class="fas fa-sun"></i></a>
        <a href="#" data-toggle-theme class="header-icon <?= $themeHeaderClass ?> show-on-theme-light" aria-label="Gunakan mode gelap"><i class="fas fa-moon"></i></a>
    </header>

    <?php $footerIsAuthenticated = !empty($isAuthenticated) && is_array($currentUser); ?>
    <nav id="footer-bar" class="footer-bar-6 warga-footer <?= $staffMode ? 'is-staff' : '' ?>" aria-label="Navigasi utama">
        <a class="<?= $navSection === 'dashboard' || ($staffMode && $navSection === 'petugas' && !$this->input->get('status')) ? 'active-nav' : '' ?>" href="<?= site_url($staffMode ? 'petugas' : 'dashboard') ?>"><i class="fa fa-home"></i><span>Beranda</span></a>
        <a class="<?= in_array($navSection, array('surat','permohonan','layanan','notifikasi')) || ($staffMode && $navSection === 'petugas' && $this->input->get('status')) ? 'active-nav' : '' ?>" href="<?= site_url($staffMode ? 'petugas?status=submitted' : 'surat') ?>"><i class="fa fa-envelope"></i><span>Surat</span></a>
        <a class="circle-nav <?= in_array($navSection, array('pasar', 'pasar-digital', 'marketplace', 'tokoku'), TRUE) ? 'active-nav' : '' ?>" href="<?= site_url('pasar') ?>"><i class="fa fa-store"></i><span>Pasar</span></a>
        <a class="<?= $navSection === 'pengumuman' ? 'active-nav' : '' ?>" href="<?= site_url('pengumuman') ?>"><i class="fa fa-bullhorn"></i><span>Pengumuman</span></a>
        <a class="<?= $footerIsAuthenticated && in_array($navSection,array('akun','kontak')) ? 'active-nav' : '' ?>" href="<?= site_url($footerIsAuthenticated ? 'akun' : 'login') ?>"><i class="fa fa-<?= $footerIsAuthenticated ? 'user' : 'sign-in-alt' ?>"></i><span><?= $footerIsAuthenticated ? 'Akun' : 'Login' ?></span></a>
    </nav>

    <section class="page-title page-title-fixed warga-page-title<?= $showBackButton ? ' has-back' : '' ?>" aria-label="Judul halaman">
        <?php if ($showBackButton): ?><a href="<?= e($backUrl) ?>" class="page-title-icon shadow-xl bg-theme color-theme warga-page-title-back" aria-label="Kembali"><i class="fa fa-arrow-left"></i></a><?php endif; ?>
        <h1><?= e($pageTitle) ?></h1>
        <a href="#" data-toggle-theme class="page-title-icon shadow-xl bg-theme color-theme show-on-theme-dark" aria-label="Gunakan mode terang"><i class="fa fa-sun"></i></a>
        <a href="#" data-toggle-theme class="page-title-icon shadow-xl bg-theme color-theme show-on-theme-light" aria-label="Gunakan mode gelap"><i class="fa fa-moon"></i></a>
        <a href="#" data-menu="menu-main" class="page-title-icon shadow-xl bg-theme color-theme" aria-label="Buka menu"><i class="fa fa-bars"></i></a>
    </section>
    <div class="page-title-clear" aria-hidden="true"></div>

    <main class="page-content">
        <?php $flashSuccess = $this->session->flashdata('success'); ?>
        <?php $flashError = $this->session->flashdata('error'); ?>
        <?php if ($flashSuccess): ?>
            <?php $this->load->view('layouts/flash_alert', array('flashType' => 'success', 'flashTitle' => 'Berhasil', 'flashMessage' => $flashSuccess)); ?>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <?php $this->load->view('layouts/flash_alert', array('flashType' => 'error', 'flashTitle' => 'Perlu diperbaiki', 'flashMessage' => $flashError)); ?>
        <?php endif; ?>
        <?php $this->load->view($contentView); ?>
        <?php $this->load->view('layouts/site_footer', array('footerVillage' => $footerVillage, 'currentUser' => $currentUser)); ?>
    </main>

    <aside id="menu-main" class="menu menu-box-left rounded-0" data-menu-width="300">
        <?php $this->load->view('layouts/menu', array('currentUser' => $currentUser, 'staffMode' => $staffMode, 'institutionLabel' => $institutionLabel, 'canManageMarketplace' => !empty($canManageMarketplace))); ?>
    </aside>
    <div class="menu-hider"></div>
</div>
<script>window.SDW={baseUrl:<?= json_encode(base_url()) ?>,csrfName:<?= json_encode($this->security->get_csrf_token_name()) ?>,csrfHash:<?= json_encode($this->security->get_csrf_hash()) ?>,isAuthenticated:<?= !empty($isAuthenticated) ? 'true' : 'false' ?>,serviceWorkerUrl:<?= json_encode(base_url('service-worker.js') . '?v=60') ?>,serviceWorkerScope:<?= json_encode(base_url()) ?>};</script>
<script>window.SDW.vapidPublicKey=<?= json_encode(trim((string)getenv('WARGA_VAPID_PUBLIC_KEY'))) ?>;</script>
<script src="<?= base_url('assets/v22/scripts/bootstrap.min.js') ?>"></script>
<script src="<?= base_url('assets/v22/scripts/custom.min.js') ?>?v=3"></script>
<script src="<?= base_url('assets/js/warga.min.js') ?>?v=19"></script>
<script src="<?= base_url('assets/js/community.min.js') ?>?v=10"></script>
<script src="<?= base_url('assets/js/market.js') ?>?v=8"></script>
</body>
</html>
