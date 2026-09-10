<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-community community-v22-page community-v22-notification-page">
    <header class="community-heading community-v22-page-hero" aria-labelledby="notification-page-title">
        <div class="community-v22-page-hero-icon"><i class="fa fa-bell" aria-hidden="true"></i></div>
        <div>
            <p class="community-v22-eyebrow">Pembaruan layanan</p>
            <h1 id="notification-page-title">Pemberitahuan</h1>
            <p>Status terbaru permohonan Anda.</p>
        </div>
    </header>

    <section class="warga-notification-enable-card" data-push-enable-prompt hidden aria-labelledby="notification-enable-title">
        <span class="warga-notification-enable-icon" aria-hidden="true"><i class="fa fa-bell"></i></span>
        <div class="warga-notification-enable-copy">
            <h2 id="notification-enable-title">Nyalakan pemberitahuan</h2>
            <p>Terima pembaruan layanan langsung di perangkat Anda.</p>
            <span data-push-status role="status" aria-live="polite"></span>
        </div>
        <div class="custom-control small-switch ios-switch warga-notification-enable-switch">
            <input data-push-toggle type="checkbox" class="ios-input" id="switch-notification-page-push" aria-label="Nyalakan pemberitahuan">
            <label class="custom-control-label" for="switch-notification-page-push" aria-hidden="true"></label>
        </div>
    </section>

    <div class="warga-paged-list" data-paged-list>
    <form class="warga-notification-actions" method="post" action="<?= site_url('notifikasi/baca') ?>"><?= csrf_field() ?><button class="community-button is-secondary"><i class="fa fa-check-double"></i> Tandai Semua Dibaca</button></form>
    <?php $this->load->view('layouts/list_filters', array('listKind' => 'notifications', 'dateLabel' => 'Tanggal pembaruan')); ?>
    <div data-list-results id="warga-list-results" aria-busy="false">
        <?php $this->load->view('notifications/results'); ?>
    </div>
    </div>
</div>
