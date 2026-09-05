<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<article class="warga-community community-detail community-v22-detail community-v22-announcement-detail">
    <header class="community-v22-detail-hero">
        <div class="community-v22-page-hero-icon"><i class="fa fa-bullhorn" aria-hidden="true"></i></div>
        <div>
            <p class="community-v22-eyebrow">Pengumuman <?= e($institutionLower) ?></p>
            <h1><?= e($item['title']) ?></h1>
            <div class="community-meta"><time><?= e(tanggal_id($item['created_at'], true)) ?></time><span><?= e($item['author_name']) ?></span></div>
        </div>
    </header>
    <div class="community-v22-detail-body">
        <div class="community-prose"><?= nl2br(e($item['body'])) ?></div>
        <?php if ($canManage && $item['status'] === 'published'): ?><form method="post" action="<?= site_url('pengumuman/'.$item['id'].'/arsipkan') ?>"><?= csrf_field() ?><button class="community-button is-secondary"><i class="fa fa-archive" aria-hidden="true"></i> Arsipkan</button></form><?php endif; ?>
    </div>
</article>
