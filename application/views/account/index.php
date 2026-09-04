<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-account-page">
    <section class="warga-account-head" aria-labelledby="warga-account-name">
        <span class="warga-account-avatar" aria-hidden="true"><?= e(warga_initials($currentUser['name'])) ?></span>
        <div>
            <p><?= e(strtoupper($currentUser['role_name'])) ?></p>
            <h1 id="warga-account-name"><?= e($currentUser['name']) ?></h1>
            <span class="warga-account-village"><i class="fa fa-map-marker-alt" aria-hidden="true"></i><?= e($currentUser['village_name']) ?></span>
        </div>
    </section>

    <section class="card card-style warga-account-card" aria-labelledby="warga-account-info-title">
        <div class="content">
            <header class="warga-account-card-title">
                <div>
                    <p>INFORMASI AKUN</p>
                    <h2 id="warga-account-info-title">Data Akun</h2>
                </div>
                <span aria-hidden="true"><i class="fa fa-id-card"></i></span>
            </header>

            <div class="warga-account-row">
                <span class="warga-account-row-icon is-phone" aria-hidden="true"><i class="fa fa-phone"></i></span>
                <div><span>Nomor telepon</span><strong><?= e($currentUser['phone'] ?: '-') ?></strong></div>
            </div>
            <div class="warga-account-row">
                <span class="warga-account-row-icon is-email" aria-hidden="true"><i class="fa fa-envelope"></i></span>
                <div><span>Email</span><strong><?= e($currentUser['email'] ?: '-') ?></strong></div>
            </div>
            <div class="warga-account-row">
                <span class="warga-account-row-icon is-location" aria-hidden="true"><i class="fa fa-map-marker-alt"></i></span>
                <div>
                    <span>Wilayah layanan</span>
                    <strong><?= e($currentUser['village_name'] . ', ' . $currentUser['district_name']) ?></strong>
                    <small><?= e($currentUser['regency_name']) ?></small>
                </div>
            </div>
        </div>
    </section>

    <section class="card card-style warga-settings-list" aria-labelledby="warga-account-settings-title">
        <div class="content mb-0">
            <header class="warga-account-card-title">
                <div>
                    <p>PREFERENSI</p>
                    <h2 id="warga-account-settings-title">Pengaturan Aplikasi</h2>
                </div>
                <span aria-hidden="true"><i class="fa fa-sliders-h"></i></span>
            </header>

            <a href="#" data-toggle-theme>
                <span class="warga-setting-icon is-dark"><i class="fa fa-moon" aria-hidden="true"></i></span>
                <div><strong>Mode Tampilan</strong><small>Gunakan tema terang atau gelap</small></div>
                <i class="fa fa-chevron-right" aria-hidden="true"></i>
            </a>
            <?php if (!$staffMode): ?>
                <a href="<?= site_url('notifikasi') ?>">
                    <span class="warga-setting-icon is-red"><i class="fa fa-bell" aria-hidden="true"></i></span>
                    <div><strong>Notifikasi</strong><small>Lihat pembaruan status layanan</small></div>
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                </a>
            <?php endif; ?>
        </div>
    </section>

    <section class="card card-style warga-install-card"><div class="content"><?php $this->load->view('layouts/pwa_install'); ?></div></section>

    <form method="post" action="<?= site_url('logout') ?>" class="content warga-account-logout" data-logout-form>
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-full btn-m border-red-dark color-red-dark rounded-s font-600"><i class="fa fa-sign-out-alt me-2" aria-hidden="true"></i>Keluar dari Akun</button>
    </form>
</div>
