<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$products = isset($products) && is_array($products) ? $products : array();
$eagerFirst = !empty($eagerFirst);

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
<?php foreach ($products as $index => $product): ?>
    <?php if (!is_array($product)) continue; $id = $productId($product); if ($id === '') continue; $category = $productCategory($product); $storeName = trim((string) ($product['store_name'] ?? ($product['seller_name'] ?? ''))); $villageName = trim((string) ($product['village_name'] ?? '')); $isEager = $eagerFirst && $index === 0; $ratingAverage = max(0, min(5, (float) ($product['rating_average'] ?? 0))); $ratingCount = max(0, (int) ($product['rating_count'] ?? 0)); $ratingRounded = $ratingCount > 0 ? (int) round($ratingAverage) : 0; ?>
    <a class="market-product-card" href="<?= site_url('pasar/produk/' . rawurlencode($id)) ?>" data-market-product data-name="<?= e(strtolower((string) ($product['name'] ?? ''))) ?>" data-category="<?= e(strtolower($category)) ?>">
        <figure class="market-product-media">
            <img src="<?= e($productImage($product)) ?>" alt="<?= e($product['name'] ?? 'Produk warga') ?>" loading="<?= $isEager ? 'eager' : 'lazy' ?>" decoding="async" width="600" height="600"<?= $isEager ? ' fetchpriority="high"' : '' ?>>
            <span class="market-product-badge color-white"><?= e($category) ?></span>
        </figure>
        <span class="market-product-copy">
            <span class="market-product-stars market-product-rating-trigger" role="button" tabindex="0" aria-label="<?= e($ratingCount ? 'Beri rating untuk ' . ($product['name'] ?? 'produk') : 'Beri rating untuk ' . ($product['name'] ?? 'produk')) ?>" data-market-review-open data-review-url="<?= e(site_url('pasar/produk/' . rawurlencode($id) . '/rating')) ?>" data-review-product-id="<?= e($id) ?>" data-review-product-name="<?= e($product['name'] ?? 'Produk warga') ?>">
                <span class="market-rating-stars" data-market-rating-stars aria-hidden="true"><?php for ($star = 1; $star <= 5; $star++): ?><i class="fa fa-star <?= $star <= $ratingRounded ? 'is-filled' : 'is-empty' ?>" aria-hidden="true"></i><?php endfor; ?></span>
                <small class="market-product-rating-count" data-market-rating-count><?= $ratingCount ? e(number_format($ratingAverage, 1, ',', '.') . ' (' . $ratingCount . ')') : 'Beri rating' ?></small>
            </span>
            <strong class="market-product-name"><?= e($product['name'] ?? 'Produk warga') ?></strong>
            <small class="market-product-store"><i class="fa fa-store" aria-hidden="true"></i><?= e($storeName !== '' ? $storeName : 'Toko warga') ?></small>
            <?php if ($villageName !== ''): ?><small class="market-product-village"><i class="fa fa-map-marker-alt" aria-hidden="true"></i><?= e($villageName) ?></small><?php endif; ?>
            <b class="market-product-price"><?= e($productPrice($product['price'] ?? 0)) ?></b>
            <?php if (!empty($product['stock']) || isset($product['stock'])): ?><small class="market-product-stock <?= isset($product['stock']) && (int) $product['stock'] < 1 ? 'is-empty' : '' ?>"><?= isset($product['stock']) && (int) $product['stock'] < 1 ? 'Stok habis' : 'Tersedia' ?></small><?php endif; ?>
        </span>
    </a>
<?php endforeach; ?>
