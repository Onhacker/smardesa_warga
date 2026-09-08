<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$store = isset($store) && is_array($store) ? $store : array();
$products = isset($products) && is_array($products) ? $products : array();
$marketUrl = static function ($value) {
    $value = trim((string) $value);
    if ($value === '') return base_url('assets/images/market-product-placeholder.svg');
    if (preg_match('#^(?:https?:)?//#i', $value)) return $value;
    return base_url(ltrim($value, '/'));
};
$imageFor = static function (array $product) use ($marketUrl) {
    $images = isset($product['images']) && is_array($product['images']) ? $product['images'] : array();
    $first = $images ? reset($images) : '';
    return $marketUrl(is_array($first) ? ($first['url'] ?? '') : $first);
};
?>
<div class="marketplace-page marketplace-tokoku-page">
    <section class="market-hero market-hero-compact" aria-labelledby="tokoku-title">
        <div class="market-hero-copy">
            <p class="market-eyebrow color-white">RUANG USAHA WARGA</p>
            <h1 id="tokoku-title">Tokoku</h1>
            <span class="color-white">Kelola identitas toko dan produk yang Anda jual.</span>
        </div>
        <span class="market-hero-icon color-white" aria-hidden="true"><i class="fa fa-store color-white"></i></span>
    </section>

    <section class="card card-style market-store-summary-card">
        <div class="content">
            <div class="market-tokoku-store-head">
                <span class="market-tokoku-store-icon"><i class="fa fa-store-alt" aria-hidden="true"></i></span>
                <div>
                    <p class="market-eyebrow market-eyebrow-blue">IDENTITAS TOKO</p>
                    <h2><?= e($store['name'] ?? 'Toko saya') ?></h2>
                    <p><?= e($store['description'] ?? 'Lengkapi identitas toko agar pembeli mudah menghubungi Anda.') ?></p>
                </div>
                <a class="market-tokoku-edit" href="<?= site_url('pasar/toko') ?>" aria-label="Edit identitas toko"><i class="fa fa-pen" aria-hidden="true"></i></a>
            </div>
            <div class="market-tokoku-contact-row">
                <?php if (!empty($store['whatsapp'])): ?><span><i class="fab fa-whatsapp" aria-hidden="true"></i><?= e($store['whatsapp']) ?></span><?php endif; ?>
                <?php if (!empty($store['phone'])): ?><span><i class="fa fa-phone" aria-hidden="true"></i><?= e($store['phone']) ?></span><?php endif; ?>
                <?php if (!empty($store['address'])): ?><span><i class="fa fa-map-marker-alt" aria-hidden="true"></i><?= e($store['address']) ?></span><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="market-tokoku-products" aria-labelledby="tokoku-products-title">
        <div class="market-section-heading">
            <div><p class="market-eyebrow market-eyebrow-blue">ETALASE SAYA</p><h2 id="tokoku-products-title">Produk saya</h2></div>
            <a class="market-tokoku-add" href="<?= site_url('pasar/buat') ?>"><i class="fa fa-plus color-white" aria-hidden="true"></i><span class="color-white">Jual produk</span></a>
        </div>
        <?php if (!$products): ?>
            <div class="market-empty-state"><span class="market-empty-icon"><i class="fa fa-box-open" aria-hidden="true"></i></span><h3>Etalase masih kosong</h3><p>Tambahkan produk pertama Anda agar bisa dilihat warga dari seluruh kampung.</p><a href="<?= site_url('pasar/buat') ?>" class="btn btn-s bg-blue-dark color-white rounded-s"><i class="fa fa-plus color-white" aria-hidden="true"></i><span class="color-white">Tambah produk</span></a></div>
        <?php else: ?>
            <div class="market-product-grid market-tokoku-grid">
                <?php foreach ($products as $product): ?>
                    <?php $id = (string) ($product['id'] ?? ''); if ($id === '') continue; $status = (string) ($product['status'] ?? 'published'); ?>
                    <article class="market-product-card market-tokoku-product-card">
                        <a href="<?= site_url('pasar/produk/' . rawurlencode($id)) ?>" class="market-tokoku-product-link">
                            <figure class="market-product-media"><img src="<?= e($imageFor($product)) ?>" alt="<?= e($product['name'] ?? 'Produk') ?>" loading="lazy"></figure>
                            <div class="market-product-copy"><strong class="market-product-name"><?= e($product['name'] ?? 'Produk') ?></strong><b class="market-product-price"><?= e($product['price_label'] ?? 'Rp 0') ?></b><small class="market-tokoku-status status-<?= e($status) ?>"><?= e($status === 'published' ? 'Tayang' : ($status === 'draft' ? 'Draf' : 'Diarsipkan')) ?></small></div>
                        </a>
                        <div class="market-tokoku-product-actions"><a href="<?= site_url('pasar/produk/' . rawurlencode($id) . '/ubah') ?>"><i class="fa fa-pen" aria-hidden="true"></i> Edit</a><form method="post" action="<?= site_url('pasar/produk/' . rawurlencode($id) . '/hapus') ?>" data-confirm="Produk ini dan seluruh fotonya akan dihapus permanen." data-confirm-title="Hapus produk?" data-confirm-button="Hapus" data-confirm-tone="danger" data-disable-submit><?= csrf_field() ?><button type="submit" class="color-red-dark"><i class="fa fa-trash-alt" aria-hidden="true"></i> Hapus</button></form></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
