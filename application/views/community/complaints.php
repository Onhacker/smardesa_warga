<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-community">
<header class="community-heading"><div><h1><?= $canManage ? 'Pengaduan Warga' : 'Pengaduan Saya' ?></h1><p><?= e($currentUser['village_name']) ?></p></div><i class="fa fa-comments" aria-hidden="true"></i></header>
<?php if (!$ready): ?><p class="community-empty" role="status">Layanan pengaduan sedang disiapkan.</p><?php endif; ?>
<?php if (!$staffMode && $ready): ?>
<details class="community-compose"><summary><i class="fa fa-plus"></i> Buat Pengaduan</summary>
<form method="post" action="<?= site_url('pengaduan/kirim') ?>"><?= csrf_field() ?>
<label for="complaint-title">Judul</label><input id="complaint-title" name="title" maxlength="180" required>
<label for="complaint-location">Lokasi</label><input id="complaint-location" name="location" maxlength="255">
<label for="complaint-body">Isi pengaduan</label><textarea id="complaint-body" name="body" rows="6" minlength="10" maxlength="5000" required></textarea>
<button class="community-button"><i class="fa fa-paper-plane"></i> Kirim Pengaduan</button>
</form></details><?php endif; ?>
<div class="community-list">
<?php if (!$items && $ready): ?><p class="community-empty">Belum ada pengaduan.</p><?php endif; ?>
<?php foreach ($items as $item): ?><article class="community-item">
<div class="community-meta"><time><?= e(tanggal_id($item['created_at'],true)) ?></time><span class="community-status"><?= e(warga_complaint_status($item['status'])) ?></span></div>
<h2><a href="<?= site_url('pengaduan/'.$item['id']) ?>"><?= e($item['title']) ?></a></h2>
<?php if ($canManage): ?><p><?= e($item['citizen_name']) ?></p><?php endif; ?>
<p><?= e(mb_strimwidth($item['body'],0,180,'...')) ?></p>
<a class="community-text-link" href="<?= site_url('pengaduan/'.$item['id']) ?>">Lihat Pengaduan <i class="fa fa-arrow-right"></i></a></article><?php endforeach; ?>
</div></div>

