<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $item = isset($item) && is_array($item) ? $item : array(); ?>
<article class="community-item community-v22-feed-item" data-complaint-id="<?= e($item['id'] ?? '') ?>">
    <div class="community-v22-feed-item-icon is-complaint"><i class="fa fa-comments" aria-hidden="true"></i></div>
    <div class="community-v22-feed-item-copy">
        <div class="community-meta"><time><?= e(tanggal_id($item['created_at'] ?? '', true)) ?></time><span class="community-status"><?= e(warga_complaint_status($item['status'] ?? 'received')) ?></span></div>
        <h2><a href="<?= site_url('pengaduan/'.($item['id'] ?? '')) ?>"><?= e($item['title'] ?? '') ?></a></h2>
        <?php if (!empty($canManage)): ?><p><?= e($item['citizen_name'] ?? '') ?></p><?php endif; ?>
        <p><?= e(mb_strimwidth((string) ($item['body'] ?? ''), 0, 180, '...')) ?></p>
        <a class="community-text-link" href="<?= site_url('pengaduan/'.($item['id'] ?? '')) ?>">Lihat Pengaduan <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
    </div>
</article>
