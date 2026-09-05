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
        <form method="post" action="<?= site_url('pengaduan/kirim') ?>">
            <?= csrf_field() ?>
            <label for="complaint-title">Judul</label>
            <input id="complaint-title" name="title" maxlength="180" required>
            <label for="complaint-location">Lokasi</label>
            <input id="complaint-location" name="location" maxlength="255">
            <label for="complaint-body">Isi pengaduan</label>
            <textarea id="complaint-body" name="body" rows="6" minlength="10" maxlength="5000" required></textarea>
            <button class="community-button" type="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i> Kirim Pengaduan</button>
        </form>
    </details>
    <?php endif; ?>

    <section class="community-v22-feed" aria-label="Daftar pengaduan">
        <div class="community-v22-feed-heading"><h2>Daftar pengaduan</h2><span><?= count($items) ?> laporan</span></div>
        <div class="community-list">
            <?php if (!$items): ?><p class="community-empty" role="status">Belum ada pengaduan.</p><?php endif; ?>
            <?php foreach ($items as $item): ?>
            <article class="community-item community-v22-feed-item">
                <div class="community-v22-feed-item-icon is-complaint"><i class="fa fa-comments" aria-hidden="true"></i></div>
                <div class="community-v22-feed-item-copy">
                    <div class="community-meta"><time><?= e(tanggal_id($item['created_at'], true)) ?></time><span class="community-status"><?= e(warga_complaint_status($item['status'])) ?></span></div>
                    <h2><a href="<?= site_url('pengaduan/'.$item['id']) ?>"><?= e($item['title']) ?></a></h2>
                    <?php if ($canManage): ?><p><?= e($item['citizen_name']) ?></p><?php endif; ?>
                    <p><?= e(mb_strimwidth($item['body'], 0, 180, '...')) ?></p>
                    <a class="community-text-link" href="<?= site_url('pengaduan/'.$item['id']) ?>">Lihat Pengaduan <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
