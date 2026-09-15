<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $productImageTitle = trim((string) ($productImageTitle ?? 'Foto produk')) ?: 'Foto produk'; ?>
<div class="market-image-viewer-modal" data-market-image-viewer-modal hidden aria-hidden="true">
    <button type="button" class="market-image-viewer-backdrop" data-market-image-viewer-close aria-label="Tutup foto produk"></button>
    <section class="market-image-viewer-dialog" id="market-product-image-dialog" role="dialog" aria-modal="true" aria-labelledby="market-image-viewer-title">
        <header class="market-image-viewer-header">
            <div><span>FOTO PRODUK</span><h2 id="market-image-viewer-title" data-market-image-viewer-title><?= e($productImageTitle) ?></h2></div>
            <button type="button" class="market-image-viewer-close" data-market-image-viewer-close aria-label="Tutup"><i class="fa fa-times" aria-hidden="true"></i></button>
        </header>
        <div class="market-image-viewer-viewport" data-market-image-viewer-viewport>
            <div class="market-image-viewer-status" data-market-image-viewer-status role="status" aria-live="polite">
                <span class="market-image-viewer-status-card">
                    <span class="market-image-viewer-spinner" data-market-image-viewer-spinner aria-hidden="true"></span>
                    <span data-market-image-viewer-status-text>Memuat gambar…</span>
                </span>
            </div>
            <div class="market-image-viewer-stage" data-market-image-viewer-stage hidden>
                <img data-market-image-viewer-image alt="" draggable="false" hidden>
            </div>
            <div class="market-image-viewer-gesture" data-market-image-viewer-gesture hidden aria-label="Cubit untuk memperbesar atau memperkecil gambar"></div>
            <div class="market-image-viewer-zoom" data-market-image-viewer-zoom hidden role="group" aria-label="Kontrol zoom gambar">
                <button type="button" data-market-image-viewer-zoom-out aria-label="Perkecil gambar"><i class="fa fa-minus" aria-hidden="true"></i></button>
                <output data-market-image-viewer-zoom-level aria-live="polite">100%</output>
                <button type="button" data-market-image-viewer-zoom-reset aria-label="Sesuaikan gambar ke layar"><i class="fa fa-expand-arrows-alt" aria-hidden="true"></i></button>
                <button type="button" data-market-image-viewer-zoom-in aria-label="Perbesar gambar"><i class="fa fa-plus" aria-hidden="true"></i></button>
            </div>
            <p class="market-image-viewer-hint" data-market-image-viewer-hint hidden>Cubit atau ketuk dua kali untuk memperbesar.</p>
        </div>
    </section>
</div>
