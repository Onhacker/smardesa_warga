<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="warga-page-intro"><div><p>PEMBARUAN LAYANAN</p><h1>Notifikasi</h1><span>Status terbaru permohonan Anda.</span></div><span class="warga-intro-icon"><i class="fa fa-bell"></i></span></section>
<section class="warga-notification-list">
    <?php if (!$notifications): ?><div class="warga-empty-state"><span><i class="fa fa-bell-slash"></i></span><h3>Belum ada notifikasi</h3></div><?php endif; ?>
    <?php foreach ($notifications as $notification): ?><a href="<?= site_url('permohonan/' . rawurlencode($notification['request_id'])) ?>" class="warga-notification-item"><span class="warga-notification-icon"><i class="fa <?= $notification['status'] === 'issued' ? 'fa-check' : 'fa-bell' ?>"></i></span><span><strong><?= e($notification['title']) ?></strong><p><?= e($notification['message']) ?></p><time><?= e(tanggal_id($notification['occurred_at'], TRUE)) ?></time></span><i class="fa fa-chevron-right"></i></a><?php endforeach; ?>
</section>
