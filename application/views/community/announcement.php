<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<article class="warga-community community-detail"><div class="community-meta"><time><?= e(tanggal_id($item['created_at'],true)) ?></time><span><?= e($item['author_name']) ?></span></div>
<h1><?= e($item['title']) ?></h1><div class="community-prose"><?= nl2br(e($item['body'])) ?></div>
<?php if ($canManage && $item['status'] === 'published'): ?><form method="post" action="<?= site_url('pengumuman/'.$item['id'].'/arsipkan') ?>"><?= csrf_field() ?><button class="community-button is-secondary"><i class="fa fa-archive"></i> Arsipkan</button></form><?php endif; ?>
</article>

