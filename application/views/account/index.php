<?php defined('BASEPATH') OR exit('No direct script access allowed');
$accountProfile = isset($accountProfile) && is_array($accountProfile) ? $accountProfile : array();
$profileValue = function ($key, $fallback = 'Belum tersedia') use ($accountProfile) {
    $value = isset($accountProfile[$key]) ? trim((string) $accountProfile[$key]) : '';
    return $value !== '' ? $value : $fallback;
};
$nikFallback = !empty($currentUser['nik']) ? (string) $currentUser['nik'] : '';
$kkFallback = !empty($currentUser['kk']) ? (string) $currentUser['kk'] : (!empty($currentUser['no_kk']) ? (string) $currentUser['no_kk'] : '');
$nik = $profileValue('nik', $nikFallback !== '' ? $nikFallback : 'Belum tersedia');
$kk = $profileValue('kk', $kkFallback !== '' ? $kkFallback : 'Belum tersedia');
$birthRaw = !empty($accountProfile['birth_date']) ? (string) $accountProfile['birth_date'] : (!empty($currentUser['birth_date']) ? (string) $currentUser['birth_date'] : '');
$birthDate = preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $birthRaw) ? tanggal_id($birthRaw) : 'Belum tersedia';
$genderRaw = strtolower(trim((string) (!empty($accountProfile['gender']) ? $accountProfile['gender'] : (!empty($currentUser['gender']) ? $currentUser['gender'] : ''))));
$genderMap = array(
    'l' => 'Laki-laki',
    'lk' => 'Laki-laki',
    'laki' => 'Laki-laki',
    'laki-laki' => 'Laki-laki',
    'p' => 'Perempuan',
    'pr' => 'Perempuan',
    'perempuan' => 'Perempuan'
);
$gender = $genderRaw !== '' && isset($genderMap[$genderRaw]) ? $genderMap[$genderRaw] : ($genderRaw !== '' ? $genderRaw : 'Belum tersedia');
$regionParts = array();
foreach (array('village_name', 'district_name', 'regency_name') as $regionKey) {
    if (!empty($currentUser[$regionKey])) $regionParts[] = trim((string) $currentUser[$regionKey]);
}
$addressFallback = !empty($currentUser['address']) ? (string) $currentUser['address'] : ($regionParts ? implode(', ', $regionParts) : 'Belum tersedia');
$address = $profileValue('address', $addressFallback);
$verificationStatus = strtolower(trim((string) (isset($accountProfile['verification_status']) ? $accountProfile['verification_status'] : 'unverified')));
$verificationLabels = array(
    'verified' => array('Terverifikasi', 'is-verified'),
    'revalidation_required' => array('Perlu verifikasi ulang', 'is-warning'),
    'inactive' => array('Tidak aktif', 'is-danger'),
    'unverified' => array('Belum terverifikasi', 'is-muted')
);
$verification = isset($verificationLabels[$verificationStatus]) ? $verificationLabels[$verificationStatus] : array('Belum terverifikasi', 'is-muted');
$identityNote = isset($accountProfile['identity_note']) ? trim((string) $accountProfile['identity_note']) : '';
?>
<div class="warga-account-page">
    <section class="warga-account-head" aria-labelledby="warga-account-name">
        <span class="warga-account-avatar" aria-hidden="true"><?= e(warga_initials($currentUser['name'])) ?></span>
        <div>
            <p><?= e(strtoupper(warga_replace_institution($currentUser['role_name'], $institutionLabel))) ?></p>
            <h1 id="warga-account-name"><?= e($currentUser['name']) ?></h1>
            <span class="warga-account-village"><i class="fa fa-map-marker-alt" aria-hidden="true"></i><?= e($currentUser['village_name']) ?></span>
        </div>
    </section>

    <?php if (!$staffMode): ?>
    <section class="card card-style warga-account-resident-card" aria-labelledby="warga-resident-info-title">
        <div class="content">
            <header class="warga-resident-card-head">
                <div>
                    <p>DATA KEPENDUDUKAN</p>
                    <h2 id="warga-resident-info-title">Informasi Warga</h2>
                </div>
                <span class="warga-resident-status <?= e($verification[1]) ?>"><i class="fa fa-shield-alt" aria-hidden="true"></i><?= e($verification[0]) ?></span>
            </header>

            <div class="warga-resident-grid">
                <div class="warga-resident-item is-nik">
                    <span class="warga-resident-item-icon" aria-hidden="true"><i class="fa fa-id-card"></i></span>
                    <div class="warga-resident-item-copy"><span>NIK</span><strong><?= e($nik) ?></strong></div>
                </div>
                <div class="warga-resident-item is-kk">
                    <span class="warga-resident-item-icon" aria-hidden="true"><i class="fa fa-address-card"></i></span>
                    <div class="warga-resident-item-copy"><span>No. KK</span><strong><?= e($kk) ?></strong></div>
                </div>
                <div class="warga-resident-item is-name">
                    <span class="warga-resident-item-icon" aria-hidden="true"><i class="fa fa-user"></i></span>
                    <div class="warga-resident-item-copy"><span>Nama lengkap</span><strong><?= e($currentUser['name']) ?></strong></div>
                </div>
                <div class="warga-resident-item is-birth">
                    <span class="warga-resident-item-icon" aria-hidden="true"><i class="fa fa-calendar-alt"></i></span>
                    <div class="warga-resident-item-copy"><span>Tanggal lahir</span><strong><?= e($birthDate) ?></strong></div>
                </div>
                <div class="warga-resident-item is-gender">
                    <span class="warga-resident-item-icon" aria-hidden="true"><i class="fa fa-venus-mars"></i></span>
                    <div class="warga-resident-item-copy"><span>Jenis kelamin</span><strong><?= e($gender) ?></strong></div>
                </div>
                <div class="warga-resident-item is-address">
                    <span class="warga-resident-item-icon" aria-hidden="true"><i class="fa fa-home"></i></span>
                    <div class="warga-resident-item-copy"><span>Alamat / wilayah domisili</span><strong><?= e($address) ?></strong></div>
                </div>
            </div>

            <p class="warga-resident-note"><i class="fa fa-lock" aria-hidden="true"></i><?= e($identityNote !== '' ? $identityNote : 'NIK dan No. KK disimpan terenkripsi dan hanya ditampilkan untuk pemilik akun ini.') ?></p>
        </div>
    </section>
    <?php endif; ?>

    <section class="card card-style warga-account-card" aria-labelledby="warga-account-info-title">
        <div class="content">
            <header class="warga-account-card-title">
                <div>
                    <p>KONTAK &amp; WILAYAH</p>
                    <h2 id="warga-account-info-title">Data Akun</h2>
                </div>
                <span aria-hidden="true"><i class="fa fa-id-card"></i></span>
            </header>

            <div class="warga-account-row">
                <span class="warga-account-row-icon is-phone" aria-hidden="true"><i class="fa fa-phone"></i></span>
                <div><span>Nomor telepon</span><strong><?= e($currentUser['phone'] ?: '-') ?></strong></div>
            </div>
            <div class="warga-account-row">
                <span class="warga-account-row-icon is-email" aria-hidden="true"><i class="fa fa-envelope"></i></span>
                <div><span>Email</span><strong><?= e($currentUser['email'] ?: '-') ?></strong></div>
            </div>
            <div class="warga-account-row">
                <span class="warga-account-row-icon is-location" aria-hidden="true"><i class="fa fa-map-marker-alt"></i></span>
                <div>
                    <span>Wilayah layanan</span>
                    <strong><?= e($currentUser['village_name'] . ', ' . $currentUser['district_name']) ?></strong>
                    <small><?= e($currentUser['regency_name']) ?></small>
                </div>
            </div>
        </div>
    </section>

    <section class="card card-style warga-settings-list" aria-labelledby="warga-account-settings-title">
        <div class="content mb-0">
            <header class="warga-account-card-title">
                <div>
                    <p>PREFERENSI</p>
                    <h2 id="warga-account-settings-title">Pengaturan Aplikasi</h2>
                </div>
                <span aria-hidden="true"><i class="fa fa-sliders-h"></i></span>
            </header>

            <a href="#" data-toggle-theme>
                <span class="warga-setting-icon is-dark"><i class="fa fa-moon" aria-hidden="true"></i></span>
                <div><strong>Mode Tampilan</strong><small>Gunakan tema terang atau gelap</small></div>
                <i class="fa fa-chevron-right" aria-hidden="true"></i>
            </a>
            <?php if (!$staffMode): ?>
                <a href="<?= site_url('notifikasi') ?>">
                    <span class="warga-setting-icon is-red"><i class="fa fa-bell" aria-hidden="true"></i></span>
                    <div><strong>Notifikasi</strong><small>Lihat pembaruan status layanan</small></div>
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                </a>
            <?php endif; ?>
            <a href="<?= site_url('akun/edit') ?>">
                <span class="warga-setting-icon is-blue"><i class="fa fa-user-edit" aria-hidden="true"></i></span>
                <div><strong>Edit Akun</strong><small>Ubah email atau nomor telepon</small></div>
                <i class="fa fa-chevron-right" aria-hidden="true"></i>
            </a>
            <a href="<?= site_url('akun/ganti-password') ?>">
                <span class="warga-setting-icon is-orange"><i class="fa fa-key" aria-hidden="true"></i></span>
                <div><strong>Ganti Password</strong><small>Perbarui kata sandi akun</small></div>
                <i class="fa fa-chevron-right" aria-hidden="true"></i>
            </a>
        </div>
    </section>

    <section class="card card-style warga-install-card"><div class="content"><?php $this->load->view('layouts/pwa_install'); ?></div></section>
    <section class="warga-community">
        <a href="<?= site_url('kontak') ?>" class="community-text-link"><i class="fa fa-address-book"></i> Kontak <?= e($institutionLabel) ?></a>
        <div class="community-push">
            <button type="button" class="community-button" data-push-toggle><i class="fa fa-bell"></i> Aktifkan Notifikasi</button>
            <p data-push-status role="status"></p>
        </div>
        <?php if ($staffMode): ?><a href="<?= site_url('notifikasi') ?>">Notifikasi <span data-notification-count></span></a><?php endif; ?>
    </section>

    <form method="post" action="<?= site_url('logout') ?>" class="content warga-account-logout" data-logout-form>
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-full btn-m border-red-dark color-red-dark rounded-s font-600"><i class="fa fa-sign-out-alt me-2" aria-hidden="true"></i>Keluar dari Akun</button>
    </form>
</div>
