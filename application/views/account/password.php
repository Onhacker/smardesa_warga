<?php defined('BASEPATH') OR exit('No direct script access allowed');
$errors = isset($fieldErrors) && is_array($fieldErrors) ? $fieldErrors : array();
$error = function ($key) use ($errors) { return isset($errors[$key]) ? (string) $errors[$key] : ''; };
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
                <?php foreach (array('current_password' => 'Kata sandi saat ini', 'new_password' => 'Kata sandi baru', 'password_confirm' => 'Ulangi kata sandi baru') as $name => $label): $id = 'account-' . $name; $autocomplete = $name === 'current_password' ? 'current-password' : 'new-password'; ?>
                    <div class="warga-account-form-field">
                        <label for="<?= e($id) ?>"><?= e($label) ?></label>
                        <div class="warga-account-form-input"><i class="fa fa-lock" aria-hidden="true"></i><input type="password" id="<?= e($id) ?>" name="<?= e($name) ?>" required maxlength="72" autocomplete="<?= e($autocomplete) ?>" aria-invalid="<?= $error($name) ? 'true' : 'false' ?>" aria-describedby="<?= e($id) ?>-hint<?= $error($name) ? ' ' . e($id) . '-error' : '' ?>"><button type="button" class="warga-password-toggle" data-password-toggle aria-controls="<?= e($id) ?>" aria-pressed="false" aria-label="Tampilkan kata sandi"><i class="fa fa-eye" aria-hidden="true"></i></button></div>
                        <small id="<?= e($id) ?>-hint" class="<?= $error($name) ? 'd-none' : '' ?>"><?= $name === 'new_password' ? 'Minimal 8 karakter.' : 'Masukkan kata sandi Anda.' ?></small>
                        <?php if ($error($name)): ?><small id="<?= e($id) ?>-error" class="warga-form-field-error"><?= e($error($name)) ?></small><?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="warga-account-form-actions"><a href="<?= site_url('akun') ?>" class="btn btn-s warga-form-cancel">Batal</a><button type="submit" class="btn btn-s bg-orange-dark color-white"><i class="fa fa-key" aria-hidden="true"></i><span>Ubah password</span></button></div>
            </form>
        </div>
    </section>
</div>
