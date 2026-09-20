<?php defined('BASEPATH') OR exit('No direct script access allowed');
$values = isset($formValues) && is_array($formValues) ? $formValues : array();
$errors = isset($fieldErrors) && is_array($fieldErrors) ? $fieldErrors : array();
$value = function ($key) use ($values) { return isset($values[$key]) ? (string) $values[$key] : ''; };
$error = function ($key) use ($errors) { return isset($errors[$key]) ? (string) $errors[$key] : ''; };
$pending = isset($pendingChange) && is_array($pendingChange) ? $pendingChange : array();
?>
<div class="warga-account-form-page warga-account-edit-page">
    <section class="warga-page-intro">
        <div><p>AKUN SAYA</p><h1>Edit Akun</h1><span>Perbarui email dan nomor telepon Anda.</span></div>
        <span class="warga-intro-icon" aria-hidden="true"><i class="fa fa-user-edit"></i></span>
    </section>
    <section class="card card-style warga-account-form-card">
        <div class="content">
            <?php if (!empty($errors['contact']) || !empty($errors['otp'])): ?><div id="account-contact-error" class="warga-form-error" role="alert"><i class="fa fa-exclamation-circle" aria-hidden="true"></i><?= e(!empty($errors['otp']) ? $errors['otp'] : $errors['contact']) ?></div><?php endif; ?>
            <?php if (!empty($demoMode)): ?><p class="warga-form-demo-note"><i class="fa fa-info-circle" aria-hidden="true"></i>Mode demo menyimpan perubahan pada sesi browser ini.</p><?php endif; ?>
            <form method="post" action="<?= site_url('akun/edit') ?>" autocomplete="on" data-disable-submit>
                <?= csrf_field() ?>
                <?php if ($pending): ?>
                    <div class="warga-identity-note mb-4"><i class="fa fa-envelope-open-text" aria-hidden="true"></i><span>Kode 6 digit dikirim ke <strong><?= e($pending['email_masked'] ?? '') ?></strong>. Masukkan kode untuk menyimpan email dan nomor telepon baru.</span></div>
                    <div class="warga-account-form-field">
                        <label for="account-otp">Kode verifikasi</label>
                        <div class="warga-account-form-input"><i class="fa fa-key" aria-hidden="true"></i><input type="text" id="account-otp" name="otp" required maxlength="6" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" placeholder="6 digit" aria-invalid="<?= $error('otp') ? 'true' : 'false' ?>"></div>
                        <small>Kode berlaku 10 menit. Periksa juga folder Spam atau Promosi.</small>
                    </div>
                    <div class="warga-account-form-field">
                        <label>Kontak baru</label>
                        <div class="warga-account-form-input"><i class="fa fa-envelope" aria-hidden="true"></i><input type="text" value="<?= e($value('email')) ?>" readonly></div>
                        <?php if ($value('phone') !== ''): ?><small>Nomor telepon: <?= e($value('phone')) ?></small><?php endif; ?>
                    </div>
                    <div class="warga-account-form-actions"><button type="submit" name="cancel_change" value="1" class="btn btn-s warga-form-cancel">Batalkan</button><button type="submit" class="btn btn-s bg-teal-dark color-white warga-form-submit"><i class="fa fa-check" aria-hidden="true"></i><span>Verifikasi &amp; Simpan</span></button></div>
                <?php else: ?>
                <div class="warga-account-form-field">
                    <label for="account-email">Email</label>
                    <div class="warga-account-form-input"><i class="fa fa-envelope" aria-hidden="true"></i><input type="email" id="account-email" name="email" value="<?= e($value('email')) ?>" required maxlength="160" autocomplete="email" placeholder="nama@contoh.com" aria-describedby="account-email-hint<?= $error('contact') ? ' account-contact-error' : '' ?>"></div>
                    <small id="account-email-hint">Kode verifikasi akan dikirim ke email ini sebelum perubahan disimpan.</small>
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
                <?php endif; ?>
            </form>
        </div>
    </section>
</div>
