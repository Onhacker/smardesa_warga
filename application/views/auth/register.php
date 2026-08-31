<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE HTML>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover"><meta name="theme-color" content="#167b78">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/v22/styles/bootstrap.min.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/v22/fonts/css/fontawesome-all.min.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/simp-v22.min.css') ?>?v=1"><link rel="stylesheet" href="<?= base_url('assets/css/warga.min.css') ?>?v=1"><link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
</head>
<body class="theme-light warga-auth-body" data-base-url="<?= e(base_url()) ?>">
<div id="preloader"><div class="spinner-border color-highlight" role="status"><span class="visually-hidden">Memuat</span></div></div><div id="page">
<header class="header header-fixed header-logo-center"><a href="<?= site_url('register') ?>" class="header-title">Daftar Warga</a><a href="<?= site_url('login') ?>" class="header-icon header-icon-1" aria-label="Kembali"><i class="fa fa-chevron-left"></i></a></header>
<main class="page-content header-clear-medium warga-auth-page">
    <section class="warga-auth-brand compact"><img src="<?= base_url('assets/pwa/icon-192.png') ?>" alt="Logo Kabupaten Jayawijaya"><div><p>AKUN LAYANAN WARGA</p><h1>Daftar Akun</h1><span>Satu akun untuk permohonan layanan desa.</span></div></section>
    <section class="card card-style warga-auth-card"><div class="content">
        <?php if (!empty($error) || validation_errors()): ?><div class="alert alert-small rounded-s bg-red-dark" role="alert"><span><i class="fa fa-times color-white"></i></span><strong class="color-white"><?= !empty($error) ? e($error) : validation_errors('<span class="d-block">', '</span>') ?></strong></div><?php endif; ?>
        <?php if ($demoMode): ?><div class="warga-demo-credentials mb-4"><i class="fa fa-info-circle"></i><span>Mode demo aktif. Data pendaftaran tidak disimpan permanen.</span></div><?php endif; ?>
        <form method="post" action="<?= site_url('register') ?>" data-disable-submit>
            <?= csrf_field() ?>
            <div class="input-style no-borders has-icon validate-field mb-4"><i class="fa fa-id-card"></i><input type="text" class="form-control" id="register-name" name="name" value="<?= e(old('name')) ?>" placeholder="Nama Lengkap" required maxlength="120"><label for="register-name" class="color-highlight">Nama Lengkap</label><em>*</em></div>
            <div class="input-style no-borders has-icon validate-field mb-4"><i class="fa fa-envelope"></i><input type="text" class="form-control" id="register-contact" name="contact" value="<?= e(old('contact')) ?>" placeholder="Email atau Nomor Telepon" required maxlength="160"><label for="register-contact" class="color-highlight">Email atau Nomor Telepon</label><em>*</em></div>
            <div class="input-style no-borders has-icon validate-field mb-4"><i class="fa fa-map-marker-alt"></i><input type="text" class="form-control text-uppercase" id="register-village" name="village_code" value="<?= e(old('village_code', $demoMode ? 'DEMO-ARABODA' : '')) ?>" placeholder="Kode Desa" required maxlength="30"><label for="register-village" class="color-highlight">Kode Desa</label><em>*</em></div>
            <div class="input-style no-borders has-icon validate-field mb-4"><i class="fa fa-lock"></i><input type="password" class="form-control" id="register-password" name="password" placeholder="Kata Sandi" required minlength="8" autocomplete="new-password"><label for="register-password" class="color-highlight">Kata Sandi</label><em>*</em></div>
            <div class="input-style no-borders has-icon validate-field mb-4"><i class="fa fa-check-circle"></i><input type="password" class="form-control" id="register-confirm" name="password_confirm" placeholder="Ulangi Kata Sandi" required minlength="8" autocomplete="new-password"><label for="register-confirm" class="color-highlight">Ulangi Kata Sandi</label><em>*</em></div>
            <button class="btn btn-full btn-l font-600 bg-teal-dark color-white rounded-s" type="submit"><span>Daftar Akun</span><i class="fa fa-arrow-right ms-2"></i></button>
        </form>
        <p class="text-center mt-4 mb-0">Sudah memiliki akun? <a class="color-highlight font-600" href="<?= site_url('login') ?>">Masuk</a></p>
    </div></section>
</main></div>
<script>window.SDW={baseUrl:<?= json_encode(base_url()) ?>,serviceWorkerUrl:<?= json_encode(base_url('service-worker.js')) ?>,serviceWorkerScope:<?= json_encode(base_url()) ?>};</script><script src="<?= base_url('assets/v22/scripts/bootstrap.min.js') ?>"></script><script src="<?= base_url('assets/v22/scripts/custom.min.js') ?>?v=1"></script><script src="<?= base_url('assets/js/warga.min.js') ?>?v=1"></script>
</body></html>
