<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="content warga-section-head mt-3">
    <div>
        <p class="font-600 color-highlight mb-n1">ANTREAN <?= e($institutionUpper) ?></p>
        <h2 class="font-22 mb-0"><?= $selectedStatus !== '' ? e(warga_status_text($selectedStatus)) : 'Semua Permohonan' ?></h2>
    </div>
    <span class="warga-result-count"><?= number_format($listing['total']) ?> data</span>
</section>

<section class="warga-staff-list" aria-label="Daftar permohonan">
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

<?php $this->load->view('layouts/list_pagination'); ?>
