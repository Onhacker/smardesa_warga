<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-community community-v22-page community-v22-complaint-page">
    <header class="community-heading community-v22-page-hero" aria-labelledby="complaint-page-title">
        <div class="community-v22-page-hero-icon"><i class="fa fa-comments" aria-hidden="true"></i></div>
        <div>
            <p class="community-v22-eyebrow">Suara warga</p>
            <h1 id="complaint-page-title"><?= $canManage ? 'Pengaduan Warga' : 'Pengaduan Saya' ?></h1>
            <p><?= e($currentUser['village_name']) ?> <span aria-hidden="true">·</span> Sampaikan aspirasi dengan mudah</p>
        </div>
    </header>

    <?php if (!$staffMode && $ready): ?>
    <details class="community-compose community-v22-compose">
        <summary><i class="fa fa-plus" aria-hidden="true"></i><span>Buat Pengaduan</span><i class="fa fa-chevron-down" aria-hidden="true"></i></summary>
        <form method="post" action="<?= site_url('pengaduan/kirim') ?>" data-complaint-form>
            <?= csrf_field() ?>
            <label for="complaint-title">Judul</label>
            <input id="complaint-title" name="title" maxlength="180" required>
            <label for="complaint-location">Lokasi</label>
            <input id="complaint-location" name="location" maxlength="255">
            <label for="complaint-body">Isi pengaduan</label>
            <textarea id="complaint-body" name="body" rows="6" minlength="10" maxlength="5000" required></textarea>
            <button class="community-button community-submit-button color-white" type="submit"><i class="fa fa-paper-plane color-white" aria-hidden="true"></i><span class="color-white">Kirim Pengaduan</span></button>
        </form>
    </details>
    <?php endif; ?>

    <section class="community-v22-feed" aria-label="Daftar pengaduan">
        <div class="community-v22-feed-heading"><h2>Daftar pengaduan</h2><span data-complaint-count><?= count($items) ?> laporan</span></div>
        <div class="community-list" data-complaint-list>
            <?php if (!$items): ?>
            <div class="community-v22-empty-card" role="status" data-complaint-empty>
                <span class="community-v22-empty-icon is-complaint" aria-hidden="true"><i class="fa fa-comments"></i></span>
                <span class="community-v22-empty-copy">
                    <strong>Belum ada pengaduan</strong>
                    <span>Pengaduan warga akan tampil di sini setelah dikirim.</span>
                </span>
            </div>
            <?php endif; ?>
            <?php foreach ($items as $item): ?>
                <?php $this->load->view('community/complaint_item', array('item' => $item, 'canManage' => $canManage)); ?>
            <?php endforeach; ?>
        </div>
    </section>
</div>
