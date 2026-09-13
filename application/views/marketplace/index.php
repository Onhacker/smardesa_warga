<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$products = isset($products) && is_array($products) ? $products : array();
$categories = isset($categories) && is_array($categories) ? $categories : array();
$canManage = !empty($canManage);
$marketplaceReady = isset($marketplaceReady) ? (bool) $marketplaceReady : TRUE;
$listingFilters = isset($listing['filters']) && is_array($listing['filters']) ? $listing['filters'] : array();
$search = trim((string) ($listingFilters['q'] ?? ''));
$selectedCategoryValue = (int) ($listingFilters['category_id'] ?? 0);
$selectedCategory = $selectedCategoryValue > 0 ? (string) $selectedCategoryValue : '';
$selectedSort = (string) ($listingFilters['sort'] ?? 'newest');
$categoryName = function ($category) {
    return trim((string) ($category['name'] ?? ($category['label'] ?? '')));
};
$categorySlug = function ($category) {
    return trim((string) ($category['slug'] ?? ''));
};
// Keep the six discovery choices stable and predictable. The complete list
// remains available from the lazy "Semua Kategori" dialog, but the first
// paint always presents the same order on every village catalogue.
$quickCategories = array();
$quickSlugs = array('makanan-minuman', 'kerajinan', 'hasil-tani', 'jasa');
foreach ($quickSlugs as $preferredSlug) {
    foreach ($categories as $categoryOption) {
        $categoryId = (string) ($categoryOption['id'] ?? '');
        if ($categoryId === '' || $categorySlug($categoryOption) !== $preferredSlug) continue;
        $quickCategories[] = $categoryOption;
        break;
    }
}
$typingPhrases = array(
    'Cari makanan & minuman?',
    'Cari kerajinan lokal?',
    'Cari hasil tani?',
    'Cari jasa warga?'
);
foreach (array_slice($products, 0, 4) as $typingProduct) {
    $typingName = trim((string) ($typingProduct['name'] ?? ''));
    if ($typingName === '') continue;
    if (function_exists('mb_substr')) $typingName = mb_substr($typingName, 0, 32, 'UTF-8');
    else $typingName = substr($typingName, 0, 32);
    $typingPhrases[] = 'Cari ' . $typingName . '?';
}
$typingPhrases = array_values(array_unique($typingPhrases));
$typingPhrasesJson = json_encode($typingPhrases, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$listingPages = max(1, (int) ($listing['pages'] ?? 1));
$listingPage = max(1, (int) ($listing['page'] ?? 1));
$listingTotal = max(0, (int) ($listing['total'] ?? count($products)));
$listingPerPage = max(1, (int) ($listing['per_page'] ?? 12));
$ajaxEndpoint = site_url('pasar/data');
$categoryEndpoint = site_url('pasar/kategori');
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
     data-market-category-endpoint="<?= e($categoryEndpoint) ?>"
     data-market-initial-load="1"
     data-market-ready="<?= $marketplaceReady ? '1' : '0' ?>">
    <section class="market-hero" aria-labelledby="market-title">
        <div class="market-hero-copy">
            <p class="market-eyebrow color-white">KABUPATEN <?= e($activeRegencyUpper) ?></p>
            <h1 id="market-title">Pasar Dapulik</h1>
            <span class="color-white">Temukan produk dan dukung usaha lokal.</span>
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

    <!-- The complete category list is fetched only when a visitor asks for it.
         The initial catalogue therefore renders only the six compact choices. -->
    <div class="market-category-modal" id="market-category-modal" data-market-category-modal hidden aria-hidden="true">
        <button type="button" class="market-category-backdrop" data-market-category-close aria-label="Tutup daftar kategori"></button>
        <section class="market-category-dialog" role="dialog" aria-modal="true" aria-labelledby="market-category-title" aria-describedby="market-category-description">
            <header class="market-category-dialog-head">
                <div>
                    <p class="market-eyebrow market-eyebrow-blue">KATEGORI PRODUK</p>
                    <h2 id="market-category-title">Pilih kategori</h2>
                    <small id="market-category-description">Temukan produk sesuai kebutuhan Anda.</small>
                </div>
                <button type="button" class="market-category-close" data-market-category-close aria-label="Tutup"><i class="fa fa-times" aria-hidden="true"></i></button>
            </header>
            <div class="market-category-dialog-scroll">
                <div class="market-category-loading" data-market-category-loading role="status" aria-live="polite" hidden>
                    <span class="market-category-spinner" aria-hidden="true"></span>
                    <span>Memuat kategori…</span>
                </div>
                <div class="market-category-error" data-market-category-error role="alert" hidden>
                    <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                    <span data-market-category-error-message>Kategori belum dapat dimuat.</span>
                    <button type="button" data-market-category-retry>Coba lagi</button>
                </div>
                <div class="market-all-category-list" data-market-category-list role="list" aria-label="Semua kategori" hidden></div>
            </div>
        </section>
    </div>

    <div class="market-filter-status" data-market-filter-status<?= $search === '' ? ' hidden' : '' ?>>
        <span><i class="fa fa-search" aria-hidden="true"></i> Hasil untuk “<strong data-market-query-label><?= e($search) ?></strong>”</span>
        <button type="button" data-market-query-clear>Hapus</button>
    </div>

    <section class="market-products" aria-label="Katalog produk Pasar Dapulik">
        <form class="market-inline-search" data-market-inline-search-form role="search" action="<?= e(site_url('pasar')) ?>" method="get">
            <div class="market-inline-search-composer">
                <label class="market-inline-search-field" for="market-inline-search-input">
                    <i class="fa fa-search" aria-hidden="true"></i>
                    <span class="sr-only">Cari produk</span>
                    <input type="search" id="market-inline-search-input" name="q" value="<?= e($search) ?>" placeholder="Cari produk…" autocomplete="off" data-market-query-field data-market-typing-phrases="<?= e($typingPhrasesJson ?: '[]') ?>">
                </label>
                <button type="button" class="market-inline-search-clear" data-market-inline-query-clear aria-label="Bersihkan pencarian">
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <button type="button" class="market-search-trigger market-inline-filter" data-market-search-open aria-label="Buka filter produk" aria-haspopup="dialog" aria-controls="market-search-modal">
                <i class="fa fa-filter" aria-hidden="true"></i>
            </button>
        </form>

        <div class="market-category-shortcuts" data-market-category-shortcuts role="list" aria-label="Kategori pilihan">
            <button type="button" class="market-category-chip market-category-chip-all-products<?= $selectedCategory === '' ? ' is-active' : '' ?>" data-market-category-quick data-market-category-id="" data-market-category-slug="semua-produk" aria-pressed="<?= $selectedCategory === '' ? 'true' : 'false' ?>">
                <span class="market-category-chip-icon" aria-hidden="true"><i class="fa fa-th-large"></i></span>
                <span class="market-category-chip-label">Semua Produk</span>
            </button>
            <?php foreach ($quickCategories as $quickCategory): ?>
                <?php
                $quickId = (string) ($quickCategory['id'] ?? '');
                $quickSlug = $categorySlug($quickCategory);
                $quickLabel = $categoryName($quickCategory);
                $quickIcon = 'fa-tags';
                if ($quickSlug === 'makanan-minuman') $quickIcon = 'fa-utensils';
                elseif ($quickSlug === 'hasil-tani') $quickIcon = 'fa-leaf';
                elseif ($quickSlug === 'peternakan-perikanan') $quickIcon = 'fa-paw';
                elseif ($quickSlug === 'kerajinan') $quickIcon = 'fa-palette';
                elseif ($quickSlug === 'jasa') $quickIcon = 'fa-wrench';
                ?>
                <button type="button" class="market-category-chip<?= $selectedCategory === $quickId ? ' is-active' : '' ?>" data-market-category-quick data-market-category-id="<?= e($quickId) ?>" data-market-category-slug="<?= e($quickSlug) ?>" aria-pressed="<?= $selectedCategory === $quickId ? 'true' : 'false' ?>">
                    <span class="market-category-chip-icon" aria-hidden="true"><i class="fa <?= e($quickIcon) ?>"></i></span>
                    <span class="market-category-chip-label"><?= e($quickLabel !== '' ? $quickLabel : 'Kategori') ?></span>
                </button>
            <?php endforeach; ?>
            <button type="button" class="market-category-chip market-category-chip-all" data-market-all-categories-open aria-label="Buka semua kategori" aria-haspopup="dialog" aria-controls="market-category-modal">
                <span class="market-category-chip-icon" aria-hidden="true"><i class="fa fa-tags"></i></span>
                <span class="market-category-chip-label">Semua Kategori</span>
            </button>
        </div>

        <div class="market-results-summary" data-market-result-summary>
            <span class="market-result-note" data-market-result-note><?= $listingTotal ? 'Temukan yang Anda butuhkan' : 'Katalog sedang diperbarui' ?></span>
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
