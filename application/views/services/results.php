<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-service-catalog-grid<?= $services ? '' : ' is-empty' ?>" id="wargaServiceGrid" aria-label="Semua surat">
    <?php foreach ($services as $service): ?>
        <?php
        $serviceDescription = isset($service['description']) ? trim((string) $service['description']) : '';
        $requirementCount = isset($service['requirements']) && is_array($service['requirements']) ? count($service['requirements']) : 0;
        $serviceIcon = warga_service_icon($service);
        ?>
        <?php if ($citizenVerified): ?>
            <a href="<?= site_url('permohonan/baru?layanan=' . rawurlencode($service['slug'])) ?>" class="warga-service-catalog-item">
        <?php else: ?>
            <div class="warga-service-catalog-item is-locked" aria-disabled="true">
        <?php endif; ?>
            <span class="warga-service-icon <?= e($serviceIcon['class']) ?>"><i class="<?= e($serviceIcon['icon']) ?>" aria-hidden="true"></i></span>
            <span class="warga-service-catalog-copy">
                <strong><?= e($service['name']) ?></strong>
                <span class="warga-service-description"><?= e($serviceDescription !== '' ? $serviceDescription : 'Layanan administrasi untuk kebutuhan warga.') ?></span>
                <?php if ($requirementCount): ?><small><i class="fa fa-clipboard-list" aria-hidden="true"></i><?= (int) $requirementCount ?> persyaratan</small><?php endif; ?>
            </span>
            <i class="fa <?= $citizenVerified ? 'fa-chevron-right' : 'fa-lock' ?> warga-service-catalog-action" aria-hidden="true"></i>
        <?php if ($citizenVerified): ?></a><?php else: ?></div><?php endif; ?>
    <?php endforeach; ?>
</div>
<?php if (!$services): ?>
    <?php $hasSearch = isset($listing['filters']['q']) && $listing['filters']['q'] !== ''; ?>
    <div class="warga-service-empty">
        <i class="fa <?= $hasSearch ? 'fa-search' : 'fa-folder-open' ?>" aria-hidden="true"></i>
        <strong><?= $hasSearch ? 'Surat tidak ditemukan' : 'Belum ada surat yang tersedia' ?></strong>
        <span><?= $hasSearch ? 'Coba gunakan nama atau kata pencarian lainnya.' : 'Katalog layanan akan tampil setelah diterbitkan oleh pemerintah ' . e($institutionLower ?? 'wilayah') . '.' ?></span>
    </div>
<?php endif; ?>
<?php $this->load->view('layouts/list_pagination'); ?>
