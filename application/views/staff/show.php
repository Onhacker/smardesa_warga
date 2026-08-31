<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="warga-detail-head is-staff-detail">
    <span class="warga-detail-icon"><i class="fa <?= e($request['service_icon']) ?>"></i></span>
    <div><p><?= e($request['request_code']) ?></p><h1><?= e($request['service_name']) ?></h1><?= warga_status_label($request['status']) ?></div>
</section>

<section class="card card-style warga-detail-card"><div class="content mb-2">
    <div class="warga-form-title"><span><i class="fa fa-user"></i></span><div><h2>Data Pemohon</h2><p>Identitas warga yang mengajukan layanan.</p></div></div>
    <div class="warga-detail-row"><span>Nama warga</span><strong><?= e($request['citizen_name']) ?></strong></div>
    <div class="warga-detail-row"><span>Nomor telepon</span><strong><?= e(!empty($request['citizen_phone']) ? $request['citizen_phone'] : '-') ?></strong></div>
    <div class="warga-detail-row"><span>Desa/Kampung</span><strong><?= e($request['village_name']) ?></strong></div>
    <div class="warga-detail-row"><span>Tanggal pengajuan</span><strong><?= e(tanggal_id($request['submitted_at'], TRUE)) ?></strong></div>
</div></section>

<section class="card card-style warga-detail-card"><div class="content mb-2">
    <div class="warga-form-title"><span><i class="fa fa-file-alt"></i></span><div><h2>Isi Permohonan</h2><p>Tujuan dan catatan yang dikirim warga.</p></div></div>
    <div class="warga-detail-row"><span>Keperluan</span><strong><?= e($request['purpose']) ?></strong></div>
    <div class="warga-detail-row"><span>Catatan warga</span><strong><?= e(!empty($request['note']) ? $request['note'] : '-') ?></strong></div>
</div></section>

<section class="card card-style warga-detail-card"><div class="content mb-2">
    <div class="warga-form-title"><span><i class="fa fa-paperclip"></i></span><div><h2>Berkas Pendukung</h2><p>Berkas hanya dapat diakses petugas pada wilayah ini.</p></div></div>
    <?php if (!$documents): ?><div class="warga-inline-empty"><i class="fa fa-folder-open"></i><span>Tidak ada berkas pendukung.</span></div><?php endif; ?>
    <?php foreach ($documents as $document): ?>
        <?php $documentId = isset($document['id']) ? $document['id'] : ''; ?>
        <div class="warga-document-row"><i class="fa <?= $document['mime_type'] === 'application/pdf' ? 'fa-file-pdf color-red-dark' : 'fa-file-image color-blue-dark' ?>"></i><span><strong><?= e($document['original_name']) ?></strong><small><?= number_format(((int) $document['file_size']) / 1024, 0) ?> KB</small></span><?php if ($documentId !== ''): ?><a href="<?= site_url('petugas/berkas/' . rawurlencode($documentId)) ?>" class="btn btn-xxs bg-teal-dark color-white"><i class="fa fa-download"></i></a><?php endif; ?></div>
    <?php endforeach; ?>
</div></section>

<?php if ($actions): ?>
<form method="post" action="<?= site_url('petugas/permohonan/' . rawurlencode($request['id']) . '/tindakan') ?>" class="card card-style warga-staff-action" data-staff-action-form>
    <?= csrf_field() ?>
    <div class="content mb-2">
        <div class="warga-form-title"><span><i class="fa fa-gavel"></i></span><div><h2>Tindakan Petugas</h2><p>Alasan wajib untuk perbaikan atau penolakan.</p></div></div>
        <div class="input-style has-borders no-icon input-style-always-active mb-3"><textarea name="note" id="staff-note" rows="3" maxlength="1000" placeholder="Tambahkan catatan pemeriksaan" data-staff-note></textarea><label for="staff-note" class="color-highlight">Catatan Petugas</label></div>
        <div class="warga-action-buttons">
            <?php if (in_array('verify', $actions, TRUE)): ?><button type="submit" name="action" value="verify" class="btn bg-teal-dark color-white"><i class="fa fa-check me-2"></i>Verifikasi</button><?php endif; ?>
            <?php if (in_array('approve', $actions, TRUE)): ?><button type="submit" name="action" value="approve" class="btn bg-green-dark color-white"><i class="fa fa-check-circle me-2"></i>Setujui</button><?php endif; ?>
            <?php if (in_array('revision', $actions, TRUE)): ?><button type="submit" name="action" value="revision" class="btn bg-yellow-dark color-white" data-requires-note><i class="fa fa-edit me-2"></i>Perbaikan</button><?php endif; ?>
            <?php if (in_array('reject', $actions, TRUE)): ?><button type="submit" name="action" value="reject" class="btn bg-red-dark color-white" data-requires-note><i class="fa fa-times me-2"></i>Tolak</button><?php endif; ?>
        </div>
    </div>
</form>
<?php else: ?>
<section class="warga-home-notice"><i class="fa fa-info-circle"></i><div><strong>Tidak ada tindakan pada tahap ini</strong><p>Status hanya dapat diubah oleh peran yang berwenang sesuai urutan pelayanan.</p></div></section>
<?php endif; ?>

<section class="content warga-section-head mt-4"><div><p class="font-600 color-highlight mb-n1">JEJAK PROSES</p><h2 class="font-22 mb-0">Riwayat Status</h2></div></section>
<section class="warga-timeline">
    <?php foreach ($history as $index => $item): ?>
        <div class="warga-timeline-item <?= $index === count($history) - 1 ? 'is-current' : '' ?>">
            <span class="warga-timeline-marker"><i class="fa fa-check"></i></span>
            <div><strong><?= e($item['label']) ?></strong><time><?= e(tanggal_id($item['occurred_at'], TRUE)) ?><?= !empty($item['actor_name']) ? ' · ' . e($item['actor_name']) : '' ?></time><p><?= e($item['note']) ?></p></div>
        </div>
    <?php endforeach; ?>
</section>
