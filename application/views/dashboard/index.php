<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="warga-home-head">
    <div class="warga-home-identity">
        <span class="warga-avatar"><?= e(warga_initials($currentUser['name'])) ?></span>
        <div><p>Selamat datang</p><h1><?= e($currentUser['name']) ?></h1><span><i class="fa fa-map-marker-alt"></i> <?= e($currentUser['village_name']) ?></span></div>
    </div>
    <a href="<?= site_url('notifikasi') ?>" class="warga-head-action" aria-label="Buka notifikasi"><i class="fa fa-bell"></i></a>
</section>

<section class="warga-summary-band" aria-label="Ringkasan permohonan">
    <div><strong><?= number_format($summary['total']) ?></strong><span>Total</span></div>
    <div><strong><?= number_format($summary['active']) ?></strong><span>Diproses</span></div>
    <div><strong><?= number_format($summary['issued']) ?></strong><span>Selesai</span></div>
    <div><strong><?= number_format($summary['revision']) ?></strong><span>Perbaikan</span></div>
</section>

<section class="content warga-section-head">
    <div><p class="font-600 color-highlight mb-n1">Pelayanan desa</p><h2 class="font-22 mb-0">Ajukan Surat</h2></div>
    <a href="<?= site_url('permohonan/baru') ?>" class="font-12 color-highlight font-600">Semua layanan</a>
</section>
<section class="warga-service-grid" aria-label="Jenis layanan">
    <?php $serviceColors = array('teal', 'blue', 'orange', 'green', 'red'); ?>
    <?php foreach (array_slice($services, 0, 5) as $index => $service): ?>
        <a href="<?= site_url('permohonan/baru?layanan=' . rawurlencode($service['slug'])) ?>" class="warga-service-item">
            <span class="warga-service-icon is-<?= e($serviceColors[$index % count($serviceColors)]) ?>"><i class="fa <?= e($service['icon']) ?>"></i></span>
            <strong><?= e($service['short_name']) ?></strong>
        </a>
    <?php endforeach; ?>
</section>

<section class="content warga-section-head mt-4">
    <div><p class="font-600 color-highlight mb-n1">Aktivitas terbaru</p><h2 class="font-22 mb-0">Permohonan Saya</h2></div>
    <a href="<?= site_url('permohonan') ?>" class="font-12 color-highlight font-600">Lihat semua</a>
</section>
<section class="warga-request-list">
    <?php if (!$requests): ?>
        <div class="warga-empty-state"><span><i class="fa fa-file-alt"></i></span><h3>Belum ada permohonan</h3><a href="<?= site_url('permohonan/baru') ?>" class="btn btn-s bg-teal-dark color-white rounded-s">Ajukan Surat</a></div>
    <?php endif; ?>
    <?php foreach (array_slice($requests, 0, 3) as $request): ?>
        <a href="<?= site_url('permohonan/' . rawurlencode($request['id'])) ?>" class="warga-request-card">
            <span class="warga-request-icon"><i class="fa <?= e($request['service_icon']) ?>"></i></span>
            <span class="warga-request-copy"><strong><?= e($request['service_name']) ?></strong><small><?= e($request['request_code']) ?> · <?= e(tanggal_id($request['submitted_at'])) ?></small></span>
            <span class="warga-request-status"><?= warga_status_label($request['status']) ?><i class="fa fa-chevron-right"></i></span>
        </a>
    <?php endforeach; ?>
</section>

<section class="warga-home-notice">
    <i class="fa fa-sync-alt"></i><div><strong>Sinkronisasi desa</strong><p>Status permohonan diperbarui otomatis saat perangkat SmartDesa desa terhubung.</p></div>
</section>
