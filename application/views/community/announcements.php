<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$announcementArea = (string) ($currentUser['village_name'] ?? '');
$announcementInstitution = function_exists('mb_strtoupper') ? mb_strtoupper((string) $institutionLower, 'UTF-8') : strtoupper((string) $institutionLower);
?>
<div class="warga-community community-v22-page community-v22-announcement-page">
    <header class="community-heading community-v22-page-hero" aria-labelledby="announcement-page-title">
        <div class="community-v22-page-hero-icon"><i class="fa fa-bullhorn" aria-hidden="true"></i></div>
        <div>
            <p class="community-v22-eyebrow">Kabar <?= e($institutionLower) ?></p>
            <h1 id="announcement-page-title">Pengumuman</h1>
            <p><?= e($announcementArea) ?> <span aria-hidden="true">·</span> Informasi terbaru untuk warga</p>
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
            <?php if (!$items): ?>
            <div class="community-v22-empty-card" role="status">
                <span class="community-v22-empty-icon" aria-hidden="true"><i class="fa fa-bullhorn"></i></span>
                <span class="community-v22-empty-copy">
                    <strong>Belum ada pengumuman</strong>
                    <span>Informasi terbaru dari <?= e($institutionLower) ?> akan tampil di sini.</span>
                </span>
            </div>
            <?php endif; ?>
            <?php foreach ($items as $item): ?>
            <?php
            $announcementAuthor = trim((string) ($item['village_name'] ?? ''));
            if ($announcementAuthor === '') $announcementAuthor = trim((string) ($item['author_name'] ?? ''));
            if ($announcementAuthor === '' && ($item['status'] ?? '') === 'archived') $announcementAuthor = 'Diarsipkan';
            ?>
            <article class="community-v22-announcement-card">
                <a class="community-v22-announcement-card-head" href="<?= site_url('pengumuman/'.$item['id']) ?>">
                    <span class="community-v22-announcement-card-icon" aria-hidden="true"><i class="fa fa-bullhorn"></i></span>
                    <span class="community-v22-announcement-card-copy">
                        <span class="community-v22-announcement-card-eyebrow">PENGUMUMAN <?= e($announcementInstitution) ?></span>
                        <strong><?= e($item['title']) ?></strong>
                        <span class="community-v22-announcement-card-meta"><span class="community-v22-announcement-card-meta-item"><i class="far fa-calendar-alt" aria-hidden="true"></i><time><?= e(tanggal_id($item['created_at'])) ?></time></span><?php if ($announcementAuthor !== ''): ?><span class="community-v22-announcement-card-meta-item"><i class="fa fa-map-marker-alt" aria-hidden="true"></i><span><?= e($announcementAuthor) ?></span></span><?php endif; ?></span>
                    </span>
                </a>
                <div class="community-v22-announcement-card-body">
                    <p><?= e(mb_strimwidth((string) $item['body'], 0, 220, '...')) ?></p>
                    <a class="community-text-link" href="<?= site_url('pengumuman/'.$item['id']) ?>">Baca selengkapnya <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
