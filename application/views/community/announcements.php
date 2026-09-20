<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-community community-v22-page community-v22-announcement-page">
    <header class="community-heading community-v22-page-hero community-v22-announcement-page-hero" aria-labelledby="announcement-page-title">
        <div class="community-v22-page-hero-icon"><i class="fa fa-bullhorn" aria-hidden="true"></i></div>
        <div>
            <h1 id="announcement-page-title">Info</h1>
            <p class="community-v22-hero-subtitle">Informasi Terbaru</p>
        </div>
    </header>

    <?php if ($canManage && $ready): ?>
    <details class="community-compose community-v22-compose">
        <summary><i class="fa fa-plus" aria-hidden="true"></i><span>Buat Info</span><i class="fa fa-chevron-down" aria-hidden="true"></i></summary>
        <form method="post" action="<?= site_url('pengumuman/terbitkan') ?>" enctype="multipart/form-data" data-disable-submit>
            <?= csrf_field() ?>
            <label for="announcement-title">Judul</label>
            <input id="announcement-title" name="title" maxlength="180" required>
            <label for="announcement-body">Isi info</label>
            <textarea id="announcement-body" name="body" rows="7" minlength="10" maxlength="10000" required></textarea>
            <label for="announcement-attachment">Lampiran (opsional)</label>
            <input type="hidden" name="MAX_FILE_SIZE" value="8388608">
            <input id="announcement-attachment" name="announcement_attachment" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" aria-describedby="announcement-attachment-help">
            <small id="announcement-attachment-help" class="community-v22-file-help">Satu file PDF atau gambar, maksimal 8 MB.</small>
            <button class="community-button community-v22-publish-button color-white" type="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i><span>Terbitkan</span></button>
        </form>
    </details>
    <?php endif; ?>

    <section class="community-v22-feed warga-paged-list" data-paged-list aria-label="Daftar info">
        <div class="community-v22-feed-heading community-v22-announcement-feed-heading">
            <div class="community-v22-feed-heading-copy">
                <h2>Info terbaru</h2>
                <span data-list-total data-list-total-label="info"><?= (int) $listing['total'] ?> info</span>
            </div>
            <button type="button" class="community-v22-announcement-search-trigger" data-announcement-search-open aria-label="Cari dan filter info" aria-haspopup="dialog" aria-controls="community-announcement-search-modal"><i class="fa fa-search" aria-hidden="true"></i></button>
        </div>

        <div class="community-v22-announcement-search-modal" id="community-announcement-search-modal" data-announcement-search-modal hidden aria-hidden="true">
            <button type="button" class="community-v22-announcement-search-backdrop" data-announcement-search-close aria-label="Tutup pencarian"></button>
            <section class="community-v22-announcement-search-dialog" role="dialog" aria-modal="true" aria-labelledby="community-announcement-search-title">
                <header>
                    <div><p class="community-v22-eyebrow">FILTER &amp; PENCARIAN</p><h2 id="community-announcement-search-title">Cari info</h2></div>
                    <button type="button" data-announcement-search-close aria-label="Tutup"><i class="fa fa-times" aria-hidden="true"></i></button>
                </header>
                <?php $this->load->view('layouts/list_filters', array('listKind' => 'announcements', 'dateLabel' => 'Tanggal info')); ?>
            </section>
        </div>

        <div data-list-results id="community-announcement-results" aria-busy="false">
            <?php $this->load->view('community/announcement_results'); ?>
        </div>
    </section>
</div>

<?php $this->load->view('community/announcement_attachment_modal'); ?>
