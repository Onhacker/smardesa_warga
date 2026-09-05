<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$products = isset($products) && is_array($products) ? $products : array();
$categories = isset($categories) && is_array($categories) ? $categories : array();
$canManage = !empty($canManage);
$marketplaceReady = isset($marketplaceReady) ? (bool) $marketplaceReady : TRUE;
$listingFilters = isset($listing['filters']) && is_array($listing['filters']) ? $listing['filters'] : array();
$search = (string) ($listingFilters['q'] ?? '');
$selectedCategory = (string) ($listingFilters['category_id'] ?? '');
$selectedSort = (string) ($listingFilters['sort'] ?? 'newest');
$listingPages = max(1, (int) ($listing['pages'] ?? 1));
$listingPage = max(1, (int) ($listing['page'] ?? 1));

$marketUrl = static function ($value, $fallback = '') {
    $value = trim((string) $value);
    if ($value === '') return $fallback;
    if (preg_match('#^(?:https?:)?//#i', $value) || strpos($value, 'data:') === 0) return $value;
    if ($value[0] === '/') return base_url(ltrim($value, '/'));
    return base_url($value);
};
$productImage = static function (array $item) use ($marketUrl) {
    $candidate = $item['cover_url'] ?? ($item['image_url'] ?? ($item['cover_image'] ?? ($item['image'] ?? '')));
    if ($candidate === '' && !empty($item['images']) && is_array($item['images'])) {
        $first = reset($item['images']);
        $candidate = is_array($first) ? ($first['url'] ?? ($first['image_url'] ?? ($first['path'] ?? ''))) : $first;
    }
    return $marketUrl($candidate, base_url('assets/images/market-product-placeholder.svg'));
};
$productPrice = static function ($value) {
    $value = is_numeric($value) ? (float) $value : 0;
    return 'Rp ' . number_format($value, 0, ',', '.');
};
$productCategory = static function (array $item) {
    return trim((string) ($item['category_name'] ?? ($item['category'] ?? ($item['category_label'] ?? 'Produk warga'))));
};
$productId = static function (array $item) {
    return (string) ($item['id'] ?? ($item['product_id'] ?? ''));
};
?>

<div class="marketplace-page marketplace-listing-page">
    <section class="market-hero" aria-labelledby="market-title">
        <div class="market-hero-copy">
            <p class="market-eyebrow color-white">EKONOMI <?= e($institutionUpper ?? 'KAMPUNG') ?></p>
            <h1 id="market-title">Pasar Digital</h1>
            <span class="color-white">Temukan produk warga dan dukung usaha lokal.</span>
        </div>
        <span class="market-hero-icon color-white" aria-hidden="true"><i class="fa fa-store color-white"></i></span>
    </section>

    <section class="market-quick-actions" aria-label="Menu Pasar Digital">
        <?php if ($canManage): ?>
            <a href="<?= site_url('pasar/buat') ?>" class="market-action market-action-primary"><i class="fa fa-plus color-white" aria-hidden="true"></i><span class="color-white">Jual produk</span></a>
            <a href="<?= site_url('pasar/toko') ?>" class="market-action"><i class="fa fa-store-alt" aria-hidden="true"></i><span>Identitas toko</span></a>
        <?php else: ?>
            <span class="market-action market-action-note"><i class="fa fa-hand-holding-heart" aria-hidden="true"></i><span>Belanja dari warga <?= e($institutionLower ?? 'kampung') ?></span></span>
        <?php endif; ?>
    </section>

    <section class="card card-style market-filter-card" aria-labelledby="market-filter-title">
        <div class="content mb-0">
            <div class="market-section-heading">
                <div><p class="market-eyebrow market-eyebrow-blue">KATALOG PRODUK</p><h2 id="market-filter-title">Cari produk</h2></div>
                <span class="market-product-count" data-market-count><?= count($products) ?> produk</span>
            </div>
            <form method="get" action="<?= site_url('pasar') ?>" class="market-filter-form" data-market-filter>
                <label class="market-field market-field-search" for="market-search">
                    <span>Nama produk</span>
                    <span class="market-input-wrap"><i class="fa fa-search" aria-hidden="true"></i><input type="search" id="market-search" name="q" value="<?= e($search) ?>" placeholder="Cari produk warga" autocomplete="off"></span>
                </label>
                <label class="market-field" for="market-category">
                    <span>Kategori</span>
                    <span class="market-input-wrap"><i class="fa fa-tags" aria-hidden="true"></i><select id="market-category" name="category_id"><option value="">Semua kategori</option><?php foreach ($categories as $category): ?><?php $categoryValue = (string) ($category['id'] ?? ''); ?><option value="<?= e($categoryValue) ?>" <?= $selectedCategory === $categoryValue ? 'selected' : '' ?>><?= e($category['name'] ?? ($category['label'] ?? $categoryValue)) ?></option><?php endforeach; ?></select></span>
                </label>
                <label class="market-field" for="market-sort">
                    <span>Urutkan</span>
                    <span class="market-input-wrap"><i class="fa fa-sort-amount-down" aria-hidden="true"></i><select id="market-sort" name="sort"><option value="newest" <?= $selectedSort === 'newest' ? 'selected' : '' ?>>Terbaru</option><option value="price_low" <?= $selectedSort === 'price_low' ? 'selected' : '' ?>>Harga terendah</option><option value="price_high" <?= $selectedSort === 'price_high' ? 'selected' : '' ?>>Harga tertinggi</option><option value="name" <?= $selectedSort === 'name' ? 'selected' : '' ?>>Nama A–Z</option></select></span>
                </label>
                <button type="submit" class="market-filter-submit"><i class="fa fa-search color-white" aria-hidden="true"></i><span class="color-white">Cari</span></button>
            </form>
        </div>
    </section>

    <section class="market-products" aria-labelledby="market-products-title">
        <div class="market-section-heading market-products-heading">
            <div><p class="market-eyebrow market-eyebrow-blue">PILIHAN WARGA</p><h2 id="market-products-title">Produk terbaru</h2></div>
            <span class="market-result-note"><?= count($products) ? 'Temukan yang Anda butuhkan' : 'Katalog sedang diperbarui' ?></span>
        </div>
        <?php if ($products): ?>
            <div class="market-product-list" data-market-product-list>
                <?php foreach ($products as $product): ?>
                    <?php if (!is_array($product)) continue; $id = $productId($product); if ($id === '') continue; $category = $productCategory($product); $storeName = trim((string) ($product['store_name'] ?? ($product['seller_name'] ?? ''))); ?>
                    <a class="market-product-row" href="<?= site_url('pasar/produk/' . rawurlencode($id)) ?>" data-market-product data-name="<?= e(strtolower((string) ($product['name'] ?? ''))) ?>" data-category="<?= e(strtolower($category)) ?>">
                        <figure class="market-product-media"><img src="<?= e($productImage($product)) ?>" alt="<?= e($product['name'] ?? 'Produk warga') ?>" loading="lazy"><span class="market-product-badge color-white"><?= e($category) ?></span></figure>
                        <span class="market-product-copy">
                            <span class="market-product-stars" aria-label="Produk warga"><i class="fa fa-star" aria-hidden="true"></i><i class="fa fa-star" aria-hidden="true"></i><i class="fa fa-star" aria-hidden="true"></i><i class="fa fa-star" aria-hidden="true"></i><i class="fa fa-star" aria-hidden="true"></i></span>
                            <strong class="market-product-name"><?= e($product['name'] ?? 'Produk warga') ?></strong>
                            <small class="market-product-store"><i class="fa fa-store" aria-hidden="true"></i><?= e($storeName !== '' ? $storeName : 'Toko warga') ?></small>
                            <b class="market-product-price"><?= e($productPrice($product['price'] ?? 0)) ?></b>
                            <?php if (!empty($product['stock']) || isset($product['stock'])): ?><small class="market-product-stock <?= isset($product['stock']) && (int) $product['stock'] < 1 ? 'is-empty' : '' ?>"><?= isset($product['stock']) && (int) $product['stock'] < 1 ? 'Stok habis' : 'Tersedia' ?></small><?php endif; ?>
                        </span>
                        <i class="fa fa-chevron-right market-product-arrow" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="market-empty-state"><span class="market-empty-icon"><i class="fa <?= $marketplaceReady ? 'fa-store-slash' : 'fa-database' ?>" aria-hidden="true"></i></span><h3><?= $marketplaceReady ? 'Belum ada produk' : 'Pasar Digital belum siap' ?></h3><p><?= $marketplaceReady ? 'Produk warga akan tampil di sini setelah diterbitkan.' : 'Jalankan database/migrations/016_marketplace.sql di server untuk mengaktifkan katalog.' ?></p><?php if ($canManage && $marketplaceReady): ?><a href="<?= site_url('pasar/buat') ?>" class="btn btn-s bg-blue-dark color-white rounded-s"><i class="fa fa-plus color-white" aria-hidden="true"></i> <span class="color-white">Tambah produk</span></a><?php endif; ?></div>
        <?php endif; ?>
        <?php if ($listingPages > 1): ?>
            <nav class="market-pagination" aria-label="Halaman produk">
                <?php for ($page = 1; $page <= $listingPages; $page++): ?>
                    <?php $params = array('page' => $page); if ($search !== '') $params['q'] = $search; if ($selectedCategory !== '') $params['category_id'] = $selectedCategory; if ($selectedSort !== '') $params['sort'] = $selectedSort; ?>
                    <a href="<?= e(site_url('pasar') . '?' . http_build_query($params)) ?>" class="<?= $page === $listingPage ? 'is-active' : '' ?>" aria-label="Halaman <?= $page ?>" <?= $page === $listingPage ? 'aria-current="page"' : '' ?>><?= $page ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    </section>
</div>
