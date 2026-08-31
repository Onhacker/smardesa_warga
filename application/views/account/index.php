<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="warga-account-head"><span class="warga-account-avatar"><?= e(warga_initials($currentUser['name'])) ?></span><div><p><?= e(strtoupper($currentUser['role_name'])) ?></p><h1><?= e($currentUser['name']) ?></h1><span><?= e($currentUser['village_name']) ?></span></div></section>

<section class="card card-style warga-account-card"><div class="content mb-1">
    <div class="warga-account-row"><i class="fa fa-phone"></i><div><span>Nomor telepon</span><strong><?= e($currentUser['phone'] ?: '-') ?></strong></div></div>
    <div class="warga-account-row"><i class="fa fa-envelope"></i><div><span>Email</span><strong><?= e($currentUser['email'] ?: '-') ?></strong></div></div>
    <div class="warga-account-row"><i class="fa fa-map-marker-alt"></i><div><span>Wilayah layanan</span><strong><?= e($currentUser['village_name'] . ', ' . $currentUser['district_name']) ?></strong><small><?= e($currentUser['regency_name']) ?></small></div></div>
</div></section>

<section class="card card-style warga-settings-list"><div class="content mb-0">
    <a href="#" data-toggle-theme><span class="warga-setting-icon is-dark"><i class="fa fa-moon"></i></span><div><strong>Mode Tampilan</strong><small>Terang atau gelap</small></div><i class="fa fa-chevron-right"></i></a>
    <?php if (!$staffMode): ?><a href="<?= site_url('notifikasi') ?>"><span class="warga-setting-icon is-red"><i class="fa fa-bell"></i></span><div><strong>Notifikasi</strong><small>Pembaruan status layanan</small></div><i class="fa fa-chevron-right"></i></a><?php endif; ?>
</div></section>

<section class="card card-style warga-install-card"><div class="content"><?php $this->load->view('layouts/pwa_install'); ?></div></section>

<form method="post" action="<?= site_url('logout') ?>" class="content" data-logout-form><?= csrf_field() ?><button type="submit" class="btn btn-full btn-m border-red-dark color-red-dark rounded-s font-600"><i class="fa fa-sign-out-alt me-2"></i>Keluar dari Akun</button></form>
