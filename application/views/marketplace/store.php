<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$store = isset($store) && is_array($store) ? $store : array();
$values = isset($formValues) && is_array($formValues) ? $formValues : $store;
$errors = isset($fieldErrors) && is_array($fieldErrors) ? $fieldErrors : array();
$value = static function ($key, $fallback = '') use ($values) { return isset($values[$key]) ? (string) $values[$key] : (string) $fallback; };
$error = static function ($key) use ($errors) { return isset($errors[$key]) ? (string) $errors[$key] : ''; };
?>

<div class="marketplace-page marketplace-form-page marketplace-store-page">
    <section class="market-hero market-hero-compact" aria-labelledby="market-store-form-title">
        <div class="market-hero-copy"><p class="market-eyebrow color-white">PASAR DIGITAL</p><h1 id="market-store-form-title">Identitas Toko</h1><span class="color-white">Tampilkan informasi usaha agar warga mudah menghubungi Anda.</span></div>
        <span class="market-hero-icon color-white" aria-hidden="true"><i class="fa fa-store-alt color-white"></i></span>
    </section>

    <section class="card card-style market-form-card">
        <div class="content">
            <?php if (!empty($errors['form'])): ?><div class="market-form-alert" role="alert"><i class="fa fa-exclamation-circle" aria-hidden="true"></i><?= e($errors['form']) ?></div><?php endif; ?>
            <form method="post" action="<?= site_url('pasar/toko/simpan') ?>" data-market-store-form>
                <?= csrf_field() ?>
                <div class="market-form-section-title"><span class="color-white"><i class="fa fa-store color-white" aria-hidden="true"></i></span><div><h2>Profil toko</h2><p>Data ini tampil pada produk yang Anda terbitkan.</p></div></div>
                <label class="market-form-field" for="market-store-name"><span>Nama toko / usaha</span><input type="text" id="market-store-name" name="name" value="<?= e($value('name')) ?>" required maxlength="160" placeholder="Contoh: BUMDes Maju Bersama"><?php if ($error('name')): ?><small class="market-form-error"><?= e($error('name')) ?></small><?php endif; ?></label>
                <label class="market-form-field" for="market-store-description"><span>Deskripsi toko <em>(opsional)</em></span><textarea id="market-store-description" name="description" rows="4" maxlength="1000" placeholder="Ceritakan singkat tentang usaha atau produk Anda."><?= e($value('description')) ?></textarea></label>
                <div class="market-form-two-col">
                    <label class="market-form-field" for="market-store-whatsapp"><span>Nomor WhatsApp</span><input type="tel" id="market-store-whatsapp" name="whatsapp" value="<?= e($value('whatsapp')) ?>" maxlength="30" inputmode="tel" placeholder="08xxxxxxxxxx" required><?php if ($error('whatsapp')): ?><small class="market-form-error"><?= e($error('whatsapp')) ?></small><?php endif; ?></label>
                    <label class="market-form-field" for="market-store-phone"><span>Nomor telepon <em>(opsional)</em></span><input type="tel" id="market-store-phone" name="phone" value="<?= e($value('phone')) ?>" maxlength="30" inputmode="tel" placeholder="08xxxxxxxxxx"></label>
                </div>
                <label class="market-form-field" for="market-store-address"><span>Alamat / lokasi usaha <em>(opsional)</em></span><textarea id="market-store-address" name="address" rows="3" maxlength="255" placeholder="Contoh: Jalan utama kampung, dekat balai kampung."><?= e($value('address')) ?></textarea></label>
                <div class="market-form-actions"><a href="<?= site_url('pasar/tokoku') ?>" class="btn btn-s market-form-cancel">Batal</a><button type="submit" class="btn btn-s market-form-submit"><i class="fa fa-save color-white" aria-hidden="true"></i><span class="color-white">Simpan identitas toko</span></button></div>
            </form>
        </div>
    </section>
</div>
