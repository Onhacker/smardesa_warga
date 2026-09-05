<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-community community-v22-page community-v22-notification-page">
    <header class="community-heading community-v22-page-hero" aria-labelledby="notification-page-title">
        <div class="community-v22-page-hero-icon"><i class="fa fa-bell" aria-hidden="true"></i></div>
        <div>
            <p class="community-v22-eyebrow">Pembaruan layanan</p>
            <h1 id="notification-page-title">Notifikasi</h1>
            <p>Status terbaru permohonan Anda.</p>
        </div>
    </header>

    <div class="warga-paged-list" data-paged-list>
    <form class="warga-community" method="post" action="<?= site_url('notifikasi/baca') ?>"><?= csrf_field() ?><button class="community-button is-secondary"><i class="fa fa-check-double"></i> Tandai Semua Dibaca</button></form>
    <?php $this->load->view('layouts/list_filters', array('listKind' => 'notifications', 'dateLabel' => 'Tanggal pembaruan')); ?>
    <div data-list-results id="warga-list-results" aria-busy="false">
        <?php $this->load->view('notifications/results'); ?>
    </div>
    </div>
</div>
