<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="warga-page-intro">
    <div><p>LAYANAN WARGA</p><h1>Permohonan Saya</h1><span>Riwayat pengajuan dan status pelayanan <?= e($institutionLower) ?>.</span></div>
    <a href="<?= site_url('layanan') ?>" class="warga-intro-action" aria-label="Pilih jenis surat"><i class="fa fa-plus"></i></a>
</section>

<div class="warga-paged-list" data-paged-list>
    <?php $this->load->view('layouts/list_filters', array('listKind' => 'requests', 'dateLabel' => 'Tanggal pengajuan')); ?>
    <div data-list-results id="warga-list-results" aria-busy="false">
        <?php $this->load->view('permohonan/results'); ?>
    </div>
</div>
