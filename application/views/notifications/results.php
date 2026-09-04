<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="warga-notification-list" aria-label="Daftar notifikasi">
    <?php if (!$notifications): ?>
        <div class="warga-empty-state"><span><i class="fa fa-bell-slash" aria-hidden="true"></i></span><h3><?= $listing['filters']['q'] !== '' || $listing['filters']['date'] !== '' ? 'Notifikasi tidak ditemukan' : 'Belum ada notifikasi' ?></h3><p><?= $listing['filters']['q'] !== '' || $listing['filters']['date'] !== '' ? 'Coba nama surat atau tanggal lainnya.' : 'Pembaruan permohonan Anda akan tampil di sini.' ?></p></div>
    <?php endif; ?>
    <?php foreach ($notifications as $notification): ?>
        <?php $notificationIcon = warga_request_service_icon($notification); ?>
        <a href="<?= site_url('permohonan/' . rawurlencode($notification['request_id'])) ?>" class="warga-notification-item">
            <span class="warga-notification-icon <?= e($notificationIcon['class']) ?>"><i class="<?= e($notificationIcon['icon']) ?>" aria-hidden="true"></i></span>
            <span><strong><?= e($notification['title']) ?></strong><p><?= e($notification['message']) ?></p><time class="warga-notification-date" datetime="<?= e(str_replace(' ', 'T', $notification['occurred_at'])) ?>"><i class="far fa-calendar-alt" aria-hidden="true"></i><?= e(tanggal_id($notification['occurred_at'], TRUE)) ?></time></span>
            <i class="fa fa-chevron-right" aria-hidden="true"></i>
        </a>
    <?php endforeach; ?>
</section>
<?php $this->load->view('layouts/list_pagination'); ?>
