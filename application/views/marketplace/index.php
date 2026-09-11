<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$products = isset($products) && is_array($products) ? $products : array();
$categories = isset($categories) && is_array($categories) ? $categories : array();
$canManage = !empty($canManage);
$marketplaceReady = isset($marketplaceReady) ? (bool) $marketplaceReady : TRUE;
$listingFilters = isset($listing['filters']) && is_array($listing['filters']) ? $listing['filters'] : array();
$search = trim((string) ($listingFilters['q'] ?? ''));
$selectedCategory = (string) ($listingFilters['category_id'] ?? '');
$selectedSort = (string) ($listingFilters['sort'] ?? 'newest');
$selectedCategoryName = 'Semua produk';
foreach ($categories as $categoryOption) {
    if ((string) ($categoryOption['id'] ?? '') === $selectedCategory) {
        $selectedCategoryName = trim((string) ($categoryOption['name'] ?? ($categoryOption['label'] ?? 'Semua produk')));
        break;
    }
}
$listingPages = max(1, (int) ($listing['pages'] ?? 1));
$listingPage = max(1, (int) ($listing['page'] ?? 1));
$listingTotal = max(0, (int) ($listing['total'] ?? count($products)));
$listingPerPage = max(1, (int) ($listing['per_page'] ?? 12));
$ajaxEndpoint = site_url('pasar/data');
$activeRegency = trim((string) ($currentUser['regency_name'] ?? ($footerVillage['regency_name'] ?? '')));
if ($activeRegency === '') $activeRegency = trim((string) (getenv('PUBLIC_REGENCY_NAME') ?: (getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya')));
$activeRegencyUpper = function_exists('mb_strtoupper') ? mb_strtoupper($activeRegency, 'UTF-8') : strtoupper($activeRegency);
?>

<div class="marketplace-page marketplace-listing-page"
     data-market-catalog
     data-market-endpoint="<?= e($ajaxEndpoint) ?>"
     data-market-page="<?= $listingPage ?>"
     data-market-pages="<?= $listingPages ?>"
     data-market-per-page="<?= $listingPerPage ?>"
     data-market-initial-load="1"
     data-market-ready="<?= $marketplaceReady ? '1' : '0' ?>">
    <section class="market-hero" aria-labelledby="market-title">
        <div class="market-hero-copy">
            <p class="market-eyebrow color-white">KABUPATEN <?= e($activeRegencyUpper) ?></p>
            <h1 id="market-title">Pasar Digital</h1>
            <span class="color-white">Temukan produk warga dan dukung usaha lokal.</span>
            <small class="market-hero-count" data-market-count><?= $listingTotal ? $listingTotal . ' produk' : 'Memuat produk…' ?></small>
        </div>
        <span class="market-hero-icon color-white" aria-hidden="true"><i class="fa fa-store color-white"></i></span>
    </section>

    <div class="market-search-modal" id="market-search-modal" data-market-search-modal hidden>
        <button type="button" class="market-search-backdrop" data-market-search-close aria-label="Tutup pencarian"></button>
        <section class="market-search-dialog" role="dialog" aria-modal="true" aria-labelledby="market-search-title">
            <div class="market-search-dialog-head">
                <div>
                    <p class="market-eyebrow market-eyebrow-blue">FILTER &amp; PENCARIAN</p>
                    <h2 id="market-search-title">Temukan produk</h2>
                </div>
                <button type="button" class="market-search-close" data-market-search-close aria-label="Tutup"><i class="fa fa-times" aria-hidden="true"></i></button>
            </div>
            <form method="get" action="<?= site_url('pasar') ?>" data-market-search-form>
                <div class="market-search-filter-grid" aria-label="Filter katalog">
                    <label class="market-field" for="market-category">
                        <span>Kategori</span>
                        <span class="market-input-wrap"><i class="fa fa-tags" aria-hidden="true"></i><select id="market-category" data-market-auto-filter><option value="">Semua kategori</option><?php foreach ($categories as $category): ?><?php $categoryValue = (string) ($category['id'] ?? ''); ?><option value="<?= e($categoryValue) ?>" <?= $selectedCategory === $categoryValue ? 'selected' : '' ?>><?= e($category['name'] ?? ($category['label'] ?? $categoryValue)) ?></option><?php endforeach; ?></select></span>
                    </label>
                    <label class="market-field" for="market-sort">
                        <span>Urutkan</span>
                        <span class="market-input-wrap"><i class="fa fa-sort-amount-down" aria-hidden="true"></i><select id="market-sort" data-market-auto-filter><option value="newest" <?= $selectedSort === 'newest' ? 'selected' : '' ?>>Terbaru</option><option value="price_low" <?= $selectedSort === 'price_low' ? 'selected' : '' ?>>Harga terendah</option><option value="price_high" <?= $selectedSort === 'price_high' ? 'selected' : '' ?>>Harga tertinggi</option><option value="name" <?= $selectedSort === 'name' ? 'selected' : '' ?>>Nama A–Z</option></select></span>
                    </label>
                </div>
                <label class="market-field" for="market-search-input">
                    <span>Nama produk</span>
                    <span class="market-input-wrap market-search-input-wrap"><i class="fa fa-search" aria-hidden="true"></i><input type="search" id="market-search-input" name="q" value="<?= e($search) ?>" placeholder="Contoh: kopi, sayur, kerajinan" autocomplete="off" data-market-search-input></span>
                </label>
                <input type="hidden" name="category_id" value="<?= e($selectedCategory) ?>" data-market-modal-category>
                <input type="hidden" name="sort" value="<?= e($selectedSort) ?>" data-market-modal-sort>
                <div class="market-search-dialog-actions">
                    <button type="button" class="market-search-clear" data-market-search-clear>Reset</button>
                    <button type="submit" class="market-filter-submit"><i class="fa fa-search color-white" aria-hidden="true"></i><span class="color-white">Cari produk</span></button>
                </div>
            </form>
        </section>
    </div>

    <div class="market-filter-status" data-market-filter-status<?= $search === '' ? ' hidden' : '' ?>>
        <span><i class="fa fa-search" aria-hidden="true"></i> Hasil untuk “<strong data-market-query-label><?= e($search) ?></strong>”</span>
        <button type="button" data-market-query-clear>Hapus</button>
    </div>

    <section class="market-products" aria-labelledby="market-products-title">
        <div class="market-section-heading market-products-heading">
            <div><h2 id="market-products-title" data-market-products-title><?= e($selectedCategoryName) ?></h2></div>
            <div class="market-products-heading-actions">
                <span class="market-result-note" data-market-result-note><?= $listingTotal ? 'Temukan yang Anda butuhkan' : 'Katalog sedang diperbarui' ?></span>
                <button type="button" class="market-search-trigger" data-market-search-open aria-label="Cari, filter, dan urutkan produk" aria-haspopup="dialog" aria-controls="market-search-modal">
                    <i class="fa fa-search" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="market-product-grid" data-market-product-list aria-live="polite">
            <?php if ($products): ?>
                <?php $this->load->view('marketplace/product_cards', array('products' => $products, 'eagerFirst' => TRUE)); ?>
            <?php elseif ($marketplaceReady): ?>
                <?php for ($skeletonIndex = 0; $skeletonIndex < 8; $skeletonIndex++): ?>
                    <div class="market-product-skeleton" aria-hidden="true">
                        <div class="market-product-skeleton-media"></div>
                        <div class="market-product-skeleton-copy">
                            <div class="market-product-skeleton-line"></div>
                            <div class="market-product-skeleton-line short"></div>
                            <div class="market-product-skeleton-line short"></div>
                        </div>
                    </div>
                <?php endfor; ?>
            <?php else: ?>
                <?php $this->load->view('marketplace/empty_state', array('marketplaceReady' => $marketplaceReady, 'canManage' => $canManage)); ?>
            <?php endif; ?>
        </div>

        <div class="market-loading-state" data-market-loading hidden role="status" aria-live="polite">
            <span class="market-loading-dots" aria-hidden="true"><i></i><i></i><i></i></span>
            <span>Memuat produk…</span>
        </div>
        <div class="market-infinite-sentinel" data-market-sentinel<?= $listingPage >= $listingPages ? ' hidden' : '' ?> aria-hidden="true">
            <span class="market-sentinel-spinner"></span>
        </div>

        <?php if ($listingPages > 1): ?>
            <nav class="market-pagination" aria-label="Halaman produk" data-market-pagination>
                <?php for ($page = 1; $page <= $listingPages; $page++): ?>
                    <?php $params = array('page' => $page); if ($search !== '') $params['q'] = $search; if ($selectedCategory !== '') $params['category_id'] = $selectedCategory; if ($selectedSort !== '') $params['sort'] = $selectedSort; ?>
                    <a href="<?= e(site_url('pasar') . '?' . http_build_query($params)) ?>" class="<?= $page === $listingPage ? 'is-active' : '' ?>" aria-label="Halaman <?= $page ?>" <?= $page === $listingPage ? 'aria-current="page"' : '' ?> data-market-page-link="<?= $page ?>"><?= $page ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    </section>
    <?php $this->load->view('marketplace/review_modal', array('isAuthenticated' => !empty($isAuthenticated))); ?>
</div>
