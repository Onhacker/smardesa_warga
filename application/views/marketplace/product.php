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
    return $marketUrl($image, warga_asset_url('assets/images/market-product-placeholder.svg'));
};
$fallbackImage = warga_asset_url('assets/images/market-product-placeholder.svg');
$cover = $product['cover_url'] ?? ($product['image_url'] ?? ($product['cover_image'] ?? ($product['image'] ?? '')));
if ($cover !== '') array_unshift($images, $cover);
$galleryImages = array();
foreach ($images as $image) {
    $url = $imageUrl($image);
    $thumb = is_array($image) ? $imageUrl($image['thumbnail_url'] ?? $url, $url) : $url;
    if ($url !== '' && !isset($galleryImages[$url])) $galleryImages[$url] = array('full' => $url, 'thumb' => $thumb !== '' ? $thumb : $url);
}
if (!$galleryImages) $galleryImages[$fallbackImage] = array('full' => $fallbackImage, 'thumb' => $fallbackImage);
$galleryImages = array_values($galleryImages);
$price = is_numeric($product['price'] ?? null) ? (float) $product['price'] : 0;
$priceLabel = 'Rp ' . number_format($price, 0, ',', '.');
$category = trim((string) ($product['category_name'] ?? ($product['category'] ?? ($product['category_label'] ?? 'Produk warga'))));
$storeName = trim((string) ($product['store_name'] ?? ($store['name'] ?? ($product['seller_name'] ?? 'Toko warga'))));
$storeId = trim((string) ($product['store_id'] ?? ($store['id'] ?? '')));
$storeUrl = $storeId !== '' ? site_url('pasar/toko/' . rawurlencode($storeId)) : '';
$villageName = trim((string) ($product['village_name'] ?? ''));
$description = trim((string) ($product['description'] ?? ($product['body'] ?? '')));
$normalizeContactDigits = static function ($value) {
    $digits = preg_replace('/[^0-9]/', '', trim((string) $value));
    if (strpos($digits, '0') === 0) $digits = '62' . substr($digits, 1);
    return preg_match('/^[0-9]{8,15}$/D', (string) $digits) ? (string) $digits : '';
};
$whatsappRaw = trim((string) ($product['whatsapp'] ?? ($product['store_whatsapp'] ?? ($store['whatsapp'] ?? ''))));
$telephoneRaw = trim((string) ($product['phone'] ?? ($product['store_phone'] ?? ($store['phone'] ?? ''))));
$whatsappDigits = $normalizeContactDigits($whatsappRaw);
$telephoneDigits = $normalizeContactDigits($telephoneRaw);
// A WhatsApp number remains a valid telephone fallback when the seller did
// not provide a second telephone number.
if ($telephoneDigits === '') $telephoneDigits = $whatsappDigits;
$waText = rawurlencode('Halo, saya tertarik dengan produk ' . ($product['name'] ?? 'ini') . '. Apakah masih tersedia?');
$waHref = $whatsappDigits !== '' ? 'https://wa.me/' . $whatsappDigits . '?text=' . $waText : '';
$waCallHref = $whatsappDigits !== '' ? 'whatsapp://call?phone=' . $whatsappDigits : '';
$telHref = $telephoneDigits !== '' ? 'tel:+' . $telephoneDigits : '';
$hasContact = $waHref !== '' || $telHref !== '';
$ratingAverage = max(0, min(5, (float) ($product['rating_average'] ?? 0)));
$ratingCount = max(0, (int) ($product['rating_count'] ?? 0));
$ratingRounded = $ratingCount > 0 ? (int) round($ratingAverage) : 0;
$reviews = isset($product['reviews']) && is_array($product['reviews']) ? $product['reviews'] : array();
$productId = (string) ($product['id'] ?? '');
?>

<div class="marketplace-page marketplace-product-page">
    <section class="market-product-gallery" data-market-gallery aria-label="Foto <?= e($product['name'] ?? 'produk') ?>">
        <figure class="market-product-hero-image"><img src="<?= e($galleryImages[0]['full']) ?>" alt="<?= e($product['name'] ?? 'Produk warga') ?>" data-market-gallery-main></figure>
        <?php if (count($galleryImages) > 1): ?><div class="market-product-thumbs" role="list" aria-label="Galeri produk">
            <?php foreach ($galleryImages as $index => $image): ?><button type="button" class="market-product-thumb <?= $index === 0 ? 'is-active' : '' ?>" data-market-gallery-thumb data-image="<?= e($image['full']) ?>" aria-label="Lihat foto <?= $index + 1 ?>"><img src="<?= e($image['thumb']) ?>" alt="" loading="lazy"></button><?php endforeach; ?>
        </div><?php endif; ?>
    </section>

    <section class="card card-style market-product-info-card" aria-labelledby="market-product-title">
        <div class="content">
            <span class="market-product-category"><i class="fa fa-tag" aria-hidden="true"></i><?= e($category) ?></span>
            <h1 id="market-product-title"><?= e($product['name'] ?? 'Produk warga') ?></h1>
            <p class="market-product-detail-description"><?= e($description !== '' ? $description : 'Produk pilihan warga dari ' . ($institutionLower ?? 'kampung') . '.') ?></p>
            <div class="market-product-detail-meta">
                <strong><?= e($priceLabel) ?></strong>
                <?php if ($storeName !== '' || $villageName !== ''): ?><div class="market-product-seller-meta">
                    <?php if ($storeName !== ''): ?><span class="market-product-store-row">
                        <?php if ($storeUrl !== ''): ?><a class="market-product-seller-store" href="<?= e($storeUrl) ?>" aria-label="Buka toko <?= e($storeName) ?>"><i class="fa fa-store" aria-hidden="true"></i><strong><?= e($storeName) ?></strong></a><?php else: ?><span class="market-product-seller-store"><i class="fa fa-store" aria-hidden="true"></i><strong><?= e($storeName) ?></strong></span><?php endif; ?>
                        <?php if ($storeUrl !== ''): ?><a class="market-product-store-inline-link" href="<?= e($storeUrl) ?>">Lihat toko <i class="fa fa-arrow-right" aria-hidden="true"></i></a><?php endif; ?>
                    </span><?php endif; ?>
                    <?php if ($villageName !== ''): ?><span class="market-product-seller-village"><i class="fa fa-map-marker-alt" aria-hidden="true"></i><?= e($villageName) ?></span><?php endif; ?>
                </div><?php endif; ?>
            </div>
            <?php if (isset($product['stock'])): ?><span class="market-stock-pill <?= (int) $product['stock'] < 1 ? 'is-empty' : '' ?>"><i class="fa fa-box" aria-hidden="true"></i><?= (int) $product['stock'] < 1 ? 'Stok habis' : 'Stok tersedia' ?></span><?php endif; ?>
            <button type="button" class="market-contact-trigger" data-market-contact-open aria-haspopup="dialog" aria-controls="market-contact-dialog"><i class="fa fa-phone-alt color-white" aria-hidden="true"></i><span class="color-white">Hubungi</span><i class="fa fa-chevron-right color-white" aria-hidden="true"></i></button>
            <?php if (!$hasContact): ?><p class="market-contact-note"><i class="fa fa-info-circle" aria-hidden="true"></i>Nomor kontak penjual belum tersedia.</p><?php endif; ?>
        </div>
    </section>

    <section class="card card-style market-review-card" data-market-review-summary data-review-product-id="<?= e($productId) ?>" aria-labelledby="market-review-summary-title">
        <div class="content">
            <p class="market-eyebrow market-eyebrow-blue">ULASAN PEMBELI</p>
            <h2 id="market-review-summary-title">Ulasan produk</h2>
            <div class="market-review-summary-row">
                <div class="market-review-score">
                    <strong data-market-rating-average><?= e(number_format($ratingAverage, 1, ',', '.')) ?></strong>
                    <span> dari 5</span>
                    <span class="market-rating-stars market-review-summary-stars" data-market-rating-stars aria-label="<?= e(number_format($ratingAverage, 1, ',', '.') . ' dari 5 bintang') ?>"><?php for ($star = 1; $star <= 5; $star++): ?><i class="fa fa-star <?= $star <= $ratingRounded ? 'is-filled' : 'is-empty' ?>" aria-hidden="true"></i><?php endfor; ?></span>
                    <small data-market-rating-count><?= $ratingCount ? e($ratingCount . ' ulasan') : 'Belum ada ulasan' ?></small>
                </div>
                <button type="button" class="market-review-open-button" data-market-review-open data-review-url="<?= e(site_url('pasar/produk/' . rawurlencode($productId) . '/rating')) ?>" data-review-product-id="<?= e($productId) ?>" data-review-product-name="<?= e($product['name'] ?? 'Produk warga') ?>"><i class="fa fa-star" aria-hidden="true"></i><span>Beri rating</span></button>
            </div>
            <div class="market-review-list" data-market-review-list>
                <?php if ($reviews): ?>
                    <?php foreach ($reviews as $review): ?>
                        <?php $this->load->view('marketplace/review_item', array('review' => $review)); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="market-review-empty" data-market-review-empty>Belum ada ulasan. Jadilah warga pertama yang memberi ulasan.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="card card-style market-store-card" aria-labelledby="market-store-title">
        <div class="content">
            <p class="market-eyebrow market-eyebrow-blue">TOKO WARGA</p>
            <h2 id="market-store-title"><?= e($storeName) ?></h2>
            <?php $storeDescription = trim((string) ($product['store_description'] ?? ($store['description'] ?? ''))); $storeAddress = trim((string) ($product['store_address'] ?? ($store['address'] ?? ''))); ?>
            <?php if ($storeDescription !== ''): ?><p><?= e($storeDescription) ?></p><?php endif; ?>
            <?php if ($storeAddress !== ''): ?><span class="market-store-address"><i class="fa fa-map-marker-alt" aria-hidden="true"></i><?= e($storeAddress) ?></span><?php endif; ?>
            <?php if ($storeUrl !== ''): ?><a class="market-view-store-button market-view-store-button-secondary" href="<?= e($storeUrl) ?>"><i class="fa fa-store" aria-hidden="true"></i><span>Lihat semua produk toko ini</span><i class="fa fa-arrow-right" aria-hidden="true"></i></a><?php endif; ?>
        </div>
    </section>
</div>
<?php $this->load->view('marketplace/contact_modal', array(
    'contactStoreName' => $storeName,
    'contactVillageName' => $villageName,
    'contactWhatsappHref' => $waHref,
    'contactWhatsappCallHref' => $waCallHref,
    'contactPhoneHref' => $telHref,
    'contactHeading' => 'Hubungi penjual',
    'contactDescription' => 'Pilih cara yang paling nyaman untuk menghubungi penjual.'
)); ?>
<?php $this->load->view('marketplace/review_modal', array('isAuthenticated' => !empty($isAuthenticated))); ?>
