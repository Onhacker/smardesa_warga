<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (!$notifications): ?>
    <div class="warga-notification-center-empty">
        <span aria-hidden="true"><i class="fa fa-check-double"></i></span>
        <h3>Semua sudah dibaca</h3>
        <p>Tidak ada pemberitahuan baru saat ini.</p>
    </div>
<?php else: ?>
    <div class="warga-notification-center-list" aria-label="Pemberitahuan belum dibaca">
        <?php foreach ($notifications as $notification): ?>
            <?php $notificationIcon = warga_notification_icon($notification); ?>
            <?php $notificationTitle = warga_notification_display_text($notification['title']); ?>
            <?php $notificationMessage = warga_notification_display_text(warga_replace_institution($notification['message'], $institutionLabel)); ?>
            <a href="<?= site_url('notifikasi/buka/'.rawurlencode((string) $notification['id'])) ?>"
               class="warga-notification-center-item is-unread"
               data-notification-open
               data-notification-id="<?= e($notification['id']) ?>">
                <span class="warga-notification-type-icon <?= e($notificationIcon['class']) ?>" aria-hidden="true"><i class="<?= e($notificationIcon['icon']) ?>"></i></span>
                <span class="warga-notification-center-copy">
                    <small><?= e($notificationIcon['label']) ?></small>
                    <strong><?= e($notificationTitle) ?></strong>
                    <p><?= e($notificationMessage) ?></p>
                    <time datetime="<?= e(str_replace(' ', 'T', $notification['occurred_at'])) ?>"><i class="far fa-clock" aria-hidden="true"></i><?= e(tanggal_id($notification['occurred_at'], TRUE)) ?></time>
                </span>
                <i class="fa fa-chevron-right" aria-hidden="true"></i>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($listing['pages'] > 1): ?>
    <nav class="warga-notification-center-pagination" aria-label="Halaman pemberitahuan belum dibaca">
        <button type="button" data-notification-center-page="<?= (int) ($listing['page'] - 1) ?>" <?= $listing['page'] <= 1 ? 'disabled' : '' ?> aria-label="Halaman sebelumnya"><i class="fa fa-chevron-left" aria-hidden="true"></i></button>
        <span>Halaman <?= (int) $listing['page'] ?> dari <?= (int) $listing['pages'] ?></span>
        <button type="button" data-notification-center-page="<?= (int) ($listing['page'] + 1) ?>" <?= $listing['page'] >= $listing['pages'] ? 'disabled' : '' ?> aria-label="Halaman berikutnya"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>
    </nav>
<?php endif; ?>
