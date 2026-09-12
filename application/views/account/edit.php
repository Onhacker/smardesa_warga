<?php defined('BASEPATH') OR exit('No direct script access allowed');
$values = isset($formValues) && is_array($formValues) ? $formValues : array();
$errors = isset($fieldErrors) && is_array($fieldErrors) ? $fieldErrors : array();
$value = function ($key) use ($values) { return isset($values[$key]) ? (string) $values[$key] : ''; };
$error = function ($key) use ($errors) { return isset($errors[$key]) ? (string) $errors[$key] : ''; };
?>
<div class="warga-account-form-page warga-account-edit-page">
    <section class="warga-page-intro">
        <div><p>AKUN SAYA</p><h1>Edit Akun</h1><span>Perbarui email dan nomor telepon Anda.</span></div>
        <span class="warga-intro-icon" aria-hidden="true"><i class="fa fa-user-edit"></i></span>
    </section>
    <section class="card card-style warga-account-form-card">
        <div class="content">
            <?php if (!empty($errors['contact'])): ?><div id="account-contact-error" class="warga-form-error" role="alert"><i class="fa fa-exclamation-circle" aria-hidden="true"></i><?= e($errors['contact']) ?></div><?php endif; ?>
            <?php if (!empty($demoMode)): ?><p class="warga-form-demo-note"><i class="fa fa-info-circle" aria-hidden="true"></i>Mode demo menyimpan perubahan pada sesi browser ini.</p><?php endif; ?>
            <form method="post" action="<?= site_url('akun/edit') ?>" autocomplete="on" data-disable-submit>
                <?= csrf_field() ?>
                <div class="warga-account-form-field">
                    <label for="account-email">Email</label>
                    <div class="warga-account-form-input"><i class="fa fa-envelope" aria-hidden="true"></i><input type="email" id="account-email" name="email" value="<?= e($value('email')) ?>" maxlength="160" autocomplete="email" placeholder="nama@contoh.com" aria-describedby="account-email-hint<?= $error('contact') ? ' account-contact-error' : '' ?>"></div>
                    <small id="account-email-hint">Gunakan email yang aktif untuk menerima informasi layanan.</small>
                </div>
                <div class="warga-account-form-field">
                    <label for="account-phone">Nomor telepon</label>
                    <div class="warga-account-form-input"><i class="fa fa-phone" aria-hidden="true"></i><input type="tel" id="account-phone" name="phone" value="<?= e($value('phone')) ?>" maxlength="20" autocomplete="tel" placeholder="08xxxxxxxxxx"></div>
                    <small>Masukkan 8–15 digit, boleh memakai tanda +, spasi, atau tanda hubung.</small>
                </div>
                <div class="warga-account-form-field">
                    <label for="account-current-password">Kata sandi saat ini</label>
                    <div class="warga-account-form-input"><i class="fa fa-lock" aria-hidden="true"></i><input type="password" id="account-current-password" name="current_password" required autocomplete="current-password" aria-invalid="<?= $error('current_password') ? 'true' : 'false' ?>" aria-describedby="account-current-password-error"><button type="button" class="warga-password-toggle" data-password-toggle aria-controls="account-current-password" aria-pressed="false" aria-label="Tampilkan kata sandi"><i class="fa fa-eye" aria-hidden="true"></i></button></div>
                    <?php if ($error('current_password')): ?><small id="account-current-password-error" class="warga-form-field-error"><?= e($error('current_password')) ?></small><?php endif; ?>
                </div>
                <div class="warga-account-form-actions"><a href="<?= site_url('akun') ?>" class="btn btn-s warga-form-cancel">Batal</a><button type="submit" class="btn btn-s bg-teal-dark color-white warga-form-submit"><i class="fa fa-save" aria-hidden="true"></i><span>Simpan</span></button></div>
            </form>
        </div>
    </section>
</div>
