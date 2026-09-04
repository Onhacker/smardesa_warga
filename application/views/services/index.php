<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$citizenVerified = !empty($citizenVerified);
?>
<div class="warga-services-page">
    <section class="warga-page-intro warga-services-intro">
        <div><p>PELAYANAN DESA</p><h1>Semua Jenis Surat</h1><span>Temukan layanan administrasi yang Anda perlukan.</span></div>
        <span class="warga-intro-icon"><i class="ti ti-mail" aria-hidden="true"></i></span>
    </section>

    <section class="warga-service-catalog" aria-labelledby="warga-catalog-title">
        <div class="warga-service-catalog-head">
            <div><p>KATALOG LAYANAN</p><h2 id="warga-catalog-title">Pilih Jenis Surat</h2></div>
            <span class="warga-service-total"><?= count($services) ?> layanan</span>
        </div>

        <?php if ($services): ?>
            <div class="warga-service-search warga-service-catalog-search">
                <label for="wargaServiceSearch">Cari surat</label>
                <div class="warga-service-search-input">
                    <i class="fa fa-search" aria-hidden="true"></i>
                    <input type="search" id="wargaServiceSearch" placeholder="Nama atau jenis surat" data-service-search aria-controls="wargaServiceGrid" autocomplete="off">
                </div>
                <span class="warga-service-count" data-service-count aria-live="polite"><?= count($services) ?> layanan</span>
            </div>
        <?php endif; ?>

        <div class="warga-service-catalog-grid" id="wargaServiceGrid" aria-label="Semua jenis layanan">
            <?php foreach ($services as $index => $service): ?>
                <?php
                $serviceDescription = isset($service['description']) ? trim((string) $service['description']) : '';
                $serviceShortName = isset($service['short_name']) ? $service['short_name'] : '';
                $serviceSearch = $service['name'] . ' ' . $serviceShortName . ' ' . $service['slug'] . ' ' . $serviceDescription;
                $requirementCount = isset($service['requirements']) && is_array($service['requirements']) ? count($service['requirements']) : 0;
                $serviceIcon = warga_service_icon($service);
                ?>
                <?php if ($citizenVerified): ?>
                    <a href="<?= site_url('permohonan/baru?layanan=' . rawurlencode($service['slug'])) ?>" class="warga-service-catalog-item" data-service-name="<?= e($serviceSearch) ?>">
                <?php else: ?>
                    <div class="warga-service-catalog-item is-locked" data-service-name="<?= e($serviceSearch) ?>" aria-disabled="true">
                <?php endif; ?>
                    <span class="warga-service-icon <?= e($serviceIcon['class']) ?>"><i class="<?= e($serviceIcon['icon']) ?>" aria-hidden="true"></i></span>
                    <span class="warga-service-catalog-copy">
                        <strong><?= e($service['name']) ?></strong>
                        <span class="warga-service-description"><?= e($serviceDescription !== '' ? $serviceDescription : 'Layanan administrasi untuk kebutuhan warga.') ?></span>
                        <?php if ($requirementCount): ?><small><i class="fa fa-clipboard-list" aria-hidden="true"></i><?= $requirementCount ?> persyaratan</small><?php endif; ?>
                    </span>
                    <i class="fa <?= $citizenVerified ? 'fa-chevron-right' : 'fa-lock' ?> warga-service-catalog-action" aria-hidden="true"></i>
                <?php if ($citizenVerified): ?></a><?php else: ?></div><?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="warga-service-empty" data-service-empty <?= $services ? 'hidden' : '' ?>>
            <i class="fa <?= $services ? 'fa-search' : 'fa-folder-open' ?>" aria-hidden="true"></i>
            <strong><?= $services ? 'Surat tidak ditemukan' : 'Belum ada layanan dari desa' ?></strong>
            <span><?= $services ? 'Coba gunakan kata pencarian lainnya.' : 'Katalog layanan akan tampil setelah diterbitkan oleh desa.' ?></span>
        </div>
    </section>

    <?php if (!$citizenVerified): ?>
        <section class="warga-verification-notice" role="status">
            <i class="fa fa-user-shield" aria-hidden="true"></i>
            <div><strong>Pengajuan belum tersedia</strong><p>Anda tetap dapat melihat katalog. Hubungi operator desa untuk memverifikasi akun sebelum mengajukan surat.</p></div>
        </section>
    <?php endif; ?>
</div>
