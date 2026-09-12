<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-community community-v22-page community-v22-notification-page">
    <header class="community-heading community-v22-page-hero" aria-labelledby="notification-page-title">
        <div class="community-v22-page-hero-icon"><i class="fa fa-inbox" aria-hidden="true"></i></div>
        <div>
            <p class="community-v22-eyebrow">Pembaruan layanan</p>
            <h1 id="notification-page-title">Pemberitahuan</h1>
            <p>Surat, pengumuman, dan pengaduan terbaru Anda.</p>
        </div>
    </header>

    <section class="warga-notification-enable-card" data-push-enable-prompt hidden aria-labelledby="notification-enable-title">
        <span class="warga-notification-enable-icon" aria-hidden="true"><i class="fa fa-inbox"></i></span>
        <div class="warga-notification-enable-copy">
            <h2 id="notification-enable-title">Nyalakan pemberitahuan</h2>
            <p>Terima pembaruan layanan langsung di perangkat Anda.</p>
            <span data-push-status role="status" aria-live="polite"></span>
        </div>
        <div class="custom-control small-switch ios-switch warga-notification-enable-switch">
            <input data-push-toggle type="checkbox" class="ios-input" id="switch-notification-page-push" aria-label="Nyalakan pemberitahuan">
            <label class="custom-control-label" for="switch-notification-page-push" aria-hidden="true"></label>
        </div>
    </section>

    <div class="warga-paged-list warga-notification-paged-list" data-paged-list>
        <div class="warga-notification-toolbar">
            <form class="warga-notification-actions" method="post" action="<?= site_url('notifikasi/baca') ?>" data-notification-mark-all data-confirm="Semua pemberitahuan yang belum dibaca akan ditandai sebagai sudah dibaca. Lanjutkan?" data-confirm-title="Tandai semua dibaca?" data-confirm-button="Ya" data-confirm-tone="info">
                <?= csrf_field() ?><button type="submit" class="community-button is-secondary"><i class="fa fa-check-double" aria-hidden="true"></i><span>Tandai semua dibaca</span></button><span class="visually-hidden" data-notification-mark-all-status role="status" aria-live="polite" aria-atomic="true"></span>
            </form>
            <button type="button" class="warga-notification-search-trigger" data-notification-search-open aria-label="Cari dan filter pemberitahuan" aria-haspopup="dialog" aria-controls="warga-notification-search-modal"><i class="fa fa-search" aria-hidden="true"></i></button>
        </div>

        <div class="warga-notification-search-modal" id="warga-notification-search-modal" data-notification-search-modal hidden aria-hidden="true">
            <button type="button" class="warga-notification-search-backdrop" data-notification-search-close aria-label="Tutup pencarian"></button>
            <section class="warga-notification-search-dialog" role="dialog" aria-modal="true" aria-labelledby="warga-notification-search-title">
                <header>
                    <div><p class="community-v22-eyebrow">FILTER &amp; PENCARIAN</p><h2 id="warga-notification-search-title">Cari pemberitahuan</h2></div>
                    <button type="button" data-notification-search-close aria-label="Tutup"><i class="fa fa-times" aria-hidden="true"></i></button>
                </header>
                <?php $this->load->view('layouts/list_filters', array('listKind' => 'notifications', 'dateLabel' => 'Tanggal pembaruan')); ?>
            </section>
        </div>

        <div data-list-results id="warga-list-results" aria-busy="false">
            <?php $this->load->view('notifications/results'); ?>
        </div>
    </div>
</div>
