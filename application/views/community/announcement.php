<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$announcementAuthor = trim((string) ($item['village_name'] ?? ''));
if ($announcementAuthor === '') $announcementAuthor = trim((string) ($item['author_name'] ?? ''));
?>
<article class="warga-community community-detail community-v22-detail community-v22-announcement-detail">
    <header class="community-v22-detail-hero">
        <div class="community-v22-page-hero-icon"><i class="fa fa-bullhorn" aria-hidden="true"></i></div>
        <div>
            <p class="community-v22-eyebrow">Pengumuman <?= e($institutionLower) ?></p>
            <h1><?= e($item['title']) ?></h1>
            <div class="community-meta community-v22-announcement-detail-meta"><span class="community-v22-announcement-card-meta-item"><i class="far fa-calendar-alt" aria-hidden="true"></i><time><?= e(tanggal_id($item['created_at'], true)) ?></time></span><?php if ($announcementAuthor !== ''): ?><span class="community-v22-announcement-card-meta-item"><i class="fa fa-map-marker-alt" aria-hidden="true"></i><span><?= e($announcementAuthor) ?></span></span><?php endif; ?></div>
        </div>
    </header>
    <div class="community-v22-detail-body">
        <div class="community-prose"><?= nl2br(e($item['body'])) ?></div>
        <?php if ($canManage && $item['status'] === 'published'): ?><form method="post" action="<?= site_url('pengumuman/'.$item['id'].'/arsipkan') ?>"><?= csrf_field() ?><button class="community-button is-secondary"><i class="fa fa-archive" aria-hidden="true"></i> Arsipkan</button></form><?php endif; ?>
    </div>
</article>
