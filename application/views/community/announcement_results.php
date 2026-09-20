<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$items = isset($items) && is_array($items) ? $items : array();
$announcementArea = (string) ($currentUser['village_name'] ?? '');
$announcementInstitutionLabel = trim((string) ($institutionLabel ?? $institutionLower ?? 'Desa')) ?: 'Desa';
$announcementAreaName = trim((string) preg_replace('/^(desa|kampung|kelurahan|nagari|gampong)\s+/iu', '', trim($announcementArea), 1));
if ($announcementAreaName === '') $announcementAreaName = trim((string) (getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya')) ?: 'Jayawijaya';
$announcementScope = trim($announcementInstitutionLabel . ' ' . $announcementAreaName);
$announcementScopeUpper = function_exists('mb_strtoupper') ? mb_strtoupper($announcementScope, 'UTF-8') : strtoupper($announcementScope);
$hasAnnouncementFilters = $listing['filters']['q'] !== '' || $listing['filters']['date'] !== '';
$announcementDateParts = static function ($value) {
    $timestamp = strtotime((string) $value);
    if (!$timestamp) return array('day' => '--', 'month' => '---');
    $months = array('JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES');
    return array('day' => date('d', $timestamp), 'month' => $months[(int) date('n', $timestamp) - 1]);
};
$announcementRole = static function (array $item) use ($announcementInstitutionLabel) {
    $slug = strtolower(trim((string) ($item['author_role_slug'] ?? $item['role_slug'] ?? '')));
    $label = trim((string) ($item['author_role_name'] ?? $item['author_role'] ?? $item['role_name'] ?? ''));
    if ($slug === 'sekdes') $label = 'Sekretaris ' . $announcementInstitutionLabel;
    if ($slug === 'kepala-desa') $label = 'Kepala ' . $announcementInstitutionLabel;
    if ($label !== '' && function_exists('warga_replace_institution')) $label = warga_replace_institution($label, $announcementInstitutionLabel);
    return $label !== '' ? $label : 'Pemerintah ' . $announcementInstitutionLabel;
};
$announcementAttachment = static function (array $item) {
    $nested = isset($item['attachment']) && is_array($item['attachment']) ? $item['attachment'] : array();
    $url = trim((string) ($nested['url'] ?? $item['attachment_url'] ?? ''));
    if ($url === '' && !empty($nested['id']) && !empty($item['id'])) {
        $url = site_url('pengumuman/' . rawurlencode((string) $item['id']) . '/lampiran');
    }
    if ($url === '') return array();
    $mime = strtolower(trim((string) ($nested['mime_type'] ?? $nested['mime'] ?? $item['attachment_mime_type'] ?? $item['attachment_mime'] ?? '')));
    $name = trim((string) ($nested['original_name'] ?? $nested['name'] ?? $item['attachment_original_name'] ?? $item['attachment_name'] ?? 'Lampiran info'));
    return array('url' => $url, 'mime' => $mime, 'name' => $name, 'is_image' => strpos($mime, 'image/') === 0);
};
?>
<?php if ($hasAnnouncementFilters): ?>
<div class="community-v22-announcement-active-filter">
    <span><i class="fa fa-filter" aria-hidden="true"></i>Filter info sedang aktif</span>
    <a href="<?= e($listUrl) ?>" data-list-reset>Hapus</a>
</div>
<?php endif; ?>

<div class="community-list">
    <?php if (!$items): ?>
    <div class="community-v22-empty-card" role="status">
        <span class="community-v22-empty-icon" aria-hidden="true"><i class="fa <?= $hasAnnouncementFilters ? 'fa-search' : 'fa-bullhorn' ?>"></i></span>
        <span class="community-v22-empty-copy">
            <strong><?= $hasAnnouncementFilters ? 'Info tidak ditemukan' : 'Belum ada info' ?></strong>
            <span><?= $hasAnnouncementFilters ? 'Coba judul atau tanggal yang berbeda.' : 'Informasi terbaru dari ' . e($announcementScope) . ' akan tampil di sini.' ?></span>
        </span>
    </div>
    <?php endif; ?>
    <?php foreach ($items as $item): ?>
    <?php
    $announcementDate = $announcementDateParts($item['created_at'] ?? '');
    $announcementAuthor = $announcementRole($item);
    $attachment = $announcementAttachment($item);
    ?>
    <article class="community-v22-announcement-card">
        <a class="community-v22-announcement-card-head" href="<?= site_url('pengumuman/'.$item['id']) ?>">
            <span class="community-v22-announcement-date-tile" aria-label="Tanggal <?= e(tanggal_id($item['created_at'])) ?>"><b><?= e($announcementDate['day']) ?></b><small class="color-white"><?= e($announcementDate['month']) ?></small></span>
            <span class="community-v22-announcement-card-copy">
                <span class="community-v22-announcement-card-eyebrow">INFO <?= e($announcementScopeUpper) ?></span>
                <strong><?= e($item['title']) ?></strong>
                <span class="community-v22-announcement-card-meta"><span class="community-v22-announcement-card-meta-item"><i class="far fa-calendar-alt" aria-hidden="true"></i><time><?= e(tanggal_id($item['created_at'])) ?></time></span><span class="community-v22-announcement-card-meta-item"><i class="fa fa-user-tie" aria-hidden="true"></i><span><?= e($announcementAuthor) ?></span></span></span>
            </span>
        </a>
        <div class="community-v22-announcement-card-body">
            <p><?= e(mb_strimwidth((string) $item['body'], 0, 220, '...')) ?></p>
            <?php if ($attachment): ?><button type="button" class="community-v22-attachment-button" data-announcement-attachment-open data-attachment-url="<?= e($attachment['url']) ?>" data-attachment-mime="<?= e($attachment['mime']) ?>" data-attachment-name="<?= e($attachment['name']) ?>"><i class="fa <?= $attachment['is_image'] ? 'fa-file-image' : 'fa-file-pdf' ?>" aria-hidden="true"></i><span>Lihat lampiran</span><small><?= e($attachment['name']) ?></small></button><?php endif; ?>
            <a class="community-text-link" href="<?= site_url('pengumuman/'.$item['id']) ?>">Baca selengkapnya <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php $this->load->view('layouts/list_pagination'); ?>
