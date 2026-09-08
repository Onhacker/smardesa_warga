<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="warga-notification-list" aria-label="Daftar notifikasi">
    <?php if (!$notifications): ?>
        <div class="warga-empty-state"><span><i class="fa fa-bell-slash" aria-hidden="true"></i></span><h3><?= $listing['filters']['q'] !== '' || $listing['filters']['date'] !== '' ? 'Notifikasi tidak ditemukan' : 'Belum ada notifikasi' ?></h3><p><?= $listing['filters']['q'] !== '' || $listing['filters']['date'] !== '' ? 'Coba nama surat atau tanggal lainnya.' : 'Pembaruan permohonan Anda akan tampil di sini.' ?></p></div>
    <?php endif; ?>
    <?php foreach ($notifications as $notification): ?>
        <?php $isUnread = empty($notification['read_at']); ?>
        <a href="<?= site_url('notifikasi/buka/'.rawurlencode((string) $notification['id'])) ?>" class="warga-notification-item <?= $isUnread ? 'is-unread' : 'is-read' ?>" data-notification-open data-notification-id="<?= e($notification['id']) ?>" aria-label="<?= e(($isUnread ? 'Belum dibaca: ' : 'Sudah dibaca: ').$notification['title']) ?>">
            <span class="warga-notification-icon surat-icon-teal"><i class="fa fa-bell" aria-hidden="true"></i></span>
            <span><strong><?= e($notification['title']) ?></strong><p><?= e(warga_replace_institution($notification['message'], $institutionLabel)) ?></p><span class="warga-notification-state <?= $isUnread ? 'is-danger' : 'is-success' ?>"><?= $isUnread ? 'Belum dibaca' : 'Sudah dibaca' ?></span><time class="warga-notification-date" datetime="<?= e(str_replace(' ', 'T', $notification['occurred_at'])) ?>"><i class="far fa-calendar-alt" aria-hidden="true"></i><?= e(tanggal_id($notification['occurred_at'], TRUE)) ?></time></span>
            <i class="fa fa-chevron-right" aria-hidden="true"></i>
        </a>
    <?php endforeach; ?>
</section>
<?php $this->load->view('layouts/list_pagination'); ?>
