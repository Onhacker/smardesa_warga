<?php defined('BASEPATH') OR exit('No direct script access allowed');

$contact = isset($village['contact']) && is_array($village['contact']) ? $village['contact'] : array();
$institution = trim((string) ($institutionLabel ?? ($village['institution'] ?? 'Desa'))) ?: 'Desa';
$contactDistrictLabel = trim((string) ($districtLabel ?? ($village['district_label'] ?? 'Kecamatan'))) ?: 'Kecamatan';
$areaName = trim((string) ($village['name'] ?? ($currentUser['village_name'] ?? '')));

$contactValue = function ($key, $fallback = 'Belum tersedia') use ($contact) {
    $value = isset($contact[$key]) ? trim((string) $contact[$key]) : '';
    return $value !== '' ? $value : $fallback;
};

$phone = $contactValue('phone');
$email = $contactValue('email');
$phoneIsValid = $phone !== 'Belum tersedia' && preg_match('/^\+?[0-9 ()-]{8,25}$/D', $phone);
$emailIsValid = $email !== 'Belum tersedia' && filter_var($email, FILTER_VALIDATE_EMAIL);

$rows = array(
    array('address', 'Alamat kantor', 'fa-school', 'is-office'),
    array('phone', 'Telepon', 'fa-phone', 'is-phone'),
    array('email', 'Email', 'fa-envelope', 'is-email'),
    array('website', 'Website', 'fa-globe', 'is-website'),
    array('office_hours', 'Jam pelayanan', 'fa-clock', 'is-hours'),
    array('district_name', $contactDistrictLabel, 'fa-map-marker-alt', 'is-location'),
    array('regency_name', 'Kabupaten', 'fa-map-marked-alt', 'is-region')
);
?>
<div class="warga-account-page warga-contact-page">
    <section class="warga-account-head" aria-labelledby="warga-contact-title">
        <span class="warga-account-avatar warga-contact-avatar" aria-hidden="true"><i class="fa fa-address-book"></i></span>
        <div>
            <p>KONTAK <?= e(strtoupper($institution)) ?></p>
            <h1 id="warga-contact-title">Kontak <?= e($institution) ?></h1>
            <span class="warga-account-village"><i class="fa fa-map-marker-alt" aria-hidden="true"></i><?= e($areaName !== '' ? $areaName : 'Wilayah ' . strtolower($institution)) ?></span>
        </div>
    </section>

    <section class="card card-style warga-account-card warga-contact-card" aria-labelledby="warga-contact-info-title">
        <div class="content">
            <header class="warga-account-card-title">
                <div>
                    <p>INFORMASI LAYANAN</p>
                    <h2 id="warga-contact-info-title">Data Kontak</h2>
                </div>
                <span aria-hidden="true"><i class="fa fa-address-book"></i></span>
            </header>

            <?php foreach ($rows as $row): ?>
                <?php
                list($key, $label, $icon, $iconClass) = $row;
                if ($key === 'district_name') {
                    $value = trim((string) ($village['district_name'] ?? '')) ?: 'Belum tersedia';
                } elseif ($key === 'regency_name') {
                    $value = trim((string) ($village['regency_name'] ?? '')) ?: 'Belum tersedia';
                } else {
                    $value = $contactValue($key);
                }
                ?>
                <div class="warga-account-row">
                    <span class="warga-account-row-icon <?= e($iconClass) ?>" aria-hidden="true"><i class="fa <?= e($icon) ?>"></i></span>
                    <div>
                        <span><?= e($label) ?></span>
                        <strong>
                            <?php if ($key === 'phone' && $phoneIsValid): ?>
                                <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', $phone)) ?>"><?= e($value) ?></a>
                            <?php elseif ($key === 'email' && $emailIsValid): ?>
                                <a href="mailto:<?= e($email) ?>"><?= e($value) ?></a>
                            <?php else: ?>
                                <?= e($value) ?>
                            <?php endif; ?>
                        </strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
