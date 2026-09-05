<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-community community-v22-page community-v22-announcement-page">
    <header class="community-heading community-v22-page-hero" aria-labelledby="announcement-page-title">
        <div class="community-v22-page-hero-icon"><i class="fa fa-bullhorn" aria-hidden="true"></i></div>
        <div>
            <p class="community-v22-eyebrow">Kabar kampung</p>
            <h1 id="announcement-page-title">Pengumuman</h1>
            <p><?= e($currentUser['village_name']) ?> <span aria-hidden="true">·</span> Informasi terbaru untuk warga</p>
        </div>
    </header>

    <?php if ($canManage && $ready): ?>
    <details class="community-compose community-v22-compose">
        <summary><i class="fa fa-plus" aria-hidden="true"></i><span>Buat Pengumuman</span><i class="fa fa-chevron-down" aria-hidden="true"></i></summary>
        <form method="post" action="<?= site_url('pengumuman/terbitkan') ?>">
            <?= csrf_field() ?>
            <label for="announcement-title">Judul</label>
            <input id="announcement-title" name="title" maxlength="180" required>
            <label for="announcement-body">Isi pengumuman</label>
            <textarea id="announcement-body" name="body" rows="7" minlength="10" maxlength="10000" required></textarea>
            <button class="community-button" type="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i> Terbitkan</button>
        </form>
    </details>
    <?php endif; ?>

    <section class="community-v22-feed" aria-label="Daftar pengumuman">
        <div class="community-v22-feed-heading"><h2>Info terbaru</h2><span><?= count($items) ?> kabar</span></div>
        <div class="community-list">
            <?php if (!$items): ?><p class="community-empty" role="status">Belum ada pengumuman.</p><?php endif; ?>
            <?php foreach ($items as $item): ?>
            <article class="community-item community-v22-feed-item">
                <div class="community-v22-feed-item-icon"><i class="fa fa-bullhorn" aria-hidden="true"></i></div>
                <div class="community-v22-feed-item-copy">
                    <div class="community-meta"><time><?= e(tanggal_id($item['created_at'])) ?></time><span><?= e($item['status'] === 'archived' ? 'Diarsipkan' : $item['author_name']) ?></span></div>
                    <h2><a href="<?= site_url('pengumuman/'.$item['id']) ?>"><?= e($item['title']) ?></a></h2>
                    <p><?= e(mb_strimwidth($item['body'], 0, 220, '...')) ?></p>
                    <a class="community-text-link" href="<?= site_url('pengumuman/'.$item['id']) ?>">Selengkapnya <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
