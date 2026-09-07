<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="warga-notification-onboarding" data-notification-onboarding hidden role="dialog" aria-modal="true" aria-labelledby="warga-notification-onboarding-title" aria-describedby="warga-notification-onboarding-message">
    <button type="button" class="warga-notification-onboarding-backdrop" data-notification-onboarding-close aria-label="Tutup informasi notifikasi"></button>
    <div class="warga-notification-onboarding-panel">
        <span class="warga-notification-onboarding-icon" aria-hidden="true"><i class="fa fa-bell"></i></span>
        <p class="warga-notification-onboarding-eyebrow">NOTIFIKASI APLIKASI</p>
        <h2 id="warga-notification-onboarding-title">Aktifkan notifikasi layanan</h2>
        <p id="warga-notification-onboarding-message" data-notification-onboarding-message>Izinkan notifikasi agar Anda segera mengetahui status surat dan pembaruan layanan Anda.</p>
        <p class="warga-notification-onboarding-note" data-notification-onboarding-note><i class="fa fa-shield-alt" aria-hidden="true"></i>Notifikasi hanya digunakan untuk pembaruan layanan akun Anda.</p>
        <div class="warga-notification-onboarding-actions">
            <button type="button" class="btn warga-notification-onboarding-later" data-notification-onboarding-close>Nanti saja</button>
            <button type="button" class="btn bg-blue-dark color-white" data-notification-onboarding-enable><i class="fa fa-bell" aria-hidden="true"></i><span data-notification-onboarding-enable-label>Izinkan Notifikasi</span></button>
        </div>
    </div>
</div>
