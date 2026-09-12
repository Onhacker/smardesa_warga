<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-notification-center" id="warga-notification-center" data-notification-center hidden aria-hidden="true">
    <button type="button" class="warga-notification-center-backdrop" data-notification-center-close aria-label="Tutup pemberitahuan"></button>
    <section class="warga-notification-center-dialog" role="dialog" aria-modal="true" aria-labelledby="warga-notification-center-title" aria-describedby="warga-notification-center-description">
        <header class="warga-notification-center-head">
            <span class="warga-notification-center-head-icon" aria-hidden="true"><i class="fa fa-inbox"></i></span>
            <div>
                <p>PEMBERITAHUAN BARU</p>
                <h2 id="warga-notification-center-title">Belum dibaca</h2>
                <small id="warga-notification-center-description" data-notification-center-description>Memuat pemberitahuan terbaru…</small>
            </div>
            <button type="button" class="warga-notification-center-close" data-notification-center-close aria-label="Tutup"><i class="fa fa-times" aria-hidden="true"></i></button>
        </header>
        <div class="warga-notification-center-results" data-notification-center-results aria-live="polite" aria-busy="false">
            <div class="warga-notification-center-loading" aria-hidden="true"><i></i><i></i><i></i></div>
        </div>
        <p class="warga-notification-center-error" data-notification-center-error role="alert" hidden>Pemberitahuan belum dapat dimuat. <button type="button" data-notification-center-retry>Coba lagi</button></p>
        <footer class="warga-notification-center-footer">
            <a href="<?= site_url('notifikasi') ?>">Lihat semua pemberitahuan <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
        </footer>
    </section>
</div>
