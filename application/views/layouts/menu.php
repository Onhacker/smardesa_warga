<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-menu-head">
    <button type="button" class="close-menu warga-menu-close" aria-label="Tutup menu"><i class="fa fa-times"></i></button>
    <img src="<?= base_url('assets/pwa/icon-192.png') ?>" alt="Logo SmartDesa">
    <div>
        <h2>SmartDesa Warga</h2>
        <p><?= e($currentUser['village_name']) ?></p>
    </div>
</div>

<h6 class="menu-divider mt-4"><?= $staffMode ? 'Pelayanan Desa' : 'Layanan' ?></h6>
<div class="list-group list-custom-small list-menu warga-menu-list">
    <?php if ($staffMode): ?>
        <a class="<?= nav_is('petugas') ? 'active-nav' : '' ?>" href="<?= site_url('petugas') ?>"><i class="fa fa-chart-pie bg-teal-dark color-white"></i><span>Ringkasan Layanan</span><i class="fa fa-angle-right"></i></a>
        <a href="<?= site_url('petugas?status=submitted') ?>"><i class="fa fa-clipboard-check bg-blue-dark color-white"></i><span>Antrean Verifikasi</span><i class="fa fa-angle-right"></i></a>
        <a href="<?= site_url('petugas?status=verified') ?>"><i class="fa fa-user-check bg-orange-dark color-white"></i><span>Menunggu Persetujuan</span><i class="fa fa-angle-right"></i></a>
        <a href="<?= site_url('petugas?status=issued') ?>"><i class="fa fa-file-pdf bg-green-dark color-white"></i><span>Surat Terbit</span><i class="fa fa-angle-right"></i></a>
    <?php else: ?>
        <a class="<?= nav_is('dashboard') ? 'active-nav' : '' ?>" href="<?= site_url('dashboard') ?>"><i class="fa fa-home bg-teal-dark color-white"></i><span>Beranda</span><i class="fa fa-angle-right"></i></a>
        <a class="<?= nav_is('permohonan') && $this->router->fetch_method() === 'create' ? 'active-nav' : '' ?>" href="<?= site_url('permohonan/baru') ?>"><i class="fa fa-plus bg-blue-dark color-white"></i><span>Permohonan Baru</span><i class="fa fa-angle-right"></i></a>
        <a class="<?= nav_is('permohonan') && $this->router->fetch_method() !== 'create' ? 'active-nav' : '' ?>" href="<?= site_url('permohonan') ?>"><i class="fa fa-file-alt bg-orange-dark color-white"></i><span>Riwayat Permohonan</span><i class="fa fa-angle-right"></i></a>
        <a class="<?= nav_is('notifications') ? 'active-nav' : '' ?>" href="<?= site_url('notifikasi') ?>"><i class="fa fa-bell bg-red-dark color-white"></i><span>Notifikasi</span><i class="fa fa-angle-right"></i></a>
    <?php endif; ?>
</div>

<h6 class="menu-divider mt-4">Akun</h6>
<div class="list-group list-custom-small list-menu warga-menu-list">
    <a class="<?= nav_is('account') ? 'active-nav' : '' ?>" href="<?= site_url('akun') ?>"><i class="fa fa-user bg-green-dark color-white"></i><span>Akun Saya</span><i class="fa fa-angle-right"></i></a>
    <a href="#" data-toggle-theme><i class="fa fa-moon bg-dark color-white"></i><span>Mode Gelap</span><div class="custom-control small-switch ios-switch"><input data-toggle-theme type="checkbox" class="ios-input" id="switch-dark-mode"><label class="custom-control-label" for="switch-dark-mode"></label></div></a>
    <form method="post" action="<?= site_url('logout') ?>" class="warga-logout-form" data-logout-form>
        <?= csrf_field() ?>
        <button type="submit"><i class="fa fa-sign-out-alt bg-red-dark color-white"></i><span>Keluar</span><i class="fa fa-angle-right"></i></button>
    </form>
</div>

<p class="warga-menu-user">Masuk sebagai <strong><?= e($currentUser['name']) ?></strong></p>
