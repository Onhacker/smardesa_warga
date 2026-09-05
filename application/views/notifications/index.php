<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="warga-page-intro"><div><p>PEMBARUAN LAYANAN</p><h1>Notifikasi</h1><span>Status terbaru permohonan Anda.</span></div><span class="warga-intro-icon"><i class="fa fa-bell"></i></span></section>
<div class="warga-paged-list" data-paged-list>
    <form class="warga-community" method="post" action="<?= site_url('notifikasi/baca') ?>"><?= csrf_field() ?><button class="community-button is-secondary"><i class="fa fa-check-double"></i> Tandai Semua Dibaca</button></form>
    <?php $this->load->view('layouts/list_filters', array('listKind' => 'notifications', 'dateLabel' => 'Tanggal pembaruan')); ?>
    <div data-list-results id="warga-list-results" aria-busy="false">
        <?php $this->load->view('notifications/results'); ?>
    </div>
</div>
