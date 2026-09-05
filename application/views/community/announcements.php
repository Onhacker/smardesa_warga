<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-community">
<header class="community-heading"><div><h1>Pengumuman</h1><p><?= e($currentUser['village_name']) ?></p></div><i class="fa fa-bullhorn" aria-hidden="true"></i></header>
<?php if (!$ready): ?><p class="community-empty" role="status">Layanan pengumuman sedang disiapkan.</p><?php endif; ?>
<?php if ($canManage && $ready): ?>
<details class="community-compose"><summary><i class="fa fa-plus"></i> Buat Pengumuman</summary>
<form method="post" action="<?= site_url('pengumuman/terbitkan') ?>">
<?= csrf_field() ?>
<label for="announcement-title">Judul</label><input id="announcement-title" name="title" maxlength="180" required>
<label for="announcement-body">Isi pengumuman</label><textarea id="announcement-body" name="body" rows="7" minlength="10" maxlength="10000" required></textarea>
<button class="community-button" type="submit"><i class="fa fa-paper-plane"></i> Terbitkan</button>
</form></details>
<?php endif; ?>
<div class="community-list">
<?php if (!$items && $ready): ?><p class="community-empty">Belum ada pengumuman.</p><?php endif; ?>
<?php foreach ($items as $item): ?>
<article class="community-item"><div class="community-meta"><time><?= e(tanggal_id($item['created_at'])) ?></time><span><?= e($item['status'] === 'archived' ? 'Diarsipkan' : $item['author_name']) ?></span></div>
<h2><a href="<?= site_url('pengumuman/'.$item['id']) ?>"><?= e($item['title']) ?></a></h2>
<p><?= e(mb_strimwidth($item['body'],0,220,'...')) ?></p><a class="community-text-link" href="<?= site_url('pengumuman/'.$item['id']) ?>">Selengkapnya <i class="fa fa-arrow-right"></i></a></article>
<?php endforeach; ?>
</div></div>

