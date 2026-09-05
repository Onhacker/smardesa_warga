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
$footerBrand = trim('Smart ' . $footerInstitution . ($footerVillageName !== '' ? ' ' . $footerVillageName : ''));
if ($footerBrand === 'Smart Desa') $footerBrand = 'SmartDesa Warga';

$footerAddress = trim((string) ($footerContact['address'] ?? ''));
if ($footerAddress === '') $footerAddress = 'Alamat kantor belum tersedia';

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
        <p class="warga-site-footer-address"><i class="fa fa-map-marker-alt" aria-hidden="true"></i><span><?= e($footerAddress) ?></span></p>

        <?php if ($footerActions): ?>
            <div class="warga-site-footer-actions" aria-label="Kontak <?= e($footerBrand) ?>">
                <?php foreach ($footerActions as $action): ?>
                    <a class="warga-site-footer-action <?= e($action['class']) ?>" href="<?= e($action['href']) ?>" aria-label="<?= e($action['label']) ?>"<?= !empty($action['external']) ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><i class="<?= e($action['icon']) ?>" aria-hidden="true"></i></a>
                <?php endforeach; ?>
                <a class="warga-site-footer-action is-top back-to-top" href="#page" aria-label="Kembali ke atas"><i class="fa fa-arrow-up" aria-hidden="true"></i></a>
            </div>
        <?php else: ?>
            <div class="warga-site-footer-actions" aria-label="Navigasi halaman">
                <a class="warga-site-footer-action is-top back-to-top" href="#page" aria-label="Kembali ke atas"><i class="fa fa-arrow-up" aria-hidden="true"></i></a>
            </div>
        <?php endif; ?>
    </div>
    <div class="warga-site-footer-divider" aria-hidden="true"></div>
    <nav class="warga-site-footer-links" aria-label="Dokumen aplikasi">
        <a href="<?= site_url('kebijakan-privasi') ?>">Kebijakan Privasi</a>
        <a href="<?= site_url('syarat-ketentuan') ?>">Syarat &amp; Ketentuan</a>
    </nav>
</footer>
