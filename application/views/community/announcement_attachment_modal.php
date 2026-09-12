<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div
    class="community-v22-attachment-modal"
    data-announcement-attachment-modal
    data-pdfjs-url="<?= e(warga_asset_url('assets/vendor/pdfjs/pdf.min.mjs')) ?>"
    data-pdfjs-worker-url="<?= e(warga_asset_url('assets/vendor/pdfjs/pdf.worker.min.mjs')) ?>"
    hidden
    aria-hidden="true"
>
    <button type="button" class="community-v22-attachment-backdrop" data-announcement-attachment-close aria-label="Tutup lampiran"></button>
    <section class="community-v22-attachment-dialog" role="dialog" aria-modal="true" aria-labelledby="community-v22-attachment-title">
        <header class="community-v22-attachment-dialog-head">
            <div><span class="community-v22-eyebrow">Lampiran pengumuman</span><h2 id="community-v22-attachment-title" data-announcement-attachment-title>Lampiran</h2></div>
            <button type="button" class="community-v22-attachment-close" data-announcement-attachment-close aria-label="Tutup"><i class="fa fa-times" aria-hidden="true"></i></button>
        </header>
        <div class="community-v22-attachment-viewer" data-announcement-attachment-viewer>
            <img data-announcement-attachment-image alt="" hidden>
            <div class="community-v22-pdf-preview" data-announcement-attachment-pdf hidden>
                <p class="community-v22-pdf-status" data-announcement-attachment-pdf-status role="status"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i><span>Menyiapkan PDF…</span></p>
                <div class="community-v22-pdf-pages" data-announcement-attachment-pdf-pages></div>
            </div>
            <p data-announcement-attachment-error hidden>Lampiran belum dapat ditampilkan. Silakan unduh untuk melihat berkasnya.</p>
        </div>
        <div class="community-v22-attachment-actions">
            <a class="community-button color-white" data-announcement-attachment-download href="#" download><i class="fa fa-download" aria-hidden="true"></i><span>Unduh</span></a>
            <button type="button" class="community-button is-secondary" data-announcement-attachment-close>Tutup</button>
        </div>
    </section>
</div>
