<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-community community-v22-detail community-v22-complaint-detail">
    <article class="community-detail">
        <header class="community-v22-detail-hero">
            <div class="community-v22-page-hero-icon"><i class="fa fa-comments" aria-hidden="true"></i></div>
            <div>
                <p class="community-v22-eyebrow">Detail pengaduan</p>
                <h1><?= e($item['title']) ?></h1>
                <div class="community-meta"><span class="community-status"><?= e(warga_complaint_status($item['status'])) ?></span><time><?= e(tanggal_id($item['created_at'], true)) ?></time></div>
            </div>
        </header>
        <div class="community-v22-detail-body">
            <p class="community-v22-detail-subtitle"><i class="fa fa-user" aria-hidden="true"></i> <?= e($item['citizen_name']) ?><?= $item['location'] ? ' <span aria-hidden="true">·</span> '.e($item['location']) : '' ?></p>
            <div class="community-prose"><?= nl2br(e($item['body'])) ?></div>
        </div>
    </article>

    <section class="community-replies">
        <div class="community-v22-feed-heading"><h2>Tanggapan</h2><span><?= count($replies) ?> tanggapan</span></div>
        <?php if (!$replies): ?><p class="community-empty">Belum ada tanggapan.</p><?php endif; ?>
        <?php foreach ($replies as $reply): ?><article class="community-item"><div class="community-meta"><strong><?= e($reply['actor_name']) ?></strong><time><?= e(tanggal_id($reply['created_at'], true)) ?></time></div><p><?= nl2br(e($reply['message'])) ?></p><span class="community-status"><?= e(warga_complaint_status($reply['status'])) ?></span></article><?php endforeach; ?>
    </section>

    <?php if ($canManage): ?><form class="community-compose community-v22-compose" method="post" action="<?= site_url('pengaduan/'.$item['id'].'/tanggapan') ?>">
        <?= csrf_field() ?><div class="community-v22-form-title"><i class="fa fa-reply" aria-hidden="true"></i> Kirim tanggapan</div>
        <label for="complaint-status">Status</label><select id="complaint-status" name="status"><?php foreach (array('received','processing','resolved','rejected') as $status): ?><option value="<?= $status ?>" <?= $item['status'] === $status ? 'selected' : '' ?>><?= e(warga_complaint_status($status)) ?></option><?php endforeach; ?></select>
        <label for="reply-message">Tanggapan</label><textarea id="reply-message" name="message" rows="4" minlength="5" maxlength="3000" required></textarea><button class="community-button"><i class="fa fa-paper-plane" aria-hidden="true"></i> Kirim Tanggapan</button>
    </form><?php endif; ?>
</div>
