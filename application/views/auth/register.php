<?php defined('BASEPATH') OR exit('No direct script access allowed');
$registrationRegions = isset($registrationRegions) && is_array($registrationRegions) ? $registrationRegions : array();
$registrationDistricts = array();
foreach ($registrationRegions as $region) {
    $districtCode = isset($region['district_code']) ? trim((string) $region['district_code']) : '';
    $districtName = isset($region['district_name']) ? trim((string) $region['district_name']) : '';
    if ($districtCode !== '' && !isset($registrationDistricts[$districtCode])) {
        $registrationDistricts[$districtCode] = $districtName !== '' ? $districtName : $districtCode;
    }
}
$registrationRegionsJson = json_encode($registrationRegions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$registrationValidationErrors = validation_errors('<span class="warga-auth-error-item">', '</span>');
$registrationErrorHtml = !empty($error)
    ? '<span class="warga-auth-error-item">' . e($error) . '</span>'
    : $registrationValidationErrors;
?>
<!DOCTYPE HTML>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover"><meta name="theme-color" content="#235fa4">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/v22/styles/bootstrap.min.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/v22/fonts/css/fontawesome-all.min.css') ?>"><link rel="stylesheet" href="<?= base_url('assets/css/simp-v22.min.css') ?>?v=1"><link rel="stylesheet" href="<?= base_url('assets/css/warga.min.css') ?>?v=96"><link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
</head>
<body class="theme-light warga-auth-body" data-base-url="<?= e(base_url()) ?>">
<?php $this->load->view('layouts/page_skeleton'); ?><div id="page">
<header class="header header-fixed header-logo-center"><a href="<?= site_url('register') ?>" class="header-title">Daftar Warga</a><a href="<?= site_url('login') ?>" class="header-icon header-icon-1" aria-label="Kembali"><i class="fa fa-chevron-left"></i></a></header>
<main class="page-content header-clear-medium warga-auth-page">
    <section class="warga-auth-brand compact"><img src="<?= base_url('assets/pwa/icon-192.png') ?>" alt="Logo Kabupaten Jayawijaya"><div><p>AKUN LAYANAN WARGA</p><h1>Daftar Akun</h1><span>Satu akun untuk permohonan layanan wilayah.</span></div></section>
    <section class="card card-style warga-auth-card"><div class="content">
        <?php if ($registrationErrorHtml !== ''): ?>
            <div class="warga-auth-error" role="alert" aria-live="assertive">
                <span class="warga-auth-error-icon" aria-hidden="true"><i class="fa fa-exclamation"></i></span>
                <div class="warga-auth-error-content">
                    <strong>Pendaftaran belum berhasil</strong>
                    <div class="warga-auth-error-list"><?= $registrationErrorHtml ?></div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($demoMode): ?><div class="warga-demo-credentials mb-4"><i class="fa fa-info-circle"></i><span>Mode demo aktif. Data pendaftaran tidak disimpan permanen.</span></div><?php endif; ?>
        <form method="post" action="<?= site_url('register') ?>" data-disable-submit>
            <?= csrf_field() ?>
            <div class="warga-identity-note"><i class="fa fa-shield-alt"></i><span>Pendaftaran hanya untuk penduduk wilayah yang dipilih. Isi NIK, No. KK, dan nama sesuai Data Penduduk wilayah.</span></div>
            <div class="input-style no-borders has-icon validate-field mb-4"><i class="fa fa-id-card"></i><input type="text" class="form-control" id="register-nik" name="nik" value="<?= e(old('nik')) ?>" placeholder="NIK (16 digit)" required maxlength="25" inputmode="numeric" autocomplete="off"><label for="register-nik" class="color-highlight">NIK (16 digit)</label><em>*</em></div>
            <div class="input-style no-borders has-icon validate-field mb-4"><i class="fa fa-address-card"></i><input type="text" class="form-control" id="register-kk" name="kk" value="<?= e(old('kk')) ?>" placeholder="No. KK (16 digit)" required maxlength="25" inputmode="numeric" autocomplete="off"><label for="register-kk" class="color-highlight">No. KK (16 digit)</label><em>*</em></div>
            <div class="input-style no-borders has-icon validate-field mb-4"><i class="fa fa-user"></i><input type="text" class="form-control" id="register-name" name="name" value="<?= e(old('name')) ?>" placeholder="Nama Lengkap sesuai Data Penduduk" required maxlength="120"><label for="register-name" class="color-highlight">Nama Lengkap sesuai Data Penduduk</label><em>*</em></div>
            <div class="input-style no-borders has-icon validate-field mb-4"><i class="fa fa-envelope"></i><input type="text" class="form-control" id="register-contact" name="contact" value="<?= e(old('contact')) ?>" placeholder="Email atau Nomor Telepon" required maxlength="160"><label for="register-contact" class="color-highlight">Email atau Nomor Telepon</label><em>*</em></div>
            <div class="warga-region-grid">
                <div class="input-style no-borders has-icon validate-field warga-region-field">
                    <i class="fa fa-map-marker-alt"></i>
                    <select class="form-select registration-cascade-select warga-region-select" id="register-district" name="district_code" required>
                        <option value="">Pilih distrik/kecamatan</option>
                        <?php foreach ($registrationDistricts as $districtCode => $districtName): ?>
                            <option value="<?= e($districtCode) ?>" <?= old('district_code') === $districtCode ? 'selected' : '' ?>><?= e($districtName) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa fa-times disabled invalid color-red-dark"></i><i class="fa fa-check disabled valid color-green-dark"></i>
                    <label for="register-district" class="color-highlight">Distrik/Kecamatan</label><em>*</em>
                </div>
                <div class="input-style no-borders has-icon validate-field warga-region-field">
                    <i class="fa fa-home"></i>
                    <select class="form-select registration-cascade-select warga-region-select" id="register-village" name="village_code" required disabled>
                        <option value="">Pilih wilayah</option>
                    </select>
                    <i class="fa fa-times disabled invalid color-red-dark"></i><i class="fa fa-check disabled valid color-green-dark"></i>
                    <label for="register-village" class="color-highlight">Wilayah</label><em>*</em>
                </div>
            </div>
            <div class="warga-region-hint" id="register-region-hint" aria-live="polite">Pilih distrik/kecamatan terlebih dahulu. Kode wilayah disimpan otomatis.</div>
            <?php if (!$registrationRegions): ?><div class="warga-region-empty" role="alert"><i class="fa fa-info-circle"></i><span>Daftar wilayah belum tersedia. Hubungi administrator pusat.</span></div><?php endif; ?>
            <div class="input-style no-borders has-icon validate-field mb-4"><i class="fa fa-lock"></i><input type="password" class="form-control" id="register-password" name="password" placeholder="Kata Sandi" required minlength="8" autocomplete="new-password"><label for="register-password" class="color-highlight">Kata Sandi</label><em>*</em></div>
            <div class="input-style no-borders has-icon validate-field mb-4"><i class="fa fa-check-circle"></i><input type="password" class="form-control" id="register-confirm" name="password_confirm" placeholder="Ulangi Kata Sandi" required minlength="8" autocomplete="new-password"><label for="register-confirm" class="color-highlight">Ulangi Kata Sandi</label><em>*</em></div>
            <button class="btn btn-full btn-l font-600 bg-teal-dark color-white rounded-s" type="submit"><span>Daftar Akun</span><i class="fa fa-arrow-right ms-2"></i></button>
        </form>
        <p class="text-center mt-4 mb-0">Sudah memiliki akun? <a class="color-highlight font-600" href="<?= site_url('login') ?>">Masuk</a></p>
    </div></section>
</main></div>
<script>window.SDW={baseUrl:<?= json_encode(base_url()) ?>,serviceWorkerUrl:<?= json_encode(base_url('service-worker.js') . '?v=52') ?>,serviceWorkerScope:<?= json_encode(base_url()) ?>};window.SDW_REGISTER_REGIONS=<?= $registrationRegionsJson ?: '[]' ?>;</script><script src="<?= base_url('assets/v22/scripts/bootstrap.min.js') ?>"></script><script src="<?= base_url('assets/v22/scripts/custom.min.js') ?>?v=1"></script><script src="<?= base_url('assets/js/warga.min.js') ?>?v=15"></script>
<script>
(function () {
    var oldVillage = <?= json_encode((string) old('village_code')) ?>;
    var oldDistrict = <?= json_encode((string) old('district_code')) ?>;

    function initRegionCascade() {
        var district = document.getElementById('register-district');
        var village = document.getElementById('register-village');
        var hint = document.getElementById('register-region-hint');
        var regions = Array.isArray(window.SDW_REGISTER_REGIONS) ? window.SDW_REGISTER_REGIONS : [];
        if (!district || !village || !hint || district.dataset.regionCascadeReady === '1') return;
        district.dataset.regionCascadeReady = '1';

        function updateHint() {
            var selected = regions.find(function (region) {
                return String(region.village_code || '') === String(village.value || '');
            });
            if (selected) {
                hint.textContent = 'Wilayah terpilih: ' + (selected.village_name || selected.village_code) + '. Kode wilayah disimpan otomatis.';
            } else if (district.value) {
                hint.textContent = 'Pilih wilayah. Kode wilayah disimpan otomatis.';
            } else {
                hint.textContent = 'Pilih distrik/kecamatan terlebih dahulu. Kode wilayah disimpan otomatis.';
            }
        }

        function fillVillages(preferredCode) {
            var selectedDistrict = String(district.value || '');
            var available = regions.filter(function (region) {
                return String(region.district_code || '') === selectedDistrict;
            });
            village.innerHTML = '';
            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Pilih wilayah';
            village.appendChild(placeholder);
            available.forEach(function (region) {
                var option = document.createElement('option');
                option.value = String(region.village_code || '');
                option.textContent = String(region.village_name || region.village_code || '');
                village.appendChild(option);
            });
            village.disabled = available.length === 0;
            var selected = available.some(function (region) {
                return String(region.village_code || '') === String(preferredCode || '');
            });
            village.value = selected ? String(preferredCode) : '';
            updateHint();
        }

        if (!district.value && oldVillage) {
            var matched = regions.find(function (region) {
                return String(region.village_code || '') === oldVillage;
            });
            if (matched) district.value = String(matched.district_code || '');
        } else if (oldDistrict) {
            district.value = oldDistrict;
        }
        fillVillages(oldVillage);
        district.addEventListener('change', function () { fillVillages(''); });
        village.addEventListener('change', updateHint);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRegionCascade);
    } else {
        initRegionCascade();
    }
}());
</script>
</body></html>
