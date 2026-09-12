<?php defined('BASEPATH') OR exit('No direct script access allowed');
$pwaInstitution = trim((string) ($institutionLabel ?? 'Kampung'));
if ($pwaInstitution === '') $pwaInstitution = 'Kampung';
$pwaBrand = 'SI DAPULIK';
?>
<section class="warga-pwa-install" data-pwa-install-panel aria-label="Instal <?= e($pwaBrand) ?>">
    <div class="warga-pwa-install-icon"><i class="fa fa-mobile-alt"></i></div>
    <div class="warga-pwa-install-copy">
        <h3><?= e($pwaBrand) ?></h3>
        <p data-pwa-install-status>Pasang aplikasi pada layar utama perangkat ini.</p>
    </div>
    <button type="button" class="btn btn-s bg-teal-dark color-white rounded-s" data-pwa-install><i class="fa fa-download me-1"></i> Instal</button>
</section>
