<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="warga-page-intro"><div><p>PEMBARUAN LAYANAN</p><h1>Notifikasi</h1><span>Status terbaru permohonan Anda.</span></div><span class="warga-intro-icon"><i class="fa fa-bell"></i></span></section>
<div class="warga-paged-list" data-paged-list>
    <?php $this->load->view('layouts/list_filters', array('listKind' => 'notifications', 'dateLabel' => 'Tanggal pembaruan')); ?>
    <div data-list-results id="warga-list-results" aria-busy="false">
        <?php $this->load->view('notifications/results'); ?>
    </div>
</div>
