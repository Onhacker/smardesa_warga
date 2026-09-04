<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $formRows = warga_request_form_rows($request); $fileLabels = warga_request_file_labels($request); ?>
<div class="warga-request-detail">
<section class="warga-detail-head">
    <span class="warga-detail-icon"><i class="fa <?= e($request['service_icon']) ?>"></i></span>
    <div><p><?= e($request['request_code']) ?></p><h1><?= e($request['service_name']) ?></h1><?= warga_status_label($request['status']) ?></div>
</section>

<section class="card card-style warga-detail-card"><div class="content mb-2">
    <div class="warga-detail-row"><span>Tanggal pengajuan</span><strong><?= e(tanggal_id($request['submitted_at'], TRUE)) ?></strong></div>
    <div class="warga-detail-row"><span>Keperluan</span><strong><?= e($request['purpose']) ?></strong></div>
    <?php if (!empty($request['note'])): ?><div class="warga-detail-row"><span>Catatan</span><strong><?= e($request['note']) ?></strong></div><?php endif; ?>
    <?php if (!empty($request['local_reference'])): ?><div class="warga-detail-row"><span>Nomor surat</span><strong><?= e($request['local_reference']) ?></strong></div><?php endif; ?>
    <?php if (!empty($request['documents'])): ?><div class="warga-detail-row"><span>Berkas dikirim</span><strong><?= e(count($request['documents'])) ?> berkas</strong></div><?php endif; ?>
</div></section>

<?php if (!empty($formRows)): ?>
<section class="card card-style warga-detail-card"><div class="content mb-2">
    <div class="warga-form-title"><span><i class="fa fa-list-alt"></i></span><div><h2>Data Tambahan</h2><p>Data yang diisi mengikuti formulir layanan desa.</p></div></div>
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
<section class="warga-result-band"><span><i class="fa fa-check"></i></span><div><strong>Surat telah diterbitkan</strong><p>Surat resmi siap dilihat atau diunduh dalam format PDF.</p></div><?php if (!empty($request['document_path'])): ?><?php $officialDocumentUrl = site_url('permohonan/' . rawurlencode($request['id']) . '/surat'); ?><div class="warga-result-actions"><a href="<?= e($officialDocumentUrl) ?>" target="_blank" rel="noopener" class="btn btn-s bg-green-dark color-white rounded-s"><i class="fa fa-eye"></i><span>Lihat Surat</span></a><a href="<?= e($officialDocumentUrl . '?download=1') ?>" class="btn btn-s warga-result-download rounded-s"><i class="fa fa-download"></i><span>Unduh PDF</span></a></div><?php endif; ?></section>
<?php endif; ?>

<section class="content warga-section-head mt-4"><div><p class="font-600 color-highlight mb-n1">Perjalanan layanan</p><h2 class="font-22 mb-0">Status Permohonan</h2></div></section>
<section class="warga-timeline">
    <?php foreach ($history as $index => $item): ?>
        <article class="warga-timeline-item <?= $index + 1 === count($history) ? 'is-current' : '' ?>">
            <span class="warga-timeline-marker"><i class="fa <?= $item['status'] === 'verified' ? 'fa-user-check' : ($item['status'] === 'issued' ? 'fa-file-download' : 'fa-paper-plane') ?>"></i></span>
            <div><strong><?= e(isset($item['label']) ? $item['label'] : ucwords(str_replace('_', ' ', $item['to_status']))) ?></strong><time><?= e(tanggal_id($item['occurred_at'], TRUE)) ?></time><p><?= e($item['note']) ?></p></div>
        </article>
    <?php endforeach; ?>
</section>

<section class="warga-home-notice"><i class="fa fa-info-circle"></i><div><strong>Pembaruan status</strong><p>Verifikasi Sekdes, persetujuan Kepala Desa, dan penerbitan surat akan tampil pada halaman ini.</p></div></section>
</div>
