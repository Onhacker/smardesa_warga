<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$announcementArea = (string) ($currentUser['village_name'] ?? '');
$announcementInstitutionLabel = trim((string) ($institutionLabel ?? $institutionLower ?? 'Kampung')) ?: 'Kampung';
$announcementAreaName = trim($announcementArea);
/* A few older tenant snapshots store the institution prefix in village_name.
 * Strip it before composing the public scope so we do not render
 * "Kampung Kampung Araboda". */
$announcementAreaName = trim((string) preg_replace('/^(desa|kampung|kelurahan|nagari|gampong)\s+/iu', '', $announcementAreaName, 1));
if ($announcementAreaName === '') $announcementAreaName = trim((string) (getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya')) ?: 'Jayawijaya';
$announcementScope = trim($announcementInstitutionLabel . ' ' . $announcementAreaName);
$announcementScopeUpper = function_exists('mb_strtoupper') ? mb_strtoupper($announcementScope, 'UTF-8') : strtoupper($announcementScope);
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
    $name = trim((string) ($nested['original_name'] ?? $nested['name'] ?? $item['attachment_original_name'] ?? $item['attachment_name'] ?? 'Lampiran pengumuman'));
    return array('url' => $url, 'mime' => $mime, 'name' => $name, 'is_image' => strpos($mime, 'image/') === 0);
};
?>
<div class="warga-community community-v22-page community-v22-announcement-page">
    <header class="community-heading community-v22-page-hero community-v22-announcement-page-hero" aria-labelledby="announcement-page-title">
        <div class="community-v22-page-hero-icon"><i class="fa fa-bullhorn" aria-hidden="true"></i></div>
        <div>
            <h1 id="announcement-page-title">Pengumuman</h1>
            <p class="community-v22-hero-subtitle">Informasi Terbaru</p>
        </div>
    </header>

    <?php if ($canManage && $ready): ?>
    <details class="community-compose community-v22-compose">
        <summary><i class="fa fa-plus" aria-hidden="true"></i><span>Buat Pengumuman</span><i class="fa fa-chevron-down" aria-hidden="true"></i></summary>
        <form method="post" action="<?= site_url('pengumuman/terbitkan') ?>" enctype="multipart/form-data" data-disable-submit>
            <?= csrf_field() ?>
            <label for="announcement-title">Judul</label>
            <input id="announcement-title" name="title" maxlength="180" required>
            <label for="announcement-body">Isi pengumuman</label>
            <textarea id="announcement-body" name="body" rows="7" minlength="10" maxlength="10000" required></textarea>
            <label for="announcement-attachment">Lampiran (opsional)</label>
            <input type="hidden" name="MAX_FILE_SIZE" value="8388608">
            <input id="announcement-attachment" name="announcement_attachment" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" aria-describedby="announcement-attachment-help">
            <small id="announcement-attachment-help" class="community-v22-file-help">Satu file PDF atau gambar, maksimal 8 MB.</small>
            <button class="community-button community-v22-publish-button color-white" type="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i><span>Terbitkan</span></button>
        </form>
    </details>
    <?php endif; ?>

    <section class="community-v22-feed" aria-label="Daftar pengumuman">
        <div class="community-v22-feed-heading"><h2>Info terbaru</h2><span><?= count($items) ?> kabar</span></div>
        <div class="community-list">
            <?php if (!$items): ?>
            <div class="community-v22-empty-card" role="status">
                <span class="community-v22-empty-icon" aria-hidden="true"><i class="fa fa-bullhorn"></i></span>
                <span class="community-v22-empty-copy">
                    <strong>Belum ada pengumuman</strong>
                    <span>Informasi terbaru dari <?= e($announcementScope) ?> akan tampil di sini.</span>
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
                    <span class="community-v22-announcement-date-tile" aria-label="Tanggal <?= e(tanggal_id($item['created_at'])) ?>"><b><?= e($announcementDate['day']) ?></b><small><?= e($announcementDate['month']) ?></small></span>
                    <span class="community-v22-announcement-card-copy">
                        <span class="community-v22-announcement-card-eyebrow">PENGUMUMAN <?= e($announcementScopeUpper) ?></span>
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
    </section>
</div>

<?php $this->load->view('community/announcement_attachment_modal'); ?>
