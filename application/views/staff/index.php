<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="warga-staff-head">
    <div>
        <p><?= e(strtoupper(warga_replace_institution($currentUser['role_name'], $institutionLabel))) ?></p>
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

<div class="warga-paged-list warga-staff-paged-list" data-paged-list>
    <p class="warga-list-feedback" data-list-feedback role="status" aria-live="polite" aria-atomic="true"></p>
    <div class="warga-list-error" data-list-error role="alert" hidden>
        <span data-list-error-message></span>
        <button type="button" data-list-retry>Coba lagi</button>
        <a href="<?= site_url('login') ?>" data-list-login hidden>Masuk kembali</a>
    </div>
    <div data-list-results id="warga-staff-list-results" aria-busy="false">
        <?php $this->load->view('staff/results'); ?>
    </div>
</div>

<section class="warga-home-notice">
    <i class="fa fa-shield-alt"></i>
    <div><strong>Data sesuai wilayah kerja</strong><p>Petugas hanya dapat melihat dan memproses permohonan pada <?= e($institutionLower) ?> yang terhubung dengan akunnya.</p></div>
</section>
