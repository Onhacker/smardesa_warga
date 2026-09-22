<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-security-page" data-passkey-security>
    <section class="warga-security-hero" aria-labelledby="warga-security-title">
        <span class="warga-security-hero-icon" aria-hidden="true"><i class="fa fa-user-shield"></i></span>
        <div>
            <p>KEAMANAN AKUN</p>
            <h1 id="warga-security-title">Biometrik &amp; PIN</h1>
            <span>Masuk lebih cepat dan tetap terlindungi di perangkat Anda.</span>
        </div>
    </section>

    <section class="card card-style warga-security-card warga-security-verification" aria-labelledby="warga-security-verification-title">
        <div class="content">
            <header class="warga-security-card-head">
                <span class="warga-security-icon is-password" aria-hidden="true"><i class="fa fa-lock"></i></span>
                <div>
                    <p>VERIFIKASI PERUBAHAN</p>
                    <h2 id="warga-security-verification-title">Kata sandi saat ini</h2>
                </div>
            </header>
            <div class="warga-security-input">
                <label for="security-current-password">Kata sandi akun</label>
                <input type="password" id="security-current-password" data-security-current-password autocomplete="current-password" placeholder="Masukkan kata sandi saat ini">
                <small>Wajib diisi setiap kali Anda mengaktifkan, menonaktifkan, atau mencabut metode login.</small>
            </div>
        </div>
    </section>

    <section class="card card-style warga-security-card" aria-labelledby="warga-biometric-title">
        <div class="content">
            <header class="warga-security-card-head">
                <span class="warga-security-icon" aria-hidden="true"><i class="fa fa-user-shield"></i></span>
                <div>
                    <p>LOGIN PERANGKAT</p>
                    <h2 id="warga-biometric-title">Sidik jari atau wajah</h2>
                </div>
            </header>
            <div class="warga-security-copy">
                <small>Android atau iPhone akan menggunakan sidik jari, wajah, PIN, atau pola sesuai pengaturan keamanan perangkat.</small>
                <span class="warga-security-status" data-passkey-status role="status" aria-live="polite">Memeriksa perangkat…</span>
                <div class="warga-security-actions">
                    <button type="button" class="btn btn-m bg-blue-dark color-white rounded-s" data-passkey-register hidden><i class="fa fa-plus" aria-hidden="true"></i><span>Aktifkan biometrik</span></button>
                </div>
                <div class="warga-security-message" data-passkey-message role="status" aria-live="polite" hidden></div>
                <div class="warga-passkey-devices" data-passkey-devices></div>
            </div>
        </div>
    </section>

    <section class="card card-style warga-security-card" aria-labelledby="warga-pin-title">
        <div class="content">
            <header class="warga-security-card-head">
                <span class="warga-security-icon is-pin" aria-hidden="true"><i class="fa fa-key"></i></span>
                <div>
                    <p>PILIHAN CADANGAN</p>
                    <h2 id="warga-pin-title">Login dengan PIN</h2>
                </div>
            </header>
            <div class="warga-security-copy">
                <small>Gunakan PIN khusus 6 angka ketika biometrik tidak tersedia. Jangan gunakan tanggal lahir atau angka yang mudah ditebak.</small>
                <span class="warga-security-status" data-pin-status role="status" aria-live="polite">Memeriksa status PIN…</span>
                <form data-pin-form autocomplete="off">
                    <div class="warga-security-pin-grid">
                        <div class="warga-security-input">
                            <label for="security-pin">PIN baru</label>
                            <input type="password" id="security-pin" name="pin" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="new-password" placeholder="Masukkan 6 angka" required>
                        </div>
                        <div class="warga-security-input">
                            <label for="security-pin-confirm">Ulangi PIN</label>
                            <input type="password" id="security-pin-confirm" name="pin_confirm" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="new-password" placeholder="Ulangi 6 angka" required>
                        </div>
                    </div>
                    <div class="warga-security-actions">
                        <button type="submit" class="btn btn-m bg-blue-dark color-white rounded-s"><i class="fa fa-save" aria-hidden="true"></i><span>Simpan PIN</span></button>
                        <button type="button" class="btn btn-m border-red-dark color-red-dark rounded-s" data-pin-disable hidden><i class="fa fa-times" aria-hidden="true"></i><span>Nonaktifkan PIN</span></button>
                    </div>
                </form>
                <div class="warga-security-message" data-pin-message role="status" aria-live="polite" hidden></div>
            </div>
        </div>
    </section>

    <aside class="warga-security-privacy" aria-label="Privasi biometrik">
        <span aria-hidden="true"><i class="fa fa-shield-alt"></i></span>
        <p><strong>Biometrik tetap di perangkat.</strong> SI DAPULIK hanya menyimpan kunci publik, bukan data sidik jari atau wajah. Perangkat yang telah diverifikasi dapat mempertahankan login hingga 1 tahun dan bisa dicabut kapan saja.</p>
    </aside>
</div>
