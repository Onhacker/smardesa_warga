<?php defined('BASEPATH') OR exit('No direct script access allowed');

$branding = isset($branding) && is_array($branding) ? $branding : array();
$socialBrand = trim((string) ($branding['nama_sistem'] ?? 'SIDAPULIK')) ?: 'SIDAPULIK';
$socialTagline = trim((string) ($branding['tagline'] ?? 'Layanan Digital Warga')) ?: 'Layanan Digital Warga';
$socialTitle = trim((string) ($shareTitle ?? ($socialBrand . ' — ' . $socialTagline)));
$socialDescription = trim((string) ($shareDescription ?? 'Akses layanan surat, info, pengaduan, pemberitahuan, dan Pasar Dapulik dalam satu aplikasi.'));
$socialUrl = trim((string) ($shareUrl ?? base_url()));
$socialImage = trim((string) ($shareImage ?? warga_asset_url('assets/pwa/share-preview.png')));
$socialImageAlt = trim((string) ($shareImageAlt ?? ($socialBrand . ', ' . strtolower($socialTagline))));
$socialImageWidth = max(1, (int) ($shareImageWidth ?? 1200));
$socialImageHeight = max(1, (int) ($shareImageHeight ?? 630));
$socialSiteName = $socialBrand;
$socialAlternateName = $socialBrand;

if ($socialTitle === '') $socialTitle = $socialBrand . ' — ' . $socialTagline;
if ($socialDescription === '') $socialDescription = 'Akses layanan warga dalam satu aplikasi.';
if (!filter_var($socialUrl, FILTER_VALIDATE_URL)) $socialUrl = base_url();
if (!filter_var($socialImage, FILTER_VALIDATE_URL)) $socialImage = warga_asset_url('assets/pwa/share-preview.png');
$socialWebsiteUrl = rtrim((string) base_url(), '/') . '/';
$socialStructuredData = array(
    '@context' => 'https://schema.org',
    '@graph' => array(
        array(
            '@type' => 'WebSite',
            '@id' => $socialWebsiteUrl . '#website',
            'url' => $socialWebsiteUrl,
            'name' => $socialSiteName,
            'alternateName' => $socialAlternateName,
            'description' => $socialDescription,
            'inLanguage' => 'id-ID',
            'publisher' => array('@id' => $socialWebsiteUrl . '#organization')
        ),
        array(
            '@type' => 'Organization',
            '@id' => $socialWebsiteUrl . '#organization',
            'name' => $socialSiteName,
            'alternateName' => $socialAlternateName,
            'url' => $socialWebsiteUrl,
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => warga_asset_url('assets/pwa/icon-512.png')
            )
        )
    )
);
?>
<meta name="description" content="<?= e($socialDescription) ?>">
<meta name="application-name" content="<?= e($socialSiteName) ?>">
<meta name="apple-mobile-web-app-title" content="<?= e($socialSiteName) ?>">
<meta property="og:locale" content="id_ID">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($socialSiteName) ?>">
<meta property="og:title" content="<?= e($socialTitle) ?>">
<meta property="og:description" content="<?= e($socialDescription) ?>">
<meta property="og:url" content="<?= e($socialUrl) ?>">
<link rel="canonical" href="<?= e($socialUrl) ?>">
<meta property="og:image" content="<?= e($socialImage) ?>">
<?php if (strtolower((string) parse_url($socialImage, PHP_URL_SCHEME)) === 'https'): ?><meta property="og:image:secure_url" content="<?= e($socialImage) ?>"><?php endif; ?>
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="<?= $socialImageWidth ?>">
<meta property="og:image:height" content="<?= $socialImageHeight ?>">
<meta property="og:image:alt" content="<?= e($socialImageAlt) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($socialTitle) ?>">
<meta name="twitter:description" content="<?= e($socialDescription) ?>">
<meta name="twitter:image" content="<?= e($socialImage) ?>">
<script type="application/ld+json"><?= json_encode($socialStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
