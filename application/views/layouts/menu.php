<?php defined('BASEPATH') OR exit('No direct script access allowed');
$menuInstitution = trim((string) ($institutionLabel ?? 'Desa'));
if ($menuInstitution === '') $menuInstitution = 'Desa';
$menuIsAuthenticated = !empty($currentUser) && is_array($currentUser);
$menuCanManageMarketplace = !empty($canManageMarketplace);
$menuFooterVillage = isset($footerVillage) && is_array($footerVillage) ? $footerVillage : array();
$menuArea = trim((string) ($menuIsAuthenticated
    ? ($currentUser['village_name'] ?? '')
    : ($menuFooterVillage['name'] ?? (getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya'))));
if ($menuArea === '') $menuArea = trim((string) (getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya')) ?: 'Jayawijaya';
$menuDistrict = trim((string) ($menuIsAuthenticated
    ? ($currentUser['district_name'] ?? '')
    : ($menuFooterVillage['district_name'] ?? '')));
if ($menuDistrict === '') $menuDistrict = trim((string) (getenv('PUBLIC_DISTRICT_NAME') ?: ''));
$menuRegency = trim((string) ($menuIsAuthenticated
    ? ($currentUser['regency_name'] ?? '')
    : ($menuFooterVillage['regency_name'] ?? '')));
if ($menuRegency === '') $menuRegency = trim((string) (getenv('PUBLIC_REGENCY_NAME') ?: ''));
if ($menuRegency === '') $menuRegency = trim((string) (getenv('PUBLIC_AREA_NAME') ?: 'Jayawijaya')) ?: 'Jayawijaya';
?>
<div class="warga-menu-head">
    <div class="warga-menu-brand-row">
        <img src="<?= warga_asset_url('assets/pwa/icon-192.png') ?>" alt="Logo SI DAPULIK" width="56" height="56">
        <div class="warga-menu-brand-copy">
            <h2>SI DAPULIK</h2>
            <div class="warga-menu-location" aria-label="Wilayah layanan">
                <strong><?= e($menuArea) ?></strong>
                <?php if ($menuDistrict !== '' || $menuRegency !== ''): ?><span><?= $menuDistrict !== '' ? e($menuDistrict) : '' ?><?= $menuDistrict !== '' && $menuRegency !== '' ? ' · ' : '' ?><?= $menuRegency !== '' ? e($menuRegency) : '' ?></span><?php endif; ?>
            </div>
        </div>
        <button type="button" class="close-menu warga-menu-close" aria-label="Tutup menu"><i class="fa fa-times" aria-hidden="true"></i></button>
    </div>
</div>

<h6 class="menu-divider mt-4"><?= $staffMode ? 'Pelayanan ' . e($institutionLabel) : 'Layanan' ?></h6>
<div class="list-group list-custom-small list-menu warga-menu-list">
    <?php if ($staffMode): ?>
        <a class="<?= nav_is('petugas') ? 'active-nav' : '' ?>" href="<?= site_url('petugas') ?>"><i class="fa fa-chart-pie warga-menu-icon is-green"></i><span>Ringkasan Layanan</span><i class="fa fa-angle-right"></i></a>
        <a href="<?= site_url('petugas?status=submitted') ?>"><i class="fa fa-clipboard-check warga-menu-icon is-blue"></i><span>Antrean Verifikasi</span><i class="fa fa-angle-right"></i></a>
        <a href="<?= site_url('petugas?status=verified') ?>"><i class="fa fa-user-check warga-menu-icon is-amber"></i><span>Menunggu Persetujuan</span><i class="fa fa-angle-right"></i></a>
        <a href="<?= site_url('petugas?status=issued') ?>"><i class="fa fa-file-pdf warga-menu-icon is-purple"></i><span>Surat Terbit</span><i class="fa fa-angle-right"></i></a>
    <?php else: ?>
        <a class="<?= nav_is('dashboard') ? 'active-nav' : '' ?>" href="<?= site_url('dashboard') ?>"><i class="fa fa-home warga-menu-icon is-green"></i><span>Beranda</span><i class="fa fa-angle-right"></i></a>
        <a class="<?= nav_is('layanan') ? 'active-nav' : '' ?>" href="<?= site_url('layanan') ?>"><i class="fa fa-envelope warga-menu-icon is-teal"></i><span>Surat</span><i class="fa fa-angle-right"></i></a>
        <a class="<?= nav_is('permohonan') && $this->router->fetch_method() === 'create' ? 'active-nav' : '' ?>" href="<?= site_url('layanan') ?>"><i class="fa fa-plus warga-menu-icon is-blue"></i><span>Permohonan Baru</span><i class="fa fa-angle-right"></i></a>
        <a class="<?= nav_is('permohonan') && $this->router->fetch_method() !== 'create' ? 'active-nav' : '' ?>" href="<?= site_url('permohonan') ?>"><i class="fa fa-file-alt warga-menu-icon is-sand"></i><span>Riwayat Permohonan</span><i class="fa fa-angle-right"></i></a>
        <?php if ($menuIsAuthenticated): ?><a class="<?= nav_is('notifications') ? 'active-nav' : '' ?>" href="<?= site_url('notifikasi') ?>"><i class="fa fa-inbox warga-menu-icon is-red"></i><span>Pemberitahuan</span><i class="fa fa-angle-right"></i></a><?php endif; ?>
    <?php endif; ?>
</div>

<h6 class="menu-divider mt-4">Akun</h6>
<div class="list-group list-custom-small list-menu warga-menu-list">
    <a href="<?= site_url('pengumuman') ?>"><i class="fa fa-bullhorn warga-menu-icon is-purple"></i><span>Pengumuman</span><i class="fa fa-angle-right"></i></a>
    <a href="<?= site_url('pengaduan') ?>"><i class="fa fa-comments warga-menu-icon is-amber"></i><span>Pengaduan</span><i class="fa fa-angle-right"></i></a>
    <a class="<?= nav_is('marketplace') ? 'active-nav' : '' ?>" href="<?= site_url('pasar') ?>"><i class="fa fa-store warga-menu-icon is-green"></i><span>Pasar Digital</span><i class="fa fa-angle-right"></i></a>
    <?php if ($menuIsAuthenticated && $menuCanManageMarketplace): ?><a href="<?= site_url('pasar/tokoku') ?>"><i class="fa fa-store-alt warga-menu-icon is-blue"></i><span>Tokoku</span><i class="fa fa-angle-right"></i></a><?php endif; ?>
    <a href="<?= site_url('kontak') ?>"><i class="fa fa-address-book warga-menu-icon is-teal"></i><span>Kontak <?= e($institutionLabel) ?></span><i class="fa fa-angle-right"></i></a>
    <?php if ($staffMode): ?><a href="<?= site_url('notifikasi') ?>"><i class="fa fa-inbox warga-menu-icon is-red"></i><span>Pemberitahuan</span><i class="fa fa-angle-right"></i></a><?php endif; ?>
    <?php if ($menuIsAuthenticated): ?><a class="<?= nav_is('account') ? 'active-nav' : '' ?>" href="<?= site_url('akun') ?>"><i class="fa fa-user warga-menu-icon is-indigo"></i><span>Akun Saya</span><i class="fa fa-angle-right"></i></a><?php else: ?><a href="<?= site_url('login') ?>"><i class="fa fa-sign-in-alt warga-menu-icon is-blue"></i><span>Login</span><i class="fa fa-angle-right"></i></a><?php endif; ?>
    <a href="#" data-toggle-theme class="warga-menu-toggle-row"><i class="fa fa-moon warga-menu-icon is-dark"></i><span>Mode Gelap</span><div class="custom-control small-switch ios-switch warga-menu-toggle-control"><input data-toggle-theme type="checkbox" class="ios-input" id="switch-dark-mode"><label class="custom-control-label" for="switch-dark-mode"></label></div></a>
    <?php if ($menuIsAuthenticated): ?><label class="warga-menu-toggle-row" data-push-toggle-row><i class="fa fa-inbox warga-menu-icon is-red"></i><span>Pemberitahuan</span><span class="custom-control small-switch ios-switch warga-menu-toggle-control"><input data-push-toggle type="checkbox" class="ios-input" id="switch-menu-push-notification" aria-label="Nyalakan pemberitahuan"><span class="custom-control-label" aria-hidden="true"></span></span></label><?php endif; ?>
    <?php if ($menuIsAuthenticated): ?><form method="post" action="<?= site_url('logout') ?>" class="warga-logout-form" data-logout-form>
        <?= csrf_field() ?>
        <button type="submit"><i class="fa fa-sign-out-alt warga-menu-icon is-red"></i><span>Keluar</span><i class="fa fa-angle-right"></i></button>
    </form><?php endif; ?>
</div>

<?php if ($menuIsAuthenticated): ?><div class="warga-menu-user">
    <span class="warga-menu-user-avatar" aria-hidden="true"><?= e(warga_initials($currentUser['name'])) ?></span>
    <div><small>Masuk sebagai</small><strong><?= e($currentUser['name']) ?></strong></div>
</div><?php endif; ?>
