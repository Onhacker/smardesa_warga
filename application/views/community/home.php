<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$isAuthenticated = !empty($isAuthenticated) && is_array($currentUser);
$publicArea = trim((string) ($village['name'] ?? (getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya')));
$heroRegency = trim((string) ($isAuthenticated
    ? ($currentUser['regency_name'] ?? '')
    : ($village['regency_name'] ?? '')));
if ($heroRegency === '') $heroRegency = trim((string) (getenv('PUBLIC_REGENCY_NAME') ?: ''));
if ($heroRegency === '') $heroRegency = $publicArea !== '' ? $publicArea : 'Jayawijaya';
$heroName = $isAuthenticated ? (string) ($currentUser['name'] ?? 'Warga') : 'Layanan warga';
$heroLocation = $isAuthenticated ? (string) ($currentUser['village_name'] ?? $publicArea) : $publicArea;
$heroTagline = 'Sistem Informasi Digitalisasi Administrasi, Pelayanan Umum, dan Layanan Informasi Kampung';
$pictureUrl = static function ($name) {
    return warga_asset_url('assets/v22/images/pictures/' . trim((string) $name) . '.webp');
};
?>
<div class="warga-community community-v22-home" data-dashboard-home>
    <section class="community-home community-v22-hero" aria-labelledby="community-welcome-title">
        <div class="community-v22-hero-main">
            <img class="community-v22-hero-logo" src="<?= warga_asset_url('assets/pwa/icon-192.png') ?>" width="72" height="72" alt="Logo SI DAPULIK">
            <div class="community-v22-hero-copy">
                <p class="community-v22-eyebrow">SI DAPULIK <?= e($heroRegency) ?></p>
                <p class="community-v22-hero-tagline"><?= e($heroTagline) ?></p>
                <h1 id="community-welcome-title"><?= $isAuthenticated ? 'Halo, ' : '' ?><?= e($heroName) ?></h1>
                <p class="community-v22-location"><i class="fa fa-map-marker-alt" aria-hidden="true"></i> <?= e($heroLocation) ?></p>
            </div>
        </div>

    </section>

    <section class="community-v22-services" aria-labelledby="community-services-title">
        <header class="community-v22-section-head">
            <div>
                <p class="community-v22-eyebrow">Akses cepat</p>
                <h2 id="community-services-title">Layanan untuk Anda</h2>
            </div>
            <span>Geser untuk melihat <i class="fa fa-arrow-right" aria-hidden="true"></i></span>
        </header>

        <nav class="community-v22-slider splide double-slider visible-slider slider-no-dots" id="community-services-slider" aria-label="Layanan utama">
            <div class="splide__track">
                <div class="splide__list">
            <a class="community-v22-slide splide__slide is-marketplace" href="<?= site_url('pasar') ?>" data-dashboard-media-card>
                <img src="<?= $pictureUrl('pasar-layanan') ?>" srcset="<?= $pictureUrl('pasar-layanan-480') ?> 480w, <?= $pictureUrl('pasar-layanan-768') ?> 768w, <?= $pictureUrl('pasar-layanan') ?> 1200w" sizes="(max-width: 560px) 86vw, (max-width: 900px) 58vw, 720px" alt="Ilustrasi Pasar Digital <?= e($institutionLabel) ?>" loading="eager" fetchpriority="high" decoding="async" width="1200" height="676">
                <span class="community-v22-slide-overlay" aria-hidden="true"></span>
                <span class="community-v22-slide-icon"><i class="fa fa-store" aria-hidden="true"></i></span>
                <span class="community-v22-slide-copy">
                    <small>Ekonomi <?= e($institutionLower) ?></small>
                    <strong>Pasar Digital <?= e($institutionLabel) ?></strong>
                    <span>Temukan produk warga <?= e($institutionLower) ?></span>
                </span>
            </a>
            <a class="community-v22-slide splide__slide is-letter" href="<?= site_url('surat') ?>" data-dashboard-media-card>
                <img src="<?= $pictureUrl('surat-layanan') ?>" srcset="<?= $pictureUrl('surat-layanan-480') ?> 480w, <?= $pictureUrl('surat-layanan-768') ?> 768w, <?= $pictureUrl('surat-layanan') ?> 1200w" sizes="(max-width: 560px) 86vw, (max-width: 900px) 58vw, 720px" alt="Ilustrasi layanan surat" loading="lazy" decoding="async" width="1200" height="799">
                <span class="community-v22-slide-overlay" aria-hidden="true"></span>
                <span class="community-v22-slide-icon"><i class="fa fa-envelope" aria-hidden="true"></i></span>
                <span class="community-v22-slide-copy">
                    <small>Layanan administrasi</small>
                    <strong>Surat</strong>
                    <span><?= number_format($summary['active']) ?> permohonan diproses</span>
                </span>
            </a>
            <?php if ($isAuthenticated): ?><a class="community-v22-slide splide__slide is-announcement" href="<?= site_url('pengumuman') ?>" data-dashboard-media-card>
                <img src="<?= $pictureUrl('pengumuman-layanan') ?>" srcset="<?= $pictureUrl('pengumuman-layanan-480') ?> 480w, <?= $pictureUrl('pengumuman-layanan-768') ?> 768w, <?= $pictureUrl('pengumuman-layanan') ?> 1200w" sizes="(max-width: 560px) 86vw, (max-width: 900px) 58vw, 720px" alt="Ilustrasi layanan pengumuman" loading="lazy" decoding="async" width="1200" height="676">
                <span class="community-v22-slide-overlay" aria-hidden="true"></span>
                <span class="community-v22-slide-icon"><i class="fa fa-bullhorn" aria-hidden="true"></i></span>
                <span class="community-v22-slide-copy">
                    <small>Informasi <?= e($institutionLower) ?></small>
                    <strong>Pengumuman</strong>
                    <span><?= $announcements ? number_format(count($announcements)) . ' informasi terbaru' : 'Belum ada informasi terbaru' ?></span>
                </span>
            </a><?php endif; ?>
            <a class="community-v22-slide splide__slide is-complaint" href="<?= site_url('pengaduan') ?>" data-dashboard-media-card>
                <img src="<?= $pictureUrl('pengaduan-layanan') ?>" srcset="<?= $pictureUrl('pengaduan-layanan-480') ?> 480w, <?= $pictureUrl('pengaduan-layanan-768') ?> 768w, <?= $pictureUrl('pengaduan-layanan') ?> 1200w" sizes="(max-width: 560px) 86vw, (max-width: 900px) 58vw, 720px" alt="Ilustrasi layanan pengaduan" loading="lazy" width="1200" height="799">
                <span class="community-v22-slide-overlay" aria-hidden="true"></span>
                <span class="community-v22-slide-icon"><i class="fa fa-comments" aria-hidden="true"></i></span>
                <span class="community-v22-slide-copy">
                    <small>Aspirasi warga</small>
                    <strong>Pengaduan</strong>
                    <span>Sampaikan keluhan dan masukan</span>
                </span>
            </a>
                </div>
            </div>
        </nav>
    </section>

    <?php $homeServices = isset($services) && is_array($services) ? array_slice($services, 0, 4) : array(); ?>
    <section class="warga-service-card warga-dashboard-services community-v22-letter-preview" aria-labelledby="community-letter-preview-title">
        <div class="content warga-section-head warga-service-heading-card">
            <div>
                <p class="font-600 color-highlight mb-n1">Pelayanan <?= e($institutionLower) ?></p>
                <h2 id="community-letter-preview-title" class="font-22 mb-0">Ajukan Surat</h2>
            </div>
            <a href="<?= site_url($isAuthenticated ? 'layanan' : 'login') ?>" class="community-v22-section-action font-12 color-highlight font-600">Lihat semua <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <?php if ($homeServices): ?>
            <div class="warga-service-grid" aria-label="Empat jenis surat">
                <?php foreach ($homeServices as $service): ?>
                    <?php
                    $homeServiceName = !empty($service['short_name']) ? $service['short_name'] : $service['name'];
                    $homeServiceIcon = warga_service_icon($service);
                    $homeServiceUrl = $isAuthenticated
                        ? site_url('permohonan/baru?layanan=' . rawurlencode($service['slug']))
                        : site_url('login');
                    ?>
                    <a href="<?= e($homeServiceUrl) ?>" class="warga-service-item" title="<?= e($service['name']) ?>">
                        <span class="warga-service-icon <?= e($homeServiceIcon['class']) ?>"><i class="<?= e($homeServiceIcon['icon']) ?>" aria-hidden="true"></i></span>
                        <strong><?= e($homeServiceName) ?></strong>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="warga-service-empty">Belum ada layanan surat yang tersedia.</p>
        <?php endif; ?>
    </section>

    <?php
    $marketplaceProducts = isset($marketplaceProducts) && is_array($marketplaceProducts) ? array_slice($marketplaceProducts, 0, 4) : array();
    $marketplaceReady = !isset($marketplaceReady) || (bool) $marketplaceReady;
    ?>
    <section class="marketplace-page community-v22-market-preview" aria-labelledby="community-market-preview-title">
        <header class="community-v22-section-head">
            <div>
                <p class="community-v22-eyebrow">Pasar digital</p>
                <h2 id="community-market-preview-title">Produk terbaru</h2>
            </div>
            <a href="<?= site_url('pasar') ?>" class="community-v22-section-action">Lihat semua <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
        </header>

        <?php if ($marketplaceProducts): ?>
            <div class="market-product-grid community-v22-market-grid" data-dashboard-market-preview>
                <?php $this->load->view('marketplace/product_cards', array('products' => $marketplaceProducts, 'eagerFirst' => TRUE)); ?>
            </div>
            <?php $this->load->view('marketplace/review_modal', array('isAuthenticated' => !empty($isAuthenticated))); ?>
        <?php else: ?>
            <div class="community-v22-market-empty" role="status">
                <span class="community-v22-market-empty-icon" aria-hidden="true"><i class="fa fa-store"></i></span>
                <span>
                    <strong><?= $marketplaceReady ? 'Belum ada produk' : 'Pasar digital sedang disiapkan' ?></strong>
                    <small><?= $marketplaceReady ? 'Produk warga akan tampil di sini.' : 'Silakan lihat kembali beberapa saat lagi.' ?></small>
                </span>
            </div>
        <?php endif; ?>
    </section>

    <section class="community-v22-information" aria-labelledby="community-information-title">
        <header class="community-v22-section-head">
            <div>
                <p class="community-v22-eyebrow">Tetap terhubung</p>
                <h2 id="community-information-title">Informasi Warga</h2>
            </div>
        </header>

        <div class="community-v22-feature-list">
            <a class="community-v22-feature" href="<?= site_url('notifikasi') ?>" data-dashboard-media-card>
                <img src="<?= $pictureUrl('notifikasi-layanan') ?>" srcset="<?= $pictureUrl('notifikasi-layanan-256') ?> 256w, <?= $pictureUrl('notifikasi-layanan-384') ?> 384w, <?= $pictureUrl('notifikasi-layanan') ?> 600w" sizes="(max-width: 560px) 106px, 128px" alt="Ilustrasi pemberitahuan layanan" loading="lazy" width="600" height="1067">
                <span class="community-v22-feature-copy">
                    <small>Kabar layanan</small>
                    <strong>Pemberitahuan</strong>
                    <span>Pantau perkembangan layanan dan pengumuman terbaru.</span>
                    <b>Buka pemberitahuan <span data-notification-count></span> <i class="fa fa-arrow-right" aria-hidden="true"></i></b>
                </span>
            </a>

            <a class="community-v22-feature" href="<?= site_url('kontak') ?>" data-dashboard-media-card>
                <img src="<?= $pictureUrl('kontak-lembaga') ?>" srcset="<?= $pictureUrl('kontak-lembaga-256') ?> 256w, <?= $pictureUrl('kontak-lembaga-384') ?> 384w, <?= $pictureUrl('kontak-lembaga') ?> 600w" sizes="(max-width: 560px) 106px, 128px" alt="Ilustrasi kontak <?= e($village['institution']) ?>" loading="lazy" width="600" height="933">
                <span class="community-v22-feature-copy">
                    <small>Bantuan warga</small>
                    <strong>Kontak <?= e($village['institution']) ?></strong>
                    <span>Hubungi <?= e($institutionLabel) ?> saat Anda membutuhkan bantuan.</span>
                    <b>Lihat kontak <i class="fa fa-arrow-right" aria-hidden="true"></i></b>
                </span>
            </a>
        </div>
    </section>

    <section class="community-v22-quick-cards" aria-label="Informasi dan aspirasi warga">
        <?php if ($isAuthenticated): ?><a class="community-v22-quick-card is-announcement" href="<?= site_url('pengumuman') ?>">
            <span class="community-v22-quick-card-head">
                <span class="community-v22-quick-card-icon" aria-hidden="true"><i class="fa fa-bullhorn"></i></span>
                <strong>Pengumuman</strong>
            </span>
            <span class="community-v22-quick-card-copy">
                <?= $announcements ? number_format(count($announcements)) . ' informasi terbaru.' : 'Belum ada informasi terbaru.' ?>
            </span>
        </a><?php endif; ?>
        <a class="community-v22-quick-card is-complaint" href="<?= site_url('pengaduan') ?>">
            <span class="community-v22-quick-card-head">
                <span class="community-v22-quick-card-icon" aria-hidden="true"><i class="fa fa-comment-dots"></i></span>
                <strong>Pengaduan</strong>
            </span>
            <span class="community-v22-quick-card-copy">Sampaikan keluhan dan masukan warga.</span>
        </a>
        <a class="community-v22-quick-card is-marketplace" href="<?= site_url('pasar') ?>">
            <span class="community-v22-quick-card-head">
                <span class="community-v22-quick-card-icon" aria-hidden="true"><i class="fa fa-store"></i></span>
                <strong>Pasar Digital</strong>
            </span>
            <span class="community-v22-quick-card-copy">Temukan dan jual produk warga <?= e($institutionLower) ?>.</span>
        </a>
    </section>
</div>
