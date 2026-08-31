<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $selectedService = $this->input->get('layanan', TRUE) ?: old('service_type'); ?>
<section class="warga-page-intro is-form">
    <div><p>PERMOHONAN BARU</p><h1>Ajukan Surat</h1><span>Lengkapi data dan berkas permohonan.</span></div>
    <span class="warga-intro-icon"><i class="fa fa-file-signature"></i></span>
</section>

<form method="post" action="<?= site_url('permohonan/simpan') ?>" enctype="multipart/form-data" class="warga-request-form" data-request-form data-services="<?= warga_json($services) ?>" data-disable-submit>
    <?= csrf_field() ?>
    <section class="card card-style warga-form-card"><div class="content">
        <div class="warga-form-title"><span>1</span><div><h2>Jenis Layanan</h2><p>Pilih surat yang akan diajukan.</p></div></div>
        <div class="input-style has-borders no-icon input-style-always-active mb-0"><label for="service-type" class="color-highlight">Jenis Surat</label><select name="service_type" id="service-type" required data-service-select><option value="">Pilih jenis surat</option><?php foreach ($services as $service): ?><option value="<?= e($service['slug']) ?>" <?= (string) $selectedService === (string) $service['slug'] ? 'selected' : '' ?>><?= e($service['name']) ?></option><?php endforeach; ?></select><span><i class="fa fa-chevron-down"></i></span><i class="fa fa-check disabled valid color-green-dark"></i><i class="fa fa-times disabled invalid color-red-dark"></i><em>*</em></div>
        <div class="warga-service-requirements d-none" data-service-requirements><div class="warga-requirement-head"><i class="fa fa-clipboard-check"></i><strong>Dokumen yang diperlukan</strong></div><ul data-requirement-list></ul></div>
    </div></section>

    <section class="card card-style warga-form-card"><div class="content">
        <div class="warga-form-title"><span>2</span><div><h2>Keperluan</h2><p>Isi tujuan penggunaan surat.</p></div></div>
        <div class="input-style has-borders no-icon input-style-always-active mb-3"><textarea name="purpose" id="request-purpose" rows="4" minlength="5" maxlength="500" required placeholder="Contoh: Persyaratan administrasi sekolah"><?= e(old('purpose')) ?></textarea><label for="request-purpose" class="color-highlight">Keperluan Surat</label><em>*</em></div>
        <div class="input-style has-borders no-icon input-style-always-active mb-0"><textarea name="note" id="request-note" rows="3" maxlength="1000" placeholder="Catatan tambahan jika diperlukan"><?= e(old('note')) ?></textarea><label for="request-note" class="color-highlight">Catatan Tambahan</label></div>
    </div></section>

    <section class="card card-style warga-form-card"><div class="content">
        <div class="warga-form-title"><span>3</span><div><h2>Berkas Pendukung</h2><p>Unggah foto atau PDF yang jelas.</p></div></div>
        <label for="supporting-files" class="warga-upload-zone"><i class="fa fa-cloud-upload-alt"></i><strong>Pilih Berkas</strong><span>JPG, PNG, atau PDF · maksimal 5 MB per berkas</span></label>
        <input type="file" id="supporting-files" name="supporting_files[]" class="d-none" accept="image/jpeg,image/png,application/pdf" multiple data-file-input>
        <div class="warga-file-list" data-file-list><span>Belum ada berkas dipilih.</span></div>
    </div></section>

    <section class="warga-submit-panel"><label class="warga-consent"><input type="checkbox" required><span>Saya memastikan data dan berkas yang dikirim benar.</span></label><button type="submit" class="btn btn-full btn-l bg-teal-dark color-white rounded-s font-600"><span>Kirim Permohonan</span><i class="fa fa-paper-plane ms-2"></i></button></section>
</form>
