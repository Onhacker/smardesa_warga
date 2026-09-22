<?php defined('BASEPATH') OR exit('No direct script access allowed');
$showBackButton = !empty($showBackButton);
$backUrl = isset($backUrl) && trim((string) $backUrl) !== ''
    ? (string) $backUrl
    : site_url(!empty($staffMode) ? 'petugas' : 'permohonan');
$notificationHeaderClass = $showBackButton ? 'header-icon-3' : 'header-icon-4';
$notificationUrl = !empty($isAuthenticated) ? site_url('notifikasi') : site_url('login');
$notificationLabel = !empty($isAuthenticated) ? 'Buka pemberitahuan' : 'Masuk untuk melihat pemberitahuan';
$navSection = $this->uri->segment(1) ?: 'dashboard';
$branding = isset($branding) && is_array($branding) ? $branding : array('nama_sistem' => 'SIDAPULIK', 'kepanjangan' => '', 'tagline' => 'Bersama Membangun Kampung Digital');
$brandName = trim((string) ($branding['nama_sistem'] ?? 'SIDAPULIK')) ?: 'SIDAPULIK';
?>
<!DOCTYPE HTML>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#235fa4">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <title><?= e($pageTitle) ?> | <?= e($brandName) ?></title>
    <?php $this->load->view('layouts/social_meta', array(
        'shareTitle' => $shareTitle,
            'shareDescription' => $shareDescription,
            'shareUrl' => $shareUrl,
            'branding' => $branding,
        'shareImage' => $shareImage,
        'shareImageAlt' => $shareImageAlt,
        'shareImageWidth' => $shareImageWidth,
        'shareImageHeight' => $shareImageHeight
    )); ?>
    <link rel="stylesheet" type="text/css" href="<?= warga_asset_url('assets/v22/styles/bootstrap-warga.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= warga_asset_url('assets/v22/fonts/css/fontawesome-subset.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= warga_asset_url('assets/css/simp-v22.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= warga_asset_url('assets/css/warga.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= warga_asset_url('assets/css/footer-share.css') ?>">
    <?php if (!empty($loadPasskeyScript)): ?><link rel="stylesheet" type="text/css" href="<?= warga_asset_url('assets/css/account-security.css') ?>"><?php endif; ?>
    <link rel="stylesheet" data-lazy-style="notification-core" href="<?= warga_asset_url('assets/css/warga-notification-core.min.css') ?>">
    <?php if (!empty($loadCommunityStyles)): ?><link rel="stylesheet" href="<?= warga_asset_url('assets/css/community.min.css') ?>"><?php endif; ?>
    <?php if (!empty($loadNotificationStyles)): ?><link rel="stylesheet" data-lazy-style="notification-center" href="<?= warga_asset_url('assets/css/warga-notifications.min.css') ?>"><?php endif; ?>
    <?php if (!empty($loadMarketplaceStyles)): ?><link rel="stylesheet" href="<?= warga_asset_url('assets/css/market.css') ?>"><?php endif; ?>
    <style id="warga-letters-icon-override">
        body #page .page-content .warga-letters-head .warga-intro-icon,
        body #page .page-content .warga-letters-head .warga-intro-icon > i {
            color: #fff !important;
        }
    </style>
    <link rel="manifest" href="<?= site_url('manifest') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= warga_asset_url('assets/pwa/icon-192.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= warga_asset_url('assets/pwa/icon-180.png') ?>">
</head>
<body class="theme-light" data-highlight="highlight-teal" data-base-url="<?= e(base_url()) ?>" data-csrf-name="<?= e($this->security->get_csrf_token_name()) ?>" data-csrf-hash="<?= e($this->security->get_csrf_hash()) ?>">
<?php $this->load->view('layouts/page_skeleton'); ?>
<div id="page">
    <header class="header header-fixed header-logo-center header-auto-show">
        <?php if ($showBackButton): ?>
            <a href="<?= e($backUrl) ?>" class="header-icon header-icon-1" aria-label="Kembali"><i class="fas fa-chevron-left"></i></a>
        <?php else: ?>
            <a href="#" data-menu="menu-main" class="header-icon header-icon-1" aria-label="Buka menu"><i class="fas fa-bars"></i></a>
        <?php endif; ?>
        <a href="<?= site_url(warga_home_route($currentUser)) ?>" class="header-title"><?= e($pageTitle) ?></a>
        <?php if ($showBackButton): ?><a href="#" data-menu="menu-main" class="header-icon header-icon-4" aria-label="Buka menu"><i class="fas fa-bars"></i></a><?php endif; ?>
        <a href="<?= $notificationUrl ?>" class="header-icon <?= $notificationHeaderClass ?> warga-header-notification" aria-label="<?= e($notificationLabel) ?>"<?= !empty($isAuthenticated) ? ' data-notification-center-trigger aria-haspopup="dialog" aria-controls="warga-notification-center"' : '' ?>><i class="fas fa-bell" aria-hidden="true"></i><span class="badge bg-red-dark" data-notification-count hidden></span></a>
    </header>

    <?php $footerIsAuthenticated = !empty($isAuthenticated) && is_array($currentUser); ?>
    <nav id="footer-bar" class="footer-bar-6 warga-footer <?= $staffMode ? 'is-staff' : '' ?>" aria-label="Navigasi utama">
        <a class="<?= $navSection === 'dashboard' || ($staffMode && $navSection === 'petugas' && !$this->input->get('status')) ? 'active-nav' : '' ?>" href="<?= site_url($staffMode ? 'petugas' : 'dashboard') ?>"><i class="fa fa-home"></i><span>Beranda</span></a>
        <a class="<?= in_array($navSection, array('surat','permohonan','layanan','notifikasi')) || ($staffMode && $navSection === 'petugas' && $this->input->get('status')) ? 'active-nav' : '' ?>" href="<?= site_url($staffMode ? 'petugas?status=submitted' : 'surat') ?>"><i class="fa fa-envelope"></i><span>Surat</span></a>
        <a class="circle-nav <?= in_array($navSection, array('pasar', 'pasar-digital', 'marketplace', 'tokoku'), TRUE) ? 'active-nav' : '' ?>" href="<?= site_url('pasar') ?>"><i class="fa fa-store"></i><span>Pasar</span></a>
        <a class="<?= $navSection === 'pengumuman' ? 'active-nav' : '' ?>" href="<?= site_url('pengumuman') ?>"><i class="fa fa-bullhorn"></i><span>Info</span></a>
        <a class="<?= $footerIsAuthenticated && in_array($navSection,array('akun','kontak')) ? 'active-nav' : '' ?>" href="<?= site_url($footerIsAuthenticated ? 'akun' : 'login') ?>"><i class="fa fa-<?= $footerIsAuthenticated ? 'user' : 'sign-in-alt' ?>"></i><span><?= $footerIsAuthenticated ? 'Akun' : 'Login' ?></span></a>
    </nav>

    <section class="page-title page-title-fixed warga-page-title<?= $showBackButton ? ' has-back' : '' ?>" aria-label="Judul halaman">
        <?php if ($showBackButton): ?>
            <a href="<?= e($backUrl) ?>" class="page-title-icon shadow-xl bg-theme color-theme warga-page-title-back" aria-label="Kembali"><i class="fa fa-arrow-left"></i></a>
        <?php else: ?>
            <a href="#" data-menu="menu-main" class="page-title-icon shadow-xl bg-theme color-theme warga-page-title-menu" aria-label="Buka menu"><i class="fa fa-bars"></i></a>
        <?php endif; ?>
        <h1><?= e($pageTitle) ?></h1>
        <a href="<?= $notificationUrl ?>" class="page-title-icon shadow-xl bg-theme color-theme warga-header-notification" aria-label="<?= e($notificationLabel) ?>"<?= !empty($isAuthenticated) ? ' data-notification-center-trigger aria-haspopup="dialog" aria-controls="warga-notification-center"' : '' ?>><i class="fa fa-bell" aria-hidden="true"></i><span class="badge bg-red-dark" data-notification-count hidden></span></a>
        <?php if ($showBackButton): ?><a href="#" data-menu="menu-main" class="page-title-icon shadow-xl bg-theme color-theme" aria-label="Buka menu"><i class="fa fa-bars"></i></a><?php endif; ?>
    </section>
    <div class="page-title-clear" aria-hidden="true"></div>

    <main class="page-content">
        <?php $flashSuccess = $this->session->flashdata('success'); ?>
        <?php $flashError = $this->session->flashdata('error'); ?>
        <?php if ($flashSuccess): ?>
            <?php $this->load->view('layouts/flash_alert', array('flashType' => 'success', 'flashTitle' => 'Berhasil', 'flashMessage' => $flashSuccess)); ?>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <?php $this->load->view('layouts/flash_alert', array('flashType' => 'error', 'flashTitle' => 'Perlu diperbaiki', 'flashMessage' => $flashError)); ?>
        <?php endif; ?>
        <?php $this->load->view($contentView); ?>
        <?php $this->load->view('layouts/site_footer', array(
            'footerVillage' => $footerVillage,
            'currentUser' => $currentUser,
            'shareTitle' => $shareTitle,
            'shareDescription' => $shareDescription,
            'shareUrl' => $shareUrl,
            'branding' => $branding
        )); ?>
    </main>

    <aside id="menu-main" class="menu menu-box-left rounded-0" data-menu-width="300">
        <?php $this->load->view('layouts/menu', array('currentUser' => $currentUser, 'footerVillage' => $footerVillage, 'staffMode' => $staffMode, 'institutionLabel' => $institutionLabel, 'canManageMarketplace' => !empty($canManageMarketplace), 'branding' => $branding)); ?>
    </aside>
    <div class="menu-hider"></div>
    <?php if (!empty($isAuthenticated)): ?><?php $this->load->view('layouts/notification_center'); ?><?php endif; ?>
    <?php $this->load->view('layouts/notification_onboarding'); ?>
</div>
<script>window.SDW={baseUrl:<?= json_encode(base_url()) ?>,brandName:<?= json_encode($brandName) ?>,csrfName:<?= json_encode($this->security->get_csrf_token_name()) ?>,csrfHash:<?= json_encode($this->security->get_csrf_hash()) ?>,isAuthenticated:<?= !empty($isAuthenticated) ? 'true' : 'false' ?>,serviceWorkerUrl:<?= json_encode(warga_asset_url('service-worker.js')) ?>,serviceWorkerScope:<?= json_encode(base_url()) ?>,passkeyEndpoints:<?= json_encode(array('status'=>site_url('passkey/status'),'registerOptions'=>site_url('passkey/register/options'),'register'=>site_url('passkey/register'),'revoke'=>site_url('passkey/revoke'),'pinEnable'=>site_url('passkey/pin/enable'),'pinDisable'=>site_url('passkey/pin/disable'),'loginOptions'=>site_url('passkey/login/options'),'login'=>site_url('passkey/login'),'pinLogin'=>site_url('passkey/pin/login'))) ?>};</script>
<script>window.SDW.vapidPublicKey=<?= json_encode(trim((string)getenv('WARGA_VAPID_PUBLIC_KEY'))) ?>;</script>
<script src="<?= warga_asset_url('assets/v22/scripts/appkit-core.min.js') ?>"></script>
<script src="<?= warga_asset_url('assets/v22/scripts/custom.min.js') ?>"></script>
<script src="<?= warga_asset_url('assets/js/warga.min.js') ?>"></script>
<?php if (!empty($loadCommunityScript)): ?>
<script src="<?= warga_asset_url('assets/js/community.min.js') ?>"></script>
<?php else: ?>
<script src="<?= warga_asset_url('assets/js/notifications.min.js') ?>"></script>
<?php endif; ?>
<?php if (!empty($loadMarketplaceScript)): ?><script src="<?= warga_asset_url('assets/js/market.js') ?>"></script><?php endif; ?>
<?php if (!empty($loadComplaintPaginationScript)): ?><script src="<?= warga_asset_url('assets/js/complaint-pagination.min.js') ?>"></script><?php endif; ?>
<?php if (!empty($loadPasskeyScript)): ?><script src="<?= warga_asset_url('assets/js/passkey.js') ?>"></script><?php endif; ?>
<script>
(function () {
    'use strict';

    /*
     * The notification center, footer dialogs, and the marketplace runtime
     * are interaction-only features on most screens.  Keep them out of the
     * critical path and load each bundle only when a matching control is
     * actually used.  The click is replayed after the bundle has initialised
     * so the first tap behaves exactly like a normal, eagerly loaded script.
     */
    var config = window.SDW = window.SDW || {};
    var lazyScripts = config.__lazyScripts = config.__lazyScripts || {};
    var lazyStyles = config.__lazyStyles = config.__lazyStyles || {};
    var notificationSrc = <?= json_encode(warga_asset_url('assets/js/notification-center.min.js')) ?>;
    var notificationCssSrc = <?= json_encode(warga_asset_url('assets/css/warga-notifications.min.css')) ?>;
    var footerSrc = <?= json_encode(warga_asset_url('assets/js/footer-actions.js')) ?>;
    var marketplaceSrc = <?= json_encode(warga_asset_url('assets/js/market.js')) ?>;
    var marketplaceAlreadyLoaded = <?= !empty($loadMarketplaceScript) ? 'true' : 'false' ?>;

    function loadScript(name, source) {
        if (lazyScripts[name]) return lazyScripts[name];
        lazyScripts[name] = new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            script.src = source;
            script.async = true;
            script.onload = function () { resolve(script); };
            script.onerror = function () {
                delete lazyScripts[name];
                reject(new Error('Fitur belum dapat dimuat.'));
            };
            (document.head || document.documentElement).appendChild(script);
        });
        return lazyScripts[name];
    }

    function loadStylesheet(name, source) {
        if (lazyStyles[name]) return lazyStyles[name];
        lazyStyles[name] = new Promise(function (resolve, reject) {
            var existing = document.querySelector('link[data-lazy-style="' + name + '"]');
            if (existing) { resolve(existing); return; }
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = source;
            link.dataset.lazyStyle = name;
            link.onload = function () { resolve(link); };
            link.onerror = function () {
                delete lazyStyles[name];
                reject(new Error('Gaya fitur belum dapat dimuat.'));
            };
            (document.head || document.documentElement).appendChild(link);
        });
        return lazyStyles[name];
    }

    function replayClick(target) {
        if (!target || !document.documentElement.contains(target)) return;
        var event;
        try {
            event = new MouseEvent('click', {bubbles: true, cancelable: true, view: window, button: 0});
        } catch (error) {
            event = document.createEvent('MouseEvents');
            event.initMouseEvent('click', true, true, window, 1, 0, 0, 0, 0, false, false, false, false, 0, null);
        }
        target.dispatchEvent(event);
    }

    function closestTarget(target, selector) {
        return target && typeof target.closest === 'function' ? target.closest(selector) : null;
    }

    /* Notification center/search: preserve the ordinary link when there is no
     * unread badge, but open the AJAX dialog immediately when one is present. */
    var notificationTriggers = document.querySelectorAll('[data-notification-center-trigger], [data-notification-search-open]');
    if (notificationTriggers.length) {
        var replayingNotificationClick = false;
        document.addEventListener('click', function (event) {
            if (replayingNotificationClick) return;
            var trigger = closestTarget(event.target, '[data-notification-center-trigger], [data-notification-search-open]');
            if (!trigger) return;
            var isCenter = trigger.hasAttribute('data-notification-center-trigger');
            if (isCenter) {
                var badge = document.querySelector('[data-notification-count]:not([hidden])');
                if (!badge || Math.max(0, parseInt(badge.textContent, 10) || 0) < 1) return;
            }
            if (event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            loadStylesheet('notification-center', notificationCssSrc).then(function () {
                return loadScript('notification-center', notificationSrc);
            }).then(function () {
                replayingNotificationClick = true;
                try { replayClick(trigger); } finally { replayingNotificationClick = false; }
            }).catch(function () {
                /* A failed enhancement must retain the original navigation. */
                if (isCenter && trigger.href) window.location.assign(trigger.href);
            });
        }, true);
    }

    /* Footer contact/share/install dialogs are not needed to paint the page.
     * Keep only the tiny installed-mode synchroniser inline so the install
     * panel does not flash for users who already installed the PWA. */
    var footerControls = document.querySelectorAll('[data-footer-contact-open], [data-footer-share-open], [data-footer-ios-install]');
    var installPanels = document.querySelectorAll('[data-footer-install-panel]');
    function isInstalledExperience() {
        var standalone = false;
        try {
            standalone = !!(window.matchMedia && (
                window.matchMedia('(display-mode: standalone)').matches ||
                window.matchMedia('(display-mode: minimal-ui)').matches ||
                window.matchMedia('(display-mode: fullscreen)').matches ||
                window.matchMedia('(display-mode: window-controls-overlay)').matches
            ));
        } catch (error) {}
        return standalone || window.navigator.standalone === true || /^android-app:\/\//i.test(document.referrer || '');
    }
    function syncInstallPanels() {
        var installed = isInstalledExperience();
        installPanels.forEach(function (panel) {
            panel.hidden = installed;
            panel.setAttribute('aria-hidden', installed ? 'true' : 'false');
        });
    }
    if (installPanels.length) {
        syncInstallPanels();
        window.addEventListener('pageshow', syncInstallPanels);
        window.addEventListener('appinstalled', syncInstallPanels);
    }
    if (footerControls.length) {
        var replayingFooterClick = false;
        document.addEventListener('click', function (event) {
            if (replayingFooterClick) return;
            var trigger = closestTarget(event.target, '[data-footer-contact-open], [data-footer-share-open], [data-footer-ios-install]');
            if (!trigger || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            loadScript('footer-actions', footerSrc).then(function () {
                replayingFooterClick = true;
                try { replayClick(trigger); } finally { replayingFooterClick = false; }
            }).catch(function () {});
        }, true);
    }

    /* Home renders product cards but not the interactive catalogue.  Load the
     * market runtime only if a resident actually opens a rating/review modal. */
    if (!marketplaceAlreadyLoaded && document.querySelector('[data-market-review-open]')) {
        var replayingMarketClick = false;
        document.addEventListener('click', function (event) {
            if (replayingMarketClick) return;
            var trigger = closestTarget(event.target, '[data-market-review-open]');
            if (!trigger || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            loadScript('marketplace', marketplaceSrc).then(function () {
                replayingMarketClick = true;
                try { replayClick(trigger); } finally { replayingMarketClick = false; }
            }).catch(function () {});
        }, true);
    }
}());
</script>
</body>
</html>
