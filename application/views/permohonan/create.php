<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $selectedService = $this->input->get('layanan', TRUE) ?: old('service_type'); ?>
<div class="warga-request-create">
<section class="warga-page-intro is-form">
    <div><p>PERMOHONAN BARU</p><h1>Ajukan Surat</h1><span>Lengkapi data dan berkas permohonan.</span></div>
    <span class="warga-intro-icon" aria-hidden="true"><i class="fa fa-file-alt"></i></span>
</section>

<?php if (empty($services)): ?>
<section class="card card-style warga-form-card"><div class="content text-center py-4">
    <span class="warga-empty-icon"><i class="fa fa-sync-alt"></i></span>
    <h2 class="font-22 mt-3 mb-2">Layanan Belum Tersedia</h2>
    <p class="color-theme opacity-70 mb-4">Belum ada Master Surat yang dipublikasikan oleh desa. Daftar layanan akan tampil otomatis setelah sinkronisasi berikutnya.</p>
    <a href="<?= site_url('dashboard') ?>" class="btn btn-m bg-teal-dark color-white rounded-s font-600 px-4"><i class="fa fa-chevron-left me-2"></i>Kembali ke Beranda</a>
</div></section>
<?php else: ?>
<form method="post" action="<?= site_url('permohonan/simpan') ?>" enctype="multipart/form-data" class="warga-request-form" data-request-form data-services="<?= warga_json($services) ?>" data-disable-submit>
    <?= csrf_field() ?>
    <p class="warga-request-form-hint"><i class="fa fa-info-circle" aria-hidden="true"></i><span>Kolom bertanda <em>*</em> wajib diisi.</span></p>
    <section class="card card-style warga-form-card" aria-labelledby="request-service-title"><div class="content">
        <div class="warga-form-title"><span aria-hidden="true">1</span><div><h2 id="request-service-title">Jenis Layanan</h2><p>Pilih surat yang akan diajukan.</p></div></div>
        <div class="warga-request-field">
            <label for="service-type">Jenis Surat <em>*</em></label>
            <div class="warga-request-select">
                <select name="service_type" id="service-type" class="form-control" required data-service-select>
                    <option value="">Pilih jenis surat</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?= e($service['slug']) ?>" <?= (string) $selectedService === (string) $service['slug'] ? 'selected' : '' ?>><?= e($service['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fa fa-chevron-down" aria-hidden="true"></i>
            </div>
        </div>
        <div class="warga-service-requirements d-none" data-service-requirements><div class="warga-requirement-head"><i class="fa fa-clipboard-check"></i><strong>Dokumen yang diperlukan</strong></div><ul data-requirement-list></ul></div>
    </div></section>

    <section class="card card-style warga-form-card" aria-labelledby="request-purpose-title"><div class="content">
        <div class="warga-form-title"><span aria-hidden="true">2</span><div><h2 id="request-purpose-title">Keperluan</h2><p>Isi tujuan penggunaan surat.</p></div></div>
        <div class="warga-request-field">
            <label for="request-purpose">Keperluan Surat <em>*</em></label>
            <textarea name="purpose" id="request-purpose" class="form-control" rows="4" minlength="5" maxlength="500" required placeholder="Contoh: Persyaratan administrasi sekolah" aria-describedby="request-purpose-help"><?= e(old('purpose')) ?></textarea>
            <small id="request-purpose-help">Minimal 5 karakter, maksimal 500 karakter.</small>
        </div>
        <div class="warga-request-field">
            <label for="request-note">Catatan Tambahan <small>Opsional</small></label>
            <textarea name="note" id="request-note" class="form-control" rows="3" maxlength="1000" placeholder="Catatan tambahan jika diperlukan"><?= e(old('note')) ?></textarea>
        </div>
    </div></section>

    <section class="card card-style warga-form-card d-none" data-dynamic-form-section aria-labelledby="request-data-title"><div class="content">
        <div class="warga-form-title"><span aria-hidden="true">3</span><div><h2 id="request-data-title">Data Tambahan</h2><p data-dynamic-form-description>Lengkapi isian yang dibutuhkan untuk layanan ini.</p></div></div>
        <div class="warga-dynamic-fields" data-form-fields></div>
    </div></section>

    <section class="card card-style warga-form-card" aria-labelledby="request-files-title"><div class="content">
        <div class="warga-form-title"><span data-supporting-step aria-hidden="true">4</span><div><h2 id="request-files-title">Berkas Pendukung</h2><p>Tambahkan foto atau PDF jika diperlukan.</p></div></div>
        <div class="warga-supporting-upload">
            <label for="supporting-files" class="warga-upload-zone"><i class="fa fa-cloud-upload-alt" aria-hidden="true"></i><strong>Pilih Berkas</strong><span id="supporting-files-help">JPG, PNG, atau PDF · maksimal 5 MB per berkas</span></label>
            <input type="file" id="supporting-files" name="supporting_files[]" class="warga-file-input-native" accept="image/jpeg,image/png,application/pdf" multiple data-file-input aria-describedby="supporting-files-help">
            <div class="warga-file-list" data-file-list aria-live="polite"><span>Belum ada berkas dipilih.</span></div>
        </div>
    </div></section>

    <section class="warga-submit-panel"><label class="warga-consent"><input type="checkbox" required><span>Saya memastikan data dan berkas yang dikirim benar.</span></label><button type="submit" class="btn btn-full btn-l bg-teal-dark color-white rounded-s font-600"><span>Kirim Permohonan</span><i class="fa fa-paper-plane ms-2" aria-hidden="true"></i></button></section>
</form>
<?php endif; ?>
</div>
