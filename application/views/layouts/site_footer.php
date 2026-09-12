<?php defined('BASEPATH') OR exit('No direct script access allowed');

$footerVillage = isset($footerVillage) && is_array($footerVillage) ? $footerVillage : array();
$footerContact = isset($footerVillage['contact']) && is_array($footerVillage['contact']) ? $footerVillage['contact'] : array();
$footerUser = isset($currentUser) && is_array($currentUser) ? $currentUser : array();
$footerVillageName = trim((string) ($footerVillage['name'] ?? ($footerUser['village_name'] ?? '')));
$footerInstitution = trim((string) ($footerContact['institution'] ?? ($footerVillage['institution'] ?? '')));
$hasExplicitInstitution = $footerInstitution !== '';
$institutionPrefixPattern = '/^(desa|kampung|kelurahan|nagari|gampong)\s+/iu';

// The central tenant keeps the village name separately from the institution
// type.  Remove a repeated prefix so the brand reads, for example, “Smart
// Kampung Araboda”, instead of “Smart Kampung Kampung Araboda”.
if (preg_match($institutionPrefixPattern, $footerVillageName, $prefixMatch)) {
    $nameWithoutPrefix = trim((string) preg_replace($institutionPrefixPattern, '', $footerVillageName, 1));
    if (!$hasExplicitInstitution || (strtolower($footerInstitution) === 'desa' && strtolower($prefixMatch[1]) !== 'desa')) {
        $footerInstitution = function_exists('mb_convert_case')
            ? mb_convert_case($prefixMatch[1], MB_CASE_TITLE, 'UTF-8')
            : ucfirst(strtolower($prefixMatch[1]));
    }
    $footerVillageName = $nameWithoutPrefix !== '' ? $nameWithoutPrefix : $footerVillageName;
}
if ($footerInstitution === '') $footerInstitution = 'Desa';
$footerBrand = trim('SI DAPULIK' . ($footerVillageName !== '' ? ' ' . $footerVillageName : ''));

$footerShareUrl = trim((string) ($shareUrl ?? base_url()));
if (!filter_var($footerShareUrl, FILTER_VALIDATE_URL)) $footerShareUrl = base_url();
$footerShareTitle = trim((string) ($shareTitle ?? ''));
// Keep the message shown in WhatsApp aligned with the identity rendered in
// this footer (which already contains the authenticated tenant when present).
if ($footerBrand !== '') $footerShareTitle = $footerBrand . ' — Layanan Digital Warga';
if ($footerShareTitle === '') $footerShareTitle = $footerBrand;
$footerShareDescription = trim((string) ($shareDescription ?? 'Akses layanan surat, pengumuman, pengaduan, pemberitahuan, dan Pasar Dapulik dalam satu aplikasi.'));
$footerShareMessage = $footerShareTitle . "\n" . $footerShareDescription . "\n\n" . $footerShareUrl;
$footerWhatsappShare = 'https://wa.me/?text=' . rawurlencode($footerShareMessage);
$footerFacebookShare = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($footerShareUrl);
$footerPlayStoreUrl = 'https://play.google.com/store/apps/details?id=id.co.mediaverse.smartkampung';

$footerAddress = trim((string) ($footerContact['address'] ?? ''));

$footerHttpUrl = static function ($value) {
    $value = trim((string) $value);
    if ($value === '') return '';
    if (!preg_match('#^https?://#i', $value)) $value = 'https://' . $value;
    $parts = @parse_url($value);
    if (!is_array($parts) || !in_array(strtolower((string) ($parts['scheme'] ?? '')), array('http', 'https'), true)) return '';
    return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
};
$footerPhone = trim((string) ($footerContact['phone'] ?? ($footerContact['telepon'] ?? $footerContact['no_telp'] ?? '')));
$footerPhoneHref = preg_match('/^\+?[0-9 ()-]{8,25}$/D', $footerPhone)
    ? 'tel:' . preg_replace('/[^+0-9]/', '', $footerPhone) : '';
$footerEmail = trim((string) ($footerContact['email'] ?? ''));
$footerEmailHref = filter_var($footerEmail, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $footerEmail : '';
$footerWebsite = $footerHttpUrl($footerContact['website'] ?? '');

$footerActions = array();
if ($footerPhoneHref !== '') $footerActions[] = array('href' => $footerPhoneHref, 'icon' => 'fa fa-phone', 'class' => 'is-phone', 'label' => 'Telepon kantor');
if ($footerEmailHref !== '') $footerActions[] = array('href' => $footerEmailHref, 'icon' => 'fa fa-envelope', 'class' => 'is-email', 'label' => 'Kirim email');
if ($footerWebsite !== '') $footerActions[] = array('href' => $footerWebsite, 'icon' => 'fa fa-globe', 'class' => 'is-website', 'label' => 'Buka situs kantor', 'external' => true);
foreach (array(
    array('key' => 'facebook', 'icon' => 'fab fa-facebook-f', 'class' => 'is-facebook', 'label' => 'Facebook'),
    array('key' => 'instagram', 'icon' => 'fab fa-instagram', 'class' => 'is-instagram', 'label' => 'Instagram'),
    array('key' => 'youtube', 'icon' => 'fab fa-youtube', 'class' => 'is-youtube', 'label' => 'YouTube'),
    array('key' => 'whatsapp', 'icon' => 'fab fa-whatsapp', 'class' => 'is-whatsapp', 'label' => 'WhatsApp')
) as $social) {
    $socialUrl = $footerHttpUrl($footerContact[$social['key']] ?? '');
    if ($socialUrl !== '') {
        $social['href'] = $socialUrl;
        $social['external'] = true;
        $footerActions[] = $social;
    }
}
?>
<footer class="warga-site-footer" aria-label="Informasi <?= e($footerBrand) ?>">
    <div class="warga-site-footer-main">
        <p class="warga-site-footer-kicker">Layanan digital warga</p>
        <h2><?= e($footerBrand) ?></h2>
        <?php if ($footerAddress !== ''): ?>
            <p class="warga-site-footer-address"><i class="fa fa-map-marker-alt" aria-hidden="true"></i><span><?= e($footerAddress) ?></span></p>
        <?php endif; ?>

        <div class="warga-site-footer-actions" aria-label="<?= $footerActions ? 'Kontak dan navigasi ' . e($footerBrand) : 'Navigasi halaman' ?>">
            <?php foreach ($footerActions as $action): ?>
                <a class="warga-site-footer-action <?= e($action['class']) ?>" href="<?= e($action['href']) ?>" aria-label="<?= e($action['label']) ?>"<?= !empty($action['external']) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><i class="<?= e($action['icon']) ?>" aria-hidden="true"></i></a>
            <?php endforeach; ?>
            <button type="button" class="warga-site-footer-action is-share" data-footer-share-open aria-controls="warga-footer-share-dialog" aria-haspopup="dialog" aria-label="Bagikan aplikasi"><i class="fa fa-share-alt" aria-hidden="true"></i></button>
            <a class="warga-site-footer-action is-top back-to-top" href="#page" aria-label="Kembali ke atas"><i class="fa fa-arrow-up" aria-hidden="true"></i></a>
        </div>

        <section class="warga-footer-install" data-footer-install-panel aria-labelledby="warga-footer-install-title">
            <div class="warga-footer-install-copy">
                <strong id="warga-footer-install-title">Pasang aplikasi warga</strong>
                <span>Unduh untuk Android atau tambahkan ke Layar Utama iPhone.</span>
            </div>
            <div class="warga-footer-install-actions">
                <a href="<?= e($footerPlayStoreUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="Download SI DAPULIK di Google Play"><img src="<?= warga_asset_url('assets/pwa/google-play.webp') ?>" width="600" height="169" loading="lazy" alt="Download di Google Play"></a>
                <button type="button" data-footer-ios-install aria-controls="warga-footer-ios-dialog" aria-haspopup="dialog" aria-label="Instal SI DAPULIK di iOS"><img src="<?= warga_asset_url('assets/pwa/install-ios.webp') ?>" width="600" height="168" loading="lazy" alt="Instal PWA di iOS"></button>
            </div>
        </section>
    </div>
    <div class="warga-site-footer-divider" aria-hidden="true"></div>
    <nav class="warga-site-footer-links" aria-label="Dokumen aplikasi">
        <a href="<?= site_url('kebijakan-privasi') ?>">Kebijakan Privasi</a>
        <a href="<?= site_url('syarat-ketentuan') ?>">Syarat &amp; Ketentuan</a>
    </nav>
</footer>

<div class="warga-footer-modal" id="warga-footer-share-dialog" data-footer-modal hidden aria-hidden="true">
    <button type="button" class="warga-footer-modal-backdrop" data-footer-modal-close tabindex="-1" aria-label="Tutup pilihan berbagi"></button>
    <section class="warga-footer-modal-panel" role="dialog" aria-modal="true" aria-labelledby="warga-footer-share-heading">
        <button type="button" class="warga-footer-modal-close" data-footer-modal-close aria-label="Tutup"><i class="fa fa-times" aria-hidden="true"></i></button>
        <span class="warga-footer-modal-icon is-share" aria-hidden="true"><i class="fa fa-share-alt"></i></span>
        <h2 id="warga-footer-share-heading">Bagikan aplikasi</h2>
        <p>Ajak warga lain menggunakan <?= e($footerBrand) ?>.</p>
        <div class="warga-footer-modal-actions">
            <a class="warga-site-footer-share-button is-whatsapp" href="<?= e($footerWhatsappShare) ?>" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp" aria-hidden="true"></i><span>Bagikan ke WhatsApp</span></a>
            <a class="warga-site-footer-share-button is-facebook" href="<?= e($footerFacebookShare) ?>" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f" aria-hidden="true"></i><span>Bagikan ke Facebook</span></a>
        </div>
    </section>
</div>

<div class="warga-footer-modal" id="warga-footer-ios-dialog" data-footer-modal hidden aria-hidden="true">
    <button type="button" class="warga-footer-modal-backdrop" data-footer-modal-close tabindex="-1" aria-label="Tutup petunjuk instalasi"></button>
    <section class="warga-footer-modal-panel" role="dialog" aria-modal="true" aria-labelledby="warga-footer-ios-heading">
        <button type="button" class="warga-footer-modal-close" data-footer-modal-close aria-label="Tutup"><i class="fa fa-times" aria-hidden="true"></i></button>
        <span class="warga-footer-modal-icon is-ios" aria-hidden="true"><i class="fab fa-apple"></i></span>
        <h2 id="warga-footer-ios-heading">Instal di iPhone atau iPad</h2>
        <p class="warga-footer-ios-note" data-footer-ios-note>Buka halaman ini menggunakan Safari, lalu ikuti langkah berikut.</p>
        <ol class="warga-footer-install-steps">
            <li><span aria-hidden="true">1</span>Ketuk ikon <strong>Bagikan</strong> di Safari.</li>
            <li><span aria-hidden="true">2</span>Pilih <strong>Tambahkan ke Layar Utama</strong>.</li>
            <li><span aria-hidden="true">3</span>Ketuk <strong>Tambah</strong>.</li>
        </ol>
        <button type="button" class="warga-footer-modal-done" data-footer-modal-close>Mengerti</button>
    </section>
</div>
