<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="warga-request-list" data-request-list aria-label="Daftar permohonan">
    <?php if (!$requests): ?>
        <?php $hasFilters = $listing['filters']['q'] !== '' || $listing['filters']['date'] !== '' || $listing['filters']['status'] !== 'all'; ?>
        <div class="warga-empty-state"><span><i class="fa <?= $hasFilters ? 'fa-search' : 'fa-inbox' ?>" aria-hidden="true"></i></span><h3><?= $hasFilters ? 'Permohonan tidak ditemukan' : 'Belum ada permohonan' ?></h3>
            <?php if ($hasFilters): ?><p>Coba nama surat, tanggal, atau status lainnya.</p><?php else: ?><a href="<?= site_url('permohonan/baru') ?>" class="btn btn-s bg-teal-dark color-white rounded-s">Ajukan Surat</a><?php endif; ?>
        </div>
    <?php endif; ?>
    <?php foreach ($requests as $request): ?>
        <a href="<?= site_url('permohonan/' . rawurlencode($request['id'])) ?>" class="warga-request-card">
            <span class="warga-request-icon"><i class="fa <?= e($request['service_icon']) ?>" aria-hidden="true"></i></span>
            <span class="warga-request-copy"><strong><?= e($request['service_name']) ?></strong><small><?= e($request['request_code']) ?></small><small><?= e(tanggal_id($request['submitted_at'], TRUE)) ?></small></span>
            <span class="warga-request-status"><?= warga_status_label($request['status']) ?><i class="fa fa-chevron-right" aria-hidden="true"></i></span>
        </a>
    <?php endforeach; ?>
</section>
<?php $this->load->view('layouts/list_pagination'); ?>
