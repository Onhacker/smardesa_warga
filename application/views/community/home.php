<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-community">
<header class="community-home"><img src="<?= base_url('assets/pwa/icon-192.png') ?>" width="72" height="72" alt="SmartDesa Warga"><div><p><?= e($currentUser['village_name']) ?></p><h1>Halo, <?= e($currentUser['name']) ?></h1></div></header>
<nav class="community-shortcuts" aria-label="Layanan kampung">
<a href="<?= site_url('surat') ?>"><i class="fa fa-envelope"></i><strong>Surat</strong><span><?= (int)$summary['active'] ?> diproses</span></a>
<a href="<?= site_url('pengaduan') ?>"><i class="fa fa-comments"></i><strong>Pengaduan</strong></a>
<a href="<?= site_url('kontak') ?>"><i class="fa fa-address-book"></i><strong>Kontak <?= e($village['institution']) ?></strong></a>
<a href="<?= site_url('notifikasi') ?>"><i class="fa fa-bell"></i><strong>Notifikasi</strong><span data-notification-count></span></a>
</nav>
<div class="community-heading"><h2>Pengumuman Terbaru</h2><a href="<?= site_url('pengumuman') ?>">Semua <i class="fa fa-arrow-right"></i></a></div>
<?php if (!$announcements): ?><p class="community-empty">Belum ada pengumuman.</p><?php endif; ?>
<?php foreach ($announcements as $item): ?><article class="community-item"><time><?= e(tanggal_id($item['created_at'])) ?></time><h2><a href="<?= site_url('pengumuman/'.$item['id']) ?>"><?= e($item['title']) ?></a></h2><p><?= e(mb_strimwidth($item['body'],0,180,'...')) ?></p></article><?php endforeach; ?>
</div>

