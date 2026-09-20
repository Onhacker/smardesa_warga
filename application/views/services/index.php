<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$citizenVerified = !empty($citizenVerified);
?>
<div class="warga-services-page">
    <section class="warga-page-intro warga-services-intro">
        <div><p>PELAYANAN <?= e($institutionUpper) ?></p><h1>Semua Surat</h1><span>Temukan surat administrasi yang Anda perlukan.</span></div>
        <span class="warga-intro-icon"><i class="fa fa-envelope" aria-hidden="true"></i></span>
    </section>

    <section class="warga-service-catalog warga-paged-list" data-paged-list aria-labelledby="warga-catalog-title">
        <div class="warga-service-catalog-head">
            <div><p>KATALOG SURAT</p><h2 id="warga-catalog-title">Pilih Jenis Surat</h2></div>
            <span class="warga-service-total" data-list-total data-list-total-label="surat"><?= (int) $listing['total'] ?> surat</span>
        </div>

        <form method="get" action="<?= e($listUrl) ?>" class="warga-service-search warga-service-catalog-search" data-list-search data-list-live-search aria-label="Pencarian surat">
            <label for="wargaServiceSearch">Cari surat</label>
            <div class="warga-service-search-input">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" id="wargaServiceSearch" name="q" value="<?= e($listing['filters']['q']) ?>" placeholder="Nama atau jenis surat" aria-controls="wargaServiceGrid" autocomplete="off" maxlength="180">
                <a href="<?= e($listUrl) ?>" class="warga-service-search-reset" data-list-reset aria-label="Hapus pencarian"><i class="fa fa-times" aria-hidden="true"></i></a>
            </div>
            <button type="submit" class="visually-hidden">Cari</button>
            <span class="warga-service-count" aria-live="polite">20 surat per halaman</span>
        </form>

        <p class="warga-list-feedback" data-list-feedback role="status" aria-live="polite" aria-atomic="true"></p>
        <div class="warga-list-error" data-list-error role="alert" hidden>
            <span data-list-error-message></span>
            <button type="button" data-list-retry>Coba lagi</button>
            <a href="<?= site_url('login') ?>" data-list-login hidden>Masuk kembali</a>
        </div>

        <div data-list-results id="wargaServiceResults" aria-busy="false">
            <?php $this->load->view('services/results'); ?>
        </div>
    </section>

    <?php if (!$citizenVerified): ?>
        <section class="warga-verification-notice" role="status">
            <i class="fa fa-user-shield" aria-hidden="true"></i>
            <div><strong>Pengajuan belum tersedia</strong><p>Anda tetap dapat melihat katalog. Hubungi operator <?= e($institutionLower) ?> untuk memverifikasi akun sebelum mengajukan surat.</p></div>
        </section>
    <?php endif; ?>
</div>
