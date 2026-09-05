<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-community community-v22-home">
    <section class="community-home community-v22-hero" aria-labelledby="community-welcome-title">
        <div class="community-v22-hero-main">
            <img class="community-v22-hero-logo" src="<?= base_url('assets/pwa/icon-192.png') ?>" width="72" height="72" alt="Logo SmartDesa Warga">
            <div class="community-v22-hero-copy">
                <p class="community-v22-eyebrow">Layanan digital warga</p>
                <h1 id="community-welcome-title">Halo, <?= e($currentUser['name']) ?></h1>
                <p class="community-v22-location"><i class="fa fa-map-marker-alt" aria-hidden="true"></i> <?= e($currentUser['village_name']) ?></p>
            </div>
            <a class="community-v22-hero-action" href="<?= site_url('notifikasi') ?>" aria-label="Buka notifikasi surat">
                <i class="fa fa-bell" aria-hidden="true"></i>
                <span data-notification-count></span>
            </a>
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
            <a class="community-v22-slide splide__slide is-letter" href="<?= site_url('surat') ?>">
                <img src="<?= base_url('assets/v22/images/pictures/19.jpg') ?>" alt="" loading="lazy" width="700" height="466">
                <span class="community-v22-slide-overlay" aria-hidden="true"></span>
                <span class="community-v22-slide-icon"><i class="fa fa-envelope" aria-hidden="true"></i></span>
                <span class="community-v22-slide-copy">
                    <small>Layanan administrasi</small>
                    <strong>Surat</strong>
                    <span><?= number_format($summary['active']) ?> permohonan diproses</span>
                </span>
            </a>
            <a class="community-v22-slide splide__slide is-announcement" href="<?= site_url('pengumuman') ?>">
                <img src="<?= base_url('assets/v22/images/pictures/20.jpg') ?>" alt="" loading="lazy" width="700" height="466">
                <span class="community-v22-slide-overlay" aria-hidden="true"></span>
                <span class="community-v22-slide-icon"><i class="fa fa-bullhorn" aria-hidden="true"></i></span>
                <span class="community-v22-slide-copy">
                    <small>Informasi kampung</small>
                    <strong>Pengumuman</strong>
                    <span><?= $announcements ? number_format(count($announcements)) . ' informasi terbaru' : 'Belum ada informasi terbaru' ?></span>
                </span>
            </a>
            <a class="community-v22-slide splide__slide is-complaint" href="<?= site_url('pengaduan') ?>">
                <img src="<?= base_url('assets/v22/images/pictures/6.jpg') ?>" alt="" loading="lazy" width="700" height="466">
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

    <section class="community-v22-information" aria-labelledby="community-information-title">
        <header class="community-v22-section-head">
            <div>
                <p class="community-v22-eyebrow">Tetap terhubung</p>
                <h2 id="community-information-title">Informasi Warga</h2>
            </div>
        </header>

        <div class="community-v22-feature-list">
            <a class="community-v22-feature" href="<?= site_url('notifikasi') ?>">
                <img src="<?= base_url('assets/v22/images/pictures/18l.jpg') ?>" alt="" loading="lazy" width="300" height="466">
                <span class="community-v22-feature-copy">
                    <small>Kabar layanan</small>
                    <strong>Notifikasi Surat</strong>
                    <span>Pantau perkembangan dan hasil permohonan surat Anda.</span>
                    <b>Buka notifikasi <span data-notification-count></span> <i class="fa fa-arrow-right" aria-hidden="true"></i></b>
                </span>
            </a>

            <a class="community-v22-feature" href="<?= site_url('kontak') ?>">
                <img src="<?= base_url('assets/v22/images/pictures/3l.jpg') ?>" alt="" loading="lazy" width="300" height="466">
                <span class="community-v22-feature-copy">
                    <small>Bantuan warga</small>
                    <strong>Kontak <?= e($village['institution']) ?></strong>
                    <span>Hubungi petugas <?= e($village['name'] ?? $currentUser['village_name']) ?> saat Anda membutuhkan bantuan.</span>
                    <b>Lihat kontak <i class="fa fa-arrow-right" aria-hidden="true"></i></b>
                </span>
            </a>
        </div>
    </section>

    <section class="community-v22-announcements" aria-labelledby="community-announcements-title">
        <header class="community-v22-section-head">
            <div>
                <p class="community-v22-eyebrow">Kabar kampung</p>
                <h2 id="community-announcements-title">Pengumuman Terbaru</h2>
            </div>
            <a href="<?= site_url('pengumuman') ?>">Lihat semua <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
        </header>

        <?php if (!$announcements): ?>
            <div class="community-v22-empty-card" role="status">
                <span class="community-v22-empty-icon" aria-hidden="true"><i class="fa fa-bullhorn"></i></span>
                <span class="community-v22-empty-copy">
                    <strong>Belum ada pengumuman</strong>
                    <span>Informasi terbaru dari kampung akan tampil di sini.</span>
                </span>
            </div>
        <?php endif; ?>
        <?php foreach ($announcements as $item): ?>
            <article class="community-item">
                <time datetime="<?= e(date('Y-m-d', strtotime($item['created_at']))) ?>"><?= e(tanggal_id($item['created_at'])) ?></time>
                <h2><a href="<?= site_url('pengumuman/'.$item['id']) ?>"><?= e($item['title']) ?></a></h2>
                <p><?= e(mb_strimwidth($item['body'], 0, 180, '...')) ?></p>
                <a class="community-text-link" href="<?= site_url('pengumuman/'.$item['id']) ?>">Baca pengumuman <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
            </article>
        <?php endforeach; ?>
    </section>
</div>
