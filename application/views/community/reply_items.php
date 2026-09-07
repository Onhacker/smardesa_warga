<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (empty($replies)): ?>
    <p class="community-empty" data-complaint-replies-empty>Belum ada tanggapan.</p>
<?php else: ?>
    <?php foreach ($replies as $reply): ?>
        <article class="community-item">
            <div class="community-meta"><strong><?= e($reply['actor_name']) ?></strong><time><?= e(tanggal_id($reply['created_at'], true)) ?></time></div>
            <p><?= nl2br(e($reply['message'])) ?></p>
            <span class="community-status"><?= e(warga_complaint_status($reply['status'])) ?></span>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
