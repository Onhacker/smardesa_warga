<?php defined('BASEPATH') OR exit('No direct script access allowed');
$errors = isset($fieldErrors) && is_array($fieldErrors) ? $fieldErrors : array();
$error = function ($key) use ($errors) { return isset($errors[$key]) ? (string) $errors[$key] : ''; };
$pending = isset($pendingChange) && is_array($pendingChange) ? $pendingChange : array();
?>
<div class="warga-account-form-page">
    <section class="warga-page-intro is-form">
        <div><p>KEAMANAN AKUN</p><h1>Ganti Password</h1><span>Buat kata sandi baru untuk menjaga akun tetap aman.</span></div>
        <span class="warga-intro-icon" aria-hidden="true"><i class="fa fa-key"></i></span>
    </section>
    <section class="card card-style warga-account-form-card">
        <div class="content">
            <?php if (!empty($demoMode)): ?><p class="warga-form-demo-note"><i class="fa fa-info-circle" aria-hidden="true"></i>Mode demo menyimpan kata sandi baru pada sesi browser ini.</p><?php endif; ?>
            <form method="post" action="<?= site_url('akun/ganti-password') ?>" autocomplete="on" data-disable-submit>
                <?= csrf_field() ?>
                <?php if ($pending): ?><div class="warga-identity-note mb-4"><i class="fa fa-envelope-open-text" aria-hidden="true"></i><span>Kode 6 digit dikirim ke <strong><?= e($pending['email_masked'] ?? '') ?></strong>. Kode wajib benar sebelum kata sandi diganti.</span></div><?php endif; ?>
                <?php if ($pending): ?>
                    <div class="warga-account-form-field">
                        <label for="account-otp">Kode verifikasi</label>
                        <div class="warga-account-form-input"><i class="fa fa-key" aria-hidden="true"></i><input type="text" id="account-otp" name="otp" required maxlength="6" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" placeholder="6 digit" aria-invalid="<?= $error('otp') ? 'true' : 'false' ?>"></div>
                        <?php if ($error('otp')): ?><small class="warga-form-field-error"><?= e($error('otp')) ?></small><?php else: ?><small>Kode berlaku 10 menit. Periksa Inbox, Spam, atau Promosi.</small><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php $passwordFields = $pending
                    ? array('new_password' => 'Kata sandi baru', 'password_confirm' => 'Ulangi kata sandi baru')
                    : (!empty($demoMode)
                        ? array('current_password' => 'Kata sandi saat ini', 'new_password' => 'Kata sandi baru', 'password_confirm' => 'Ulangi kata sandi baru')
                        : array('current_password' => 'Kata sandi saat ini')); ?>
                <?php foreach ($passwordFields as $name => $label): $id = 'account-' . $name; $autocomplete = $name === 'current_password' ? 'current-password' : 'new-password'; ?>
                    <div class="warga-account-form-field">
                        <label for="<?= e($id) ?>"><?= e($label) ?></label>
                        <div class="warga-account-form-input"><i class="fa fa-lock" aria-hidden="true"></i><input type="password" id="<?= e($id) ?>" name="<?= e($name) ?>" required maxlength="72" autocomplete="<?= e($autocomplete) ?>" aria-invalid="<?= $error($name) ? 'true' : 'false' ?>" aria-describedby="<?= e($id) ?>-hint<?= $error($name) ? ' ' . e($id) . '-error' : '' ?>"><button type="button" class="warga-password-toggle" data-password-toggle aria-controls="<?= e($id) ?>" aria-pressed="false" aria-label="Tampilkan kata sandi"><i class="fa fa-eye" aria-hidden="true"></i></button></div>
                        <small id="<?= e($id) ?>-hint" class="<?= $error($name) ? 'd-none' : '' ?>"><?= $name === 'new_password' ? 'Minimal 8 karakter.' : 'Masukkan kata sandi Anda.' ?></small>
                        <?php if ($error($name)): ?><small id="<?= e($id) ?>-error" class="warga-form-field-error"><?= e($error($name)) ?></small><?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="warga-account-form-actions"><?php if ($pending): ?><button type="submit" name="cancel_change" value="1" class="btn btn-s warga-form-cancel">Batalkan</button><?php else: ?><a href="<?= site_url('akun') ?>" class="btn btn-s warga-form-cancel">Batal</a><?php endif; ?><button type="submit" class="btn btn-s bg-orange-dark color-white"><i class="fa fa-key" aria-hidden="true"></i><span><?= $pending || !empty($demoMode) ? 'Ubah password' : 'Kirim kode' ?></span></button></div>
            </form>
        </div>
    </section>
</div>
