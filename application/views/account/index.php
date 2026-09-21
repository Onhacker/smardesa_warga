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

            <a href="#" data-toggle-theme class="warga-account-theme-row">
                <span class="warga-setting-icon is-dark"><i class="fa fa-moon" aria-hidden="true"></i></span>
                <div><strong>Mode Tampilan</strong><small>Gunakan tema terang atau gelap</small></div>
                <div class="custom-control small-switch ios-switch warga-account-theme-switch">
                    <input data-toggle-theme type="checkbox" class="ios-input" id="switch-account-dark-mode" aria-label="Aktifkan mode gelap">
                    <label class="custom-control-label" for="switch-account-dark-mode" aria-hidden="true"></label>
                </div>
            </a>
            <div class="warga-account-notification-row">
                <span class="warga-setting-icon is-red"><i class="fa fa-inbox" aria-hidden="true"></i></span>
                <div class="warga-account-notification-copy">
                    <strong>Pemberitahuan</strong>
                    <small>Aktifkan pemberitahuan status layanan; suara dan getar mengikuti pengaturan perangkat</small>
                    <span class="warga-account-notification-status" data-push-status role="status" aria-live="polite"></span>
                    <a class="warga-account-notification-history" href="<?= site_url('notifikasi') ?>">Lihat riwayat pemberitahuan <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="custom-control small-switch ios-switch warga-account-notification-switch">
                    <input data-push-toggle type="checkbox" class="ios-input" id="switch-push-notification" aria-label="Aktifkan pemberitahuan">
                    <label class="custom-control-label" for="switch-push-notification" aria-hidden="true"></label>
                </div>
            </div>
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
            <a href="<?= site_url('permintaan-hapus-akun') ?>">
                <span class="warga-setting-icon is-red"><i class="fa fa-user-times" aria-hidden="true"></i></span>
                <div><strong>Hapus Akun</strong><small>Ajukan penghapusan akun dan data terkait</small></div>
                <i class="fa fa-chevron-right" aria-hidden="true"></i>
            </a>
        </div>
    </section>

    <section class="card card-style warga-account-card warga-passkey-security-card" data-passkey-security aria-labelledby="warga-security-title">
        <div class="content">
            <header class="warga-account-card-title">
                <div>
                    <p>KEAMANAN LOGIN</p>
                    <h2 id="warga-security-title">Biometrik &amp; PIN</h2>
                </div>
                <span aria-hidden="true"><i class="fa fa-user-shield"></i></span>
            </header>
            <div class="warga-security-input">
                <label for="security-current-password">Kata sandi saat ini</label>
                <input type="password" id="security-current-password" data-security-current-password autocomplete="current-password" placeholder="Masukkan untuk mengubah keamanan login">
                <small>Untuk mengaktifkan atau mencabut biometrik/PIN, kata sandi diperlukan sebagai verifikasi tambahan.</small>
            </div>
            <div class="warga-security-row">
                <span class="warga-security-icon" aria-hidden="true"><i class="fa fa-user-shield"></i></span>
                <div class="warga-security-copy">
                    <strong>Login dengan sidik jari atau wajah</strong>
                    <small>Perangkat akan memilih sidik jari, Face ID, PIN, atau pola sesuai pengaturan keamanan perangkat.</small>
                    <span class="warga-security-status" data-passkey-status role="status" aria-live="polite">Memeriksa perangkat…</span>
                    <div class="warga-security-actions"><button type="button" class="btn btn-s bg-blue-dark color-white" data-passkey-register hidden><i class="fa fa-plus" aria-hidden="true"></i>Aktifkan biometrik</button></div>
                    <div class="warga-security-message" data-passkey-message role="status" aria-live="polite" hidden></div>
                    <div class="warga-passkey-devices" data-passkey-devices></div>
                </div>
            </div>
            <div class="warga-security-row">
                <span class="warga-security-icon is-pin" aria-hidden="true"><i class="fa fa-key"></i></span>
                <div class="warga-security-copy">
                    <strong>Login dengan PIN</strong>
                    <small>Gunakan PIN 6 angka sebagai pilihan lain saat biometrik tidak tersedia.</small>
                    <span class="warga-security-status" data-pin-status role="status" aria-live="polite">Memeriksa status PIN…</span>
                    <form data-pin-form autocomplete="off">
                        <div class="warga-security-input"><label for="security-pin">PIN baru</label><input type="password" id="security-pin" name="pin" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="new-password" placeholder="6 angka" required></div>
                        <div class="warga-security-input"><label for="security-pin-confirm">Ulangi PIN</label><input type="password" id="security-pin-confirm" name="pin_confirm" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="new-password" placeholder="Ulangi 6 angka" required></div>
                        <div class="warga-security-actions"><button type="submit" class="btn btn-s bg-blue-dark color-white"><i class="fa fa-save" aria-hidden="true"></i>Simpan PIN</button><button type="button" class="btn btn-s border-red-dark color-red-dark" data-pin-disable hidden><i class="fa fa-times" aria-hidden="true"></i>Nonaktifkan PIN</button></div>
                    </form>
                    <div class="warga-security-message" data-pin-message role="status" aria-live="polite" hidden></div>
                </div>
            </div>
            <p class="warga-resident-note"><i class="fa fa-shield-alt" aria-hidden="true"></i>Data sidik jari/wajah tidak dikirim ke SI DAPULIK. Server hanya menyimpan kunci publik untuk memverifikasi perangkat. Setelah verifikasi PIN atau biometrik, perangkat dapat masuk kembali hingga 1 tahun dan dapat dicabut kapan saja.</p>
        </div>
    </section>

    <section class="card card-style warga-install-card" data-pwa-install-container aria-hidden="false"><div class="content"><?php $this->load->view('layouts/pwa_install'); ?></div></section>

    <form method="post" action="<?= site_url('logout') ?>" class="content warga-account-logout" data-logout-form>
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-full btn-m border-red-dark color-red-dark rounded-s font-600"><i class="fa fa-sign-out-alt me-2" aria-hidden="true"></i>Keluar dari Akun</button>
    </form>
</div>
