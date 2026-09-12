<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$announcementArea = trim((string) ($currentUser['village_name'] ?? $item['village_name'] ?? ''));
$announcementInstitutionLabel = trim((string) ($institutionLabel ?? $institutionLower ?? 'Kampung')) ?: 'Kampung';
$announcementArea = trim((string) preg_replace('/^(desa|kampung|kelurahan|nagari|gampong)\s+/iu', '', $announcementArea, 1));
if ($announcementArea === '') $announcementArea = trim((string) (getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya')) ?: 'Jayawijaya';
$announcementScope = trim($announcementInstitutionLabel . ' ' . $announcementArea);
$announcementScopeUpper = function_exists('mb_strtoupper') ? mb_strtoupper($announcementScope, 'UTF-8') : strtoupper($announcementScope);
$announcementDateParts = static function ($value) {
    $timestamp = strtotime((string) $value);
    if (!$timestamp) return array('day' => '--', 'month' => '---');
    $months = array('JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES');
    return array('day' => date('d', $timestamp), 'month' => $months[(int) date('n', $timestamp) - 1]);
};
$announcementDate = $announcementDateParts($item['created_at'] ?? '');
$announcementRoleSlug = strtolower(trim((string) ($item['author_role_slug'] ?? $item['role_slug'] ?? '')));
$announcementAuthor = trim((string) ($item['author_role_name'] ?? $item['author_role'] ?? $item['role_name'] ?? ''));
if ($announcementRoleSlug === 'sekdes') $announcementAuthor = 'Sekretaris ' . $announcementInstitutionLabel;
if ($announcementRoleSlug === 'kepala-desa') $announcementAuthor = 'Kepala ' . $announcementInstitutionLabel;
if ($announcementAuthor !== '' && function_exists('warga_replace_institution')) $announcementAuthor = warga_replace_institution($announcementAuthor, $announcementInstitutionLabel);
if ($announcementAuthor === '') $announcementAuthor = 'Pemerintah ' . $announcementInstitutionLabel;
$nestedAttachment = isset($item['attachment']) && is_array($item['attachment']) ? $item['attachment'] : array();
$attachmentUrl = trim((string) ($nestedAttachment['url'] ?? $item['attachment_url'] ?? ''));
if ($attachmentUrl === '' && !empty($nestedAttachment['id']) && !empty($item['id'])) {
    $attachmentUrl = site_url('pengumuman/' . rawurlencode((string) $item['id']) . '/lampiran');
}
$attachmentMime = strtolower(trim((string) ($nestedAttachment['mime_type'] ?? $nestedAttachment['mime'] ?? $item['attachment_mime_type'] ?? $item['attachment_mime'] ?? '')));
$attachmentName = trim((string) ($nestedAttachment['original_name'] ?? $nestedAttachment['name'] ?? $item['attachment_original_name'] ?? $item['attachment_name'] ?? 'Lampiran pengumuman'));
$attachmentIsImage = strpos($attachmentMime, 'image/') === 0;
?>
<article class="warga-community community-detail community-v22-detail community-v22-announcement-detail">
    <header class="community-v22-detail-hero">
        <div class="community-v22-announcement-date-tile is-detail" aria-label="Tanggal <?= e(tanggal_id($item['created_at'], true)) ?>"><b><?= e($announcementDate['day']) ?></b><small><?= e($announcementDate['month']) ?></small></div>
        <div>
            <p class="community-v22-eyebrow">PENGUMUMAN <?= e($announcementScopeUpper) ?></p>
            <h1><?= e($item['title']) ?></h1>
            <div class="community-meta community-v22-announcement-detail-meta"><span class="community-v22-announcement-card-meta-item"><i class="far fa-calendar-alt" aria-hidden="true"></i><time><?= e(tanggal_id($item['created_at'], true)) ?></time></span><span class="community-v22-announcement-card-meta-item"><i class="fa fa-user-tie" aria-hidden="true"></i><span><?= e($announcementAuthor) ?></span></span></div>
        </div>
    </header>
    <div class="community-v22-detail-body">
        <div class="community-prose"><?= nl2br(e($item['body'])) ?></div>
        <?php if ($attachmentUrl !== ''): ?><button type="button" class="community-v22-attachment-button is-detail" data-announcement-attachment-open data-attachment-url="<?= e($attachmentUrl) ?>" data-attachment-mime="<?= e($attachmentMime) ?>" data-attachment-name="<?= e($attachmentName) ?>"><i class="fa <?= $attachmentIsImage ? 'fa-file-image' : 'fa-file-pdf' ?>" aria-hidden="true"></i><span>Lihat lampiran</span><small><?= e($attachmentName) ?></small></button><?php endif; ?>
        <?php if ($canManage): ?><form method="post" action="<?= site_url('pengumuman/'.$item['id'].'/hapus') ?>" data-confirm="Pengumuman ini akan dihapus secara permanen. Lanjutkan?" data-confirm-title="Hapus pengumuman?" data-confirm-button="Hapus" data-confirm-tone="danger" data-disable-submit><?= csrf_field() ?><button class="community-button bg-red-dark color-white"><i class="fa fa-trash" aria-hidden="true"></i><span>Hapus</span></button></form><?php endif; ?>
    </div>
</article>

<div class="community-v22-attachment-modal" data-announcement-attachment-modal hidden aria-hidden="true">
    <button type="button" class="community-v22-attachment-backdrop" data-announcement-attachment-close aria-label="Tutup lampiran"></button>
    <section class="community-v22-attachment-dialog" role="dialog" aria-modal="true" aria-labelledby="community-v22-attachment-title">
        <header class="community-v22-attachment-dialog-head">
            <div><span class="community-v22-eyebrow">Lampiran pengumuman</span><h2 id="community-v22-attachment-title" data-announcement-attachment-title>Lampiran</h2></div>
            <button type="button" class="community-v22-attachment-close" data-announcement-attachment-close aria-label="Tutup"><i class="fa fa-times" aria-hidden="true"></i></button>
        </header>
        <div class="community-v22-attachment-viewer" data-announcement-attachment-viewer>
            <img data-announcement-attachment-image alt="" hidden>
            <iframe data-announcement-attachment-frame title="Pratinjau lampiran pengumuman" hidden loading="lazy"></iframe>
            <p data-announcement-attachment-error hidden>Lampiran belum dapat ditampilkan. Gunakan tombol buka untuk melihatnya.</p>
        </div>
        <div class="community-v22-attachment-actions"><a class="community-button" data-announcement-attachment-download href="#" target="_blank" rel="noopener noreferrer"><i class="fa fa-download" aria-hidden="true"></i><span>Unduh lampiran</span></a><button type="button" class="community-button is-secondary" data-announcement-attachment-close>Tutup</button></div>
    </section>
</div>
