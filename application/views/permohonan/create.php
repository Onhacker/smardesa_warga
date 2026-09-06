<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $editMode = !empty($edit_mode) && !empty($request); ?>
<?php $selectedServiceRow = isset($selected_service) && is_array($selected_service) ? $selected_service : array(); ?>
<?php $selectedService = isset($selectedServiceRow['slug']) ? (string) $selectedServiceRow['slug'] : ''; ?>
<?php $selectedServiceIcon = warga_service_icon($selectedServiceRow); ?>
<?php $initialFields = $editMode && isset($request['form_data']) && is_array($request['form_data']) ? $request['form_data'] : array(); ?>
<?php $existingDocuments = $editMode && isset($request['documents']) && is_array($request['documents']) ? $request['documents'] : array(); ?>
<div class="warga-request-create">
<section class="warga-page-intro is-form">
    <div><p><?= $editMode ? 'PERBAIKAN PERMOHONAN' : 'PERMOHONAN BARU' ?></p><h1><?= $editMode ? 'Perbaiki Surat' : 'Ajukan Surat' ?></h1><span><?= $editMode ? 'Perbarui data atau berkas sesuai catatan ' . e($institutionLower) . '.' : 'Lengkapi data dan berkas permohonan.' ?></span></div>
    <span class="warga-intro-icon" aria-hidden="true"><i class="fa fa-file-alt"></i></span>
</section>

<?php if (empty($services)): ?>
<section class="card card-style warga-form-card"><div class="content text-center py-4">
    <span class="warga-empty-icon"><i class="fa fa-sync-alt"></i></span>
    <h2 class="font-22 mt-3 mb-2">Layanan Belum Tersedia</h2>
    <p class="color-theme opacity-70 mb-4">Belum ada Master Surat yang dipublikasikan oleh <?= e($institutionLower) ?>. Daftar layanan akan tampil otomatis setelah sinkronisasi berikutnya.</p>
    <a href="<?= site_url('dashboard') ?>" class="btn btn-m bg-teal-dark color-white rounded-s font-600 px-4"><i class="fa fa-chevron-left me-2"></i>Kembali ke Beranda</a>
</div></section>
<?php else: ?>
<form method="post" action="<?= site_url('permohonan/simpan') ?>" enctype="multipart/form-data" class="warga-request-form" data-request-form data-services="<?= warga_json($services) ?>" data-initial-fields="<?= warga_json($initialFields) ?>" data-existing-documents="<?= warga_json($existingDocuments) ?>" data-edit-mode="<?= $editMode ? '1' : '0' ?>" data-disable-submit>
    <?= csrf_field() ?>
    <?php if ($editMode): ?><input type="hidden" name="request_id" value="<?= e($request['id']) ?>"><?php endif; ?>
    <input type="hidden" name="service_type" value="<?= e($selectedService) ?>" data-service-select>
    <p class="warga-request-form-hint"><i class="fa fa-info-circle" aria-hidden="true"></i><span>Kolom bertanda <em>*</em> wajib diisi.</span></p>
    <section class="card card-style warga-form-card" aria-labelledby="request-service-title"><div class="content">
        <div class="warga-form-title"><span aria-hidden="true">1</span><div><h2 id="request-service-title">Surat yang Diajukan</h2><p>Jenis surat dipilih dari katalog layanan.</p></div></div>
        <div class="warga-service-catalog-item" aria-label="Jenis surat yang dipilih">
            <span class="warga-service-icon <?= e($selectedServiceIcon['class']) ?>"><i class="<?= e($selectedServiceIcon['icon']) ?>" aria-hidden="true"></i></span>
            <span class="warga-service-catalog-copy">
                <strong><?= e(isset($selectedServiceRow['name']) ? $selectedServiceRow['name'] : '') ?></strong>
                <span class="warga-service-description"><?= e(!empty($selectedServiceRow['description']) ? $selectedServiceRow['description'] : 'Layanan administrasi untuk kebutuhan warga.') ?></span>
            </span>
            <i class="fa fa-check-circle warga-service-catalog-action color-green-dark" aria-hidden="true"></i>
        </div>
        <p class="warga-service-availability d-none" data-service-availability role="status"></p>
        <div class="warga-service-requirements d-none" data-service-requirements><div class="warga-requirement-head"><i class="fa fa-clipboard-check"></i><strong>Dokumen yang diperlukan</strong></div><ul data-requirement-list></ul></div>
    </div></section>

    <section class="card card-style warga-form-card" aria-labelledby="request-purpose-title"><div class="content">
        <div class="warga-form-title"><span aria-hidden="true">2</span><div><h2 id="request-purpose-title">Keperluan</h2><p>Isi tujuan penggunaan surat.</p></div></div>
        <div class="warga-request-field">
            <label for="request-purpose">Keperluan Surat <em>*</em></label>
            <textarea name="purpose" id="request-purpose" class="form-control" rows="4" minlength="5" maxlength="500" required placeholder="Contoh: Persyaratan administrasi sekolah" aria-describedby="request-purpose-help"><?= e($editMode ? $request['purpose'] : old('purpose')) ?></textarea>
            <small id="request-purpose-help">Minimal 5 karakter, maksimal 500 karakter.</small>
        </div>
        <div class="warga-request-field">
            <label for="request-note">Catatan Tambahan <small>Opsional</small></label>
            <textarea name="note" id="request-note" class="form-control" rows="3" maxlength="1000" placeholder="Catatan tambahan jika diperlukan"><?= e($editMode ? $request['note'] : old('note')) ?></textarea>
        </div>
    </div></section>

    <section class="card card-style warga-form-card d-none" data-dynamic-form-section aria-labelledby="request-data-title"><div class="content">
        <div class="warga-form-title"><span aria-hidden="true">3</span><div><h2 id="request-data-title">Data Tambahan</h2><p data-dynamic-form-description>Lengkapi isian yang dibutuhkan untuk layanan ini.</p></div></div>
        <div class="warga-dynamic-fields" data-form-fields></div>
    </div></section>

    <section class="card card-style warga-form-card" aria-labelledby="request-files-title"><div class="content">
        <div class="warga-form-title"><span data-supporting-step aria-hidden="true">4</span><div><h2 id="request-files-title">Berkas Pendukung</h2><p>Tambahkan foto atau PDF jika diperlukan.</p></div></div>
        <div class="warga-supporting-upload">
            <?php if ($editMode && !empty($existingDocuments)): ?><div class="warga-existing-files"><strong>Berkas tersimpan</strong><ul><?php foreach ($existingDocuments as $document): ?><li><i class="fa fa-file-alt"></i><?= e($document['original_name']) ?></li><?php endforeach; ?></ul><small>Unggah berkas baru hanya jika ingin mengganti berkas pada isian yang sama.</small></div><?php endif; ?>
            <label for="supporting-files" class="warga-upload-zone"><i class="fa fa-cloud-upload-alt" aria-hidden="true"></i><strong>Pilih Berkas</strong><span id="supporting-files-help">JPG, PNG, atau PDF · maksimal 5 MB per berkas</span></label>
            <input type="file" id="supporting-files" name="supporting_files[]" class="warga-file-input-native" accept="image/jpeg,image/png,application/pdf" multiple data-file-input aria-describedby="supporting-files-help">
            <div class="warga-file-list" data-file-list aria-live="polite"><span>Belum ada berkas dipilih.</span></div>
        </div>
    </div></section>

    <section class="warga-submit-panel"><label class="warga-consent"><input type="checkbox" required><span>Saya memastikan data dan berkas yang dikirim benar.</span></label><button type="submit" class="btn btn-full btn-l bg-teal-dark color-white rounded-s font-600"><span><?= $editMode ? 'Kirim Perbaikan' : 'Kirim Permohonan' ?></span><i class="fa fa-paper-plane ms-2" aria-hidden="true"></i></button></section>
</form>
<?php endif; ?>
</div>
