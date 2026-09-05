<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$product = isset($product) && is_array($product) ? $product : array();
$store = isset($store) && is_array($store) ? $store : (isset($product['store']) && is_array($product['store']) ? $product['store'] : array());
$images = isset($images) && is_array($images) ? $images : (isset($product['images']) && is_array($product['images']) ? $product['images'] : array());
$marketUrl = static function ($value, $fallback = '') {
    $value = trim((string) $value);
    if ($value === '') return $fallback;
    if (preg_match('#^(?:https?:)?//#i', $value) || strpos($value, 'data:') === 0) return $value;
    if ($value[0] === '/') return base_url(ltrim($value, '/'));
    return base_url($value);
};
$imageUrl = static function ($image) use ($marketUrl) {
    if (is_array($image)) $image = $image['url'] ?? ($image['image_url'] ?? ($image['path'] ?? ($image['storage_path'] ?? '')));
    return $marketUrl($image, base_url('assets/images/market-product-placeholder.svg'));
};
$fallbackImage = base_url('assets/images/market-product-placeholder.svg');
$cover = $product['cover_url'] ?? ($product['image_url'] ?? ($product['cover_image'] ?? ($product['image'] ?? '')));
if ($cover !== '') array_unshift($images, $cover);
$uniqueImages = array();
foreach ($images as $image) {
    $url = $imageUrl($image);
    if ($url !== '' && !in_array($url, $uniqueImages, true)) $uniqueImages[] = $url;
}
if (!$uniqueImages) $uniqueImages[] = $fallbackImage;
$price = is_numeric($product['price'] ?? null) ? (float) $product['price'] : 0;
$priceLabel = 'Rp ' . number_format($price, 0, ',', '.');
$category = trim((string) ($product['category_name'] ?? ($product['category'] ?? ($product['category_label'] ?? 'Produk warga'))));
$storeName = trim((string) ($product['store_name'] ?? ($store['name'] ?? ($product['seller_name'] ?? 'Toko warga'))));
$villageName = trim((string) ($product['village_name'] ?? ''));
$description = trim((string) ($product['description'] ?? ($product['body'] ?? '')));
$phoneRaw = trim((string) ($product['whatsapp'] ?? ($product['store_whatsapp'] ?? ($store['whatsapp'] ?? ($product['phone'] ?? ($store['phone'] ?? ''))))));
$phoneDigits = preg_replace('/[^0-9]/', '', $phoneRaw);
if (strpos($phoneDigits, '0') === 0) $phoneDigits = '62' . substr($phoneDigits, 1);
$phoneDigits = trim((string) $phoneDigits);
$waText = rawurlencode('Halo, saya tertarik dengan produk ' . ($product['name'] ?? 'ini') . '. Apakah masih tersedia?');
$waHref = $phoneDigits !== '' ? 'https://wa.me/' . $phoneDigits . '?text=' . $waText : '#';
$telHref = $phoneDigits !== '' ? 'tel:+' . $phoneDigits : '#';
?>

<div class="marketplace-page marketplace-product-page">
    <section class="market-product-gallery" data-market-gallery aria-label="Foto <?= e($product['name'] ?? 'produk') ?>">
        <figure class="market-product-hero-image"><img src="<?= e($uniqueImages[0]) ?>" alt="<?= e($product['name'] ?? 'Produk warga') ?>" data-market-gallery-main></figure>
        <?php if (count($uniqueImages) > 1): ?><div class="market-product-thumbs" role="list" aria-label="Galeri produk">
            <?php foreach ($uniqueImages as $index => $image): ?><button type="button" class="market-product-thumb <?= $index === 0 ? 'is-active' : '' ?>" data-market-gallery-thumb data-image="<?= e($image) ?>" aria-label="Lihat foto <?= $index + 1 ?>"><img src="<?= e($image) ?>" alt="" loading="lazy"></button><?php endforeach; ?>
        </div><?php endif; ?>
    </section>

    <section class="card card-style market-product-info-card" aria-labelledby="market-product-title">
        <div class="content">
            <span class="market-product-category"><i class="fa fa-tag" aria-hidden="true"></i><?= e($category) ?></span>
            <h1 id="market-product-title"><?= e($product['name'] ?? 'Produk warga') ?></h1>
            <p class="market-product-detail-description"><?= e($description !== '' ? $description : 'Produk pilihan warga dari ' . ($institutionLower ?? 'kampung') . '.') ?></p>
            <div class="market-product-detail-meta"><strong><?= e($priceLabel) ?></strong><?php if ($storeName !== ''): ?><span><i class="fa fa-store" aria-hidden="true"></i><?= e($storeName) ?><?php if ($villageName !== ''): ?><br><i class="fa fa-map-marker-alt" aria-hidden="true"></i><?= e($villageName) ?><?php endif; ?></span><?php endif; ?></div>
            <?php if (isset($product['stock'])): ?><span class="market-stock-pill <?= (int) $product['stock'] < 1 ? 'is-empty' : '' ?>"><i class="fa fa-box" aria-hidden="true"></i><?= (int) $product['stock'] < 1 ? 'Stok habis' : 'Stok tersedia' ?></span><?php endif; ?>
            <div class="market-contact-actions" aria-label="Hubungi penjual">
                <a href="<?= e($waHref) ?>" class="market-contact-button market-contact-whatsapp <?= $phoneDigits === '' ? 'is-disabled' : '' ?>" <?= $phoneDigits !== '' ? 'target="_blank" rel="noopener"' : 'aria-disabled="true"' ?>><i class="fab fa-whatsapp color-white" aria-hidden="true"></i><span class="color-white">Chat WhatsApp</span></a>
                <a href="<?= e($telHref) ?>" class="market-contact-button market-contact-call <?= $phoneDigits === '' ? 'is-disabled' : '' ?>" <?= $phoneDigits === '' ? 'aria-disabled="true"' : '' ?>><i class="fa fa-phone color-white" aria-hidden="true"></i><span class="color-white">Telepon WhatsApp</span></a>
            </div>
            <?php if ($phoneDigits === ''): ?><p class="market-contact-note"><i class="fa fa-info-circle" aria-hidden="true"></i>Nomor kontak penjual belum tersedia.</p><?php endif; ?>
        </div>
    </section>

    <section class="card card-style market-store-card" aria-labelledby="market-store-title">
        <div class="content">
            <p class="market-eyebrow market-eyebrow-blue">TOKO WARGA</p>
            <h2 id="market-store-title"><?= e($storeName) ?></h2>
            <?php $storeDescription = trim((string) ($product['store_description'] ?? ($store['description'] ?? ''))); $storeAddress = trim((string) ($product['store_address'] ?? ($store['address'] ?? ''))); ?>
            <?php if ($storeDescription !== ''): ?><p><?= e($storeDescription) ?></p><?php endif; ?>
            <?php if ($storeAddress !== ''): ?><span class="market-store-address"><i class="fa fa-map-marker-alt" aria-hidden="true"></i><?= e($storeAddress) ?></span><?php endif; ?>
        </div>
    </section>
</div>
