<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="warga-page-intro">
    <div><p>LAYANAN WARGA</p><h1>Permohonan Saya</h1><span>Riwayat pengajuan dan status pelayanan desa.</span></div>
    <a href="<?= site_url('permohonan/baru') ?>" class="warga-intro-action" aria-label="Buat permohonan"><i class="fa fa-plus"></i></a>
</section>

<div class="content mb-2">
    <div class="warga-filter-tabs" role="tablist" aria-label="Filter permohonan">
        <button type="button" class="active" data-request-filter="all">Semua</button>
        <button type="button" data-request-filter="active">Diproses</button>
        <button type="button" data-request-filter="issued">Selesai</button>
    </div>
</div>

<section class="warga-request-list" data-request-list>
    <?php if (!$requests): ?><div class="warga-empty-state"><span><i class="fa fa-inbox"></i></span><h3>Belum ada permohonan</h3><a href="<?= site_url('permohonan/baru') ?>" class="btn btn-s bg-teal-dark color-white rounded-s">Ajukan Surat</a></div><?php endif; ?>
    <?php foreach ($requests as $request): ?>
        <?php $filterGroup = $request['status'] === 'issued' ? 'issued' : (in_array($request['status'], array('submitted', 'verified', 'approved', 'syncing', 'revision'), TRUE) ? 'active' : 'other'); ?>
        <a href="<?= site_url('permohonan/' . rawurlencode($request['id'])) ?>" class="warga-request-card" data-request-item data-filter-group="<?= e($filterGroup) ?>">
            <span class="warga-request-icon"><i class="fa <?= e($request['service_icon']) ?>"></i></span>
            <span class="warga-request-copy"><strong><?= e($request['service_name']) ?></strong><small><?= e($request['request_code']) ?></small><small><?= e(tanggal_id($request['submitted_at'], TRUE)) ?></small></span>
            <span class="warga-request-status"><?= warga_status_label($request['status']) ?><i class="fa fa-chevron-right"></i></span>
        </a>
    <?php endforeach; ?>
    <div class="warga-empty-state d-none" data-filter-empty><span><i class="fa fa-search"></i></span><h3>Data tidak ditemukan</h3></div>
</section>
