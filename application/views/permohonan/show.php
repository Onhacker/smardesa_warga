<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $formRows = warga_request_form_rows($request); $fileLabels = warga_request_file_labels($request); $requestIcon = warga_request_service_icon($request); ?>
<div class="warga-request-detail">
<section class="warga-detail-head">
    <span class="warga-detail-icon <?= e($requestIcon['class']) ?>"><i class="<?= e($requestIcon['icon']) ?>" aria-hidden="true"></i></span>
    <div><p><?= e($request['request_code']) ?></p><h1><?= e($request['service_name']) ?></h1><?= warga_status_label($request['status']) ?></div>
</section>

<section class="card card-style warga-detail-card"><div class="content mb-2">
    <div class="warga-detail-row"><span>Tanggal pengajuan</span><strong><?= e(tanggal_id($request['submitted_at'], TRUE)) ?></strong></div>
    <div class="warga-detail-row"><span>Keperluan</span><strong><?= e($request['purpose']) ?></strong></div>
    <?php if (!empty($request['note'])): ?><div class="warga-detail-row"><span>Catatan</span><strong><?= e($request['note']) ?></strong></div><?php endif; ?>
    <?php if (!empty($request['local_reference'])): ?><div class="warga-detail-row"><span>Nomor surat</span><strong><?= e($request['local_reference']) ?></strong></div><?php endif; ?>
    <?php if (!empty($request['documents'])): ?><div class="warga-detail-row"><span>Berkas dikirim</span><strong><?= e(count($request['documents'])) ?> berkas</strong></div><?php endif; ?>
    <?php if ((string) $request['status'] === 'revision'): ?><div class="warga-detail-action-row"><a href="<?= site_url('permohonan/' . rawurlencode($request['id']) . '/perbaiki') ?>" class="btn btn-m bg-teal-dark color-white rounded-s"><i class="fa fa-edit"></i><span>Perbaiki Permohonan</span></a></div><?php endif; ?>
</div></section>

<?php if (!empty($formRows)): ?>
<section class="card card-style warga-detail-card"><div class="content mb-2">
    <div class="warga-form-title"><span><i class="fa fa-list-alt"></i></span><div><h2>Data Tambahan</h2><p>Data yang diisi mengikuti formulir layanan <?= e($institutionLower) ?>.</p></div></div>
    <div class="warga-form-data-grid">
        <?php foreach ($formRows as $formRow): ?>
            <div class="warga-form-data-item"><span><?= e($formRow['label']) ?></span><strong><?= e($formRow['value']) ?></strong></div>
        <?php endforeach; ?>
    </div>
</div></section>
<?php endif; ?>

<?php if (!empty($request['documents'])): ?>
<section class="card card-style warga-detail-card"><div class="content mb-2">
    <div class="warga-form-title"><span><i class="fa fa-paperclip"></i></span><div><h2>Berkas yang Dikirim</h2><p>Daftar berkas pendukung pada permohonan ini.</p></div></div>
    <div class="warga-request-document-list">
        <?php foreach ($request['documents'] as $document): ?>
            <?php $fieldKey = isset($document['field_key']) ? (string) $document['field_key'] : ''; $fileLabel = $fieldKey !== '' && isset($fileLabels[$fieldKey]) ? $fileLabels[$fieldKey] : 'Berkas pendukung'; ?>
            <div class="warga-request-document"><i class="fa fa-file-alt"></i><div><strong><?= e($fileLabel) ?></strong><span><?= e(isset($document['original_name']) ? $document['original_name'] : 'Berkas') ?></span></div></div>
        <?php endforeach; ?>
    </div>
</div></section>
<?php endif; ?>

<?php if ($request['status'] === 'issued'): ?>
<section class="warga-result-band"><span><i class="fa fa-check"></i></span><div><strong>Surat telah diterbitkan</strong><p>Surat resmi mengikuti tampilan surat <?= e($institutionLower) ?> dan siap dilihat di sini.</p></div><?php if (!empty($request['official_html_available'])): ?><div class="warga-result-actions"><button type="button" class="btn btn-s bg-green-dark color-white rounded-s" data-warga-letter-open data-html-url="<?= e(site_url('permohonan/' . rawurlencode($request['id']) . '/surat-html')) ?>" data-html-name="<?= e('surat-' . $request['local_reference'] . '.html') ?>"><i class="fa fa-eye"></i><span>Lihat Surat</span></button></div><?php else: ?><div class="warga-result-pending"><i class="fa fa-info-circle"></i> Tampilan surat sedang disiapkan oleh <?= e($institutionLower) ?>.</div><?php endif; ?></section>
<div id="warga-letter-modal" class="warga-letter-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="warga-letter-modal-title">
    <button type="button" class="warga-letter-modal-backdrop" data-warga-letter-close aria-label="Tutup surat"></button>
    <section class="warga-letter-modal-panel" role="document">
        <header class="warga-letter-modal-header"><div><span>Surat Resmi</span><h2 id="warga-letter-modal-title"><?= e($request['service_name']) ?></h2></div><button type="button" class="warga-letter-icon-button" data-warga-letter-close aria-label="Tutup surat"><i class="fa fa-times"></i></button></header>
        <div class="warga-letter-modal-frame-wrap"><div class="warga-letter-modal-status" data-warga-letter-status role="status">Memuat surat...</div><iframe class="warga-letter-modal-frame" data-warga-letter-frame title="Pratinjau surat resmi" sandbox="" hidden></iframe></div>
        <footer class="warga-letter-modal-footer"><button type="button" class="btn btn-s warga-letter-secondary" data-warga-letter-close><i class="fa fa-times"></i><span>Tutup</span></button><button type="button" class="btn btn-s bg-green-dark color-white" data-warga-letter-download disabled><i class="fa fa-download"></i><span>Unduh Surat</span></button></footer>
    </section>
</div>
<?php endif; ?>

<section class="content warga-section-head mt-4"><div><p class="font-600 color-highlight mb-n1">Perjalanan layanan</p><h2 class="font-22 mb-0">Status Permohonan</h2></div></section>
<?php
$timelineIcons = array(
    'draft' => 'fa-pencil-alt',
    'submitted' => 'fa-paper-plane',
    'verified' => 'fa-user-check',
    'approved' => 'fa-check-double',
    'issued' => 'fa-file-download',
    'revision' => 'fa-edit',
    'rejected' => 'fa-times',
    'syncing' => 'fa-sync-alt',
    'synced' => 'fa-check-circle'
);
?>
<section class="warga-timeline">
    <?php foreach ($history as $index => $item): ?>
        <?php
        $timelineStatus = isset($item['status']) ? (string) $item['status'] : (isset($item['to_status']) ? (string) $item['to_status'] : 'submitted');
        $timelineStatusKey = strtolower(trim($timelineStatus));
        $timelineStatusClass = preg_replace('/[^a-z0-9_-]/i', '-', $timelineStatusKey);
        if ($timelineStatusClass === '') $timelineStatusClass = 'submitted';
        $timelineIcon = isset($timelineIcons[$timelineStatusKey]) ? $timelineIcons[$timelineStatusKey] : 'fa-clock';
        $timelineCurrent = $index + 1 === count($history);
        ?>
        <article class="warga-timeline-item is-<?= e($timelineStatusClass) ?> <?= $timelineCurrent ? 'is-current' : '' ?>"<?= $timelineCurrent ? ' aria-current="step"' : '' ?>>
            <span class="warga-timeline-marker"><i class="fa <?= e($timelineIcon) ?>" aria-hidden="true"></i></span>
            <div class="warga-timeline-item-content"><strong><?= e(warga_replace_institution(isset($item['label']) ? $item['label'] : ucwords(str_replace('_', ' ', $item['to_status'])), $institutionLabel)) ?></strong><time datetime="<?= e(str_replace(' ', 'T', (string) $item['occurred_at'])) ?>"><i class="fa fa-clock" aria-hidden="true"></i><?= e(tanggal_id($item['occurred_at'], TRUE)) ?></time><p><?= e(warga_replace_institution($item['note'], $institutionLabel)) ?></p></div>
        </article>
    <?php endforeach; ?>
</section>

<section class="warga-home-notice"><i class="fa fa-info-circle"></i><div><strong>Pembaruan status</strong><p>Verifikasi Sekretaris <?= e($institutionLabel) ?>, persetujuan Kepala <?= e($institutionLabel) ?>, dan penerbitan surat akan tampil pada halaman ini.</p></div></section>
</div>
