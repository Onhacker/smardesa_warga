<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$store = isset($store) && is_array($store) ? $store : array();
$products = isset($products) && is_array($products) ? $products : array();
$listing = isset($listing) && is_array($listing) ? $listing : array();
$storeName = trim((string) ($store['name'] ?? 'Toko warga')) ?: 'Toko warga';
$villageName = trim((string) ($store['village_name'] ?? ''));
$description = trim((string) ($store['description'] ?? ''));
$address = trim((string) ($store['address'] ?? ''));
$productCount = max(0, (int) ($listing['total'] ?? count($products)));
$whatsappUrl = trim((string) ($store['whatsapp_url'] ?? ''));
$phoneUrl = trim((string) ($store['phone_url'] ?? ''));
?>

<div class="marketplace-page marketplace-store-public-page">
    <section class="market-store-public-banner" aria-labelledby="market-store-public-title">
        <div class="market-store-public-head">
            <span class="market-store-public-icon" aria-hidden="true"><i class="fa fa-store-alt"></i></span>
            <div class="market-store-public-copy">
                <p class="market-eyebrow">IDENTITAS TOKO</p>
                <h1 id="market-store-public-title"><?= e($storeName) ?></h1>
                <?php if ($villageName !== ''): ?><span><i class="fa fa-map-marker-alt" aria-hidden="true"></i><?= e($villageName) ?></span><?php endif; ?>
            </div>
        </div>
        <?php if ($description !== ''): ?><p class="market-store-public-description"><?= e($description) ?></p><?php endif; ?>
        <?php if ($address !== ''): ?><p class="market-store-public-address"><i class="fa fa-location-arrow" aria-hidden="true"></i><?= e($address) ?></p><?php endif; ?>
        <?php if ($whatsappUrl !== '' || $phoneUrl !== ''): ?>
            <div class="market-store-public-actions" aria-label="Kontak toko">
                <?php if ($whatsappUrl !== ''): ?><a class="market-store-contact market-store-contact-whatsapp" href="<?= e($whatsappUrl) ?>" target="_blank" rel="noopener"><i class="fab fa-whatsapp" aria-hidden="true"></i><span>Chat WhatsApp</span></a><?php endif; ?>
                <?php if ($phoneUrl !== ''): ?><a class="market-store-contact market-store-contact-phone" href="<?= e($phoneUrl) ?>"><i class="fa fa-phone" aria-hidden="true"></i><span>Telepon</span></a><?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="market-store-public-products" aria-labelledby="market-store-products-title">
        <div class="market-section-heading">
            <div>
                <p class="market-eyebrow market-eyebrow-blue">ETALASE TOKO</p>
                <h2 id="market-store-products-title">Produk <?= e($storeName) ?></h2>
            </div>
            <span class="market-store-public-count"><?= $productCount ?> produk</span>
        </div>

        <?php if ($products): ?>
            <div class="market-product-grid" data-market-product-list>
                <?php $this->load->view('marketplace/product_cards', array('products' => $products, 'eagerFirst' => TRUE)); ?>
            </div>
        <?php else: ?>
            <div class="market-empty-state market-store-public-empty">
                <span class="market-empty-icon"><i class="fa fa-store-slash" aria-hidden="true"></i></span>
                <h3>Belum ada produk aktif</h3>
                <p>Produk toko ini akan tampil setelah diterbitkan oleh pemiliknya.</p>
            </div>
        <?php endif; ?>
    </section>
</div>
