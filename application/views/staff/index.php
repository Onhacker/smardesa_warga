<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="warga-staff-head">
    <div>
        <p><?= e(strtoupper($currentUser['role_name'])) ?></p>
        <h1>Pelayanan Surat</h1>
        <span><i class="fa fa-map-marker-alt"></i> <?= e($currentUser['village_name']) ?></span>
    </div>
    <span class="warga-intro-icon"><i class="fa fa-clipboard-check"></i></span>
</section>

<section class="warga-staff-summary" aria-label="Ringkasan pelayanan">
    <a href="<?= site_url('petugas') ?>" class="<?= $selectedStatus === '' ? 'is-active' : '' ?>"><strong><?= number_format($summary['total']) ?></strong><span>Total</span></a>
    <a href="<?= site_url('petugas?status=submitted') ?>" class="<?= $selectedStatus === 'submitted' ? 'is-active' : '' ?>"><strong><?= number_format($summary['verification']) ?></strong><span>Verifikasi</span></a>
    <a href="<?= site_url('petugas?status=verified') ?>" class="<?= $selectedStatus === 'verified' ? 'is-active' : '' ?>"><strong><?= number_format($summary['approval']) ?></strong><span>Persetujuan</span></a>
    <a href="<?= site_url('petugas?status=issued') ?>" class="<?= $selectedStatus === 'issued' ? 'is-active' : '' ?>"><strong><?= number_format($summary['issued']) ?></strong><span>Terbit</span></a>
</section>

<section class="content warga-section-head mt-3">
    <div>
        <p class="font-600 color-highlight mb-n1">ANTREAN DESA</p>
        <h2 class="font-22 mb-0"><?= $selectedStatus !== '' ? e(warga_status_text($selectedStatus)) : 'Semua Permohonan' ?></h2>
    </div>
    <span class="warga-result-count"><?= number_format(count($requests)) ?> data</span>
</section>

<section class="warga-staff-list">
    <?php if (!$requests): ?>
        <div class="warga-empty-state"><span><i class="fa fa-check-circle"></i></span><h3>Antrean ini sudah kosong</h3><p class="mb-0">Permohonan baru akan muncul otomatis.</p></div>
    <?php endif; ?>
    <?php foreach ($requests as $request): ?>
        <a href="<?= site_url('petugas/permohonan/' . rawurlencode($request['id'])) ?>" class="warga-staff-request">
            <span class="warga-request-icon"><i class="fa <?= e($request['service_icon']) ?>"></i></span>
            <span class="warga-staff-request-copy">
                <strong><?= e($request['citizen_name']) ?></strong>
                <b><?= e($request['service_name']) ?></b>
                <small><?= e($request['request_code']) ?> · <?= e(tanggal_id($request['submitted_at'], TRUE)) ?></small>
            </span>
            <span class="warga-request-status"><?= warga_status_label($request['status']) ?><i class="fa fa-chevron-right"></i></span>
        </a>
    <?php endforeach; ?>
</section>

<section class="warga-home-notice">
    <i class="fa fa-shield-alt"></i>
    <div><strong>Data sesuai wilayah kerja</strong><p>Petugas hanya dapat melihat dan memproses permohonan pada desa yang terhubung dengan akunnya.</p></div>
</section>
