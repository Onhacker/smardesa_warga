(function () {
  'use strict';

  var activeModal = null;
  var returnFocus = null;

  function isInstalledApp() {
    var standalone = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches;
    var iosStandalone = window.navigator.standalone === true;
    var trustedWebActivity = /^android-app:\/\//i.test(document.referrer || '');
    return !!(standalone || iosStandalone || trustedWebActivity);
  }

  function updateInstallVisibility() {
    var installed = isInstalledApp();
    document.querySelectorAll('[data-footer-install-panel]').forEach(function (panel) {
      panel.hidden = installed;
      panel.setAttribute('aria-hidden', installed ? 'true' : 'false');
    });
  }

  function focusableElements(modal) {
    return Array.prototype.slice.call(modal.querySelectorAll('a[href], button:not([disabled])')).filter(function (element) {
      return !element.hidden && element.tabIndex >= 0 && element.getAttribute('aria-hidden') !== 'true';
    });
  }

  function openModal(modal, trigger) {
    if (!modal) return;
    if (activeModal && activeModal !== modal) closeModal(activeModal, false);
    activeModal = modal;
    returnFocus = trigger || document.activeElement;
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('warga-dialog-open');
    var focusable = focusableElements(modal);
    if (focusable.length) focusable[focusable.length > 1 ? 1 : 0].focus();
  }

  function closeModal(modal, restoreFocus) {
    if (!modal) return;
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    if (activeModal === modal) activeModal = null;
    document.body.classList.remove('warga-dialog-open');
    if (restoreFocus !== false && returnFocus && typeof returnFocus.focus === 'function') returnFocus.focus();
    returnFocus = null;
  }

  function isIosDevice() {
    var userAgent = window.navigator.userAgent || '';
    return /iphone|ipad|ipod/i.test(userAgent) ||
      (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
  }

  function isIosSafari() {
    var userAgent = window.navigator.userAgent || '';
    return isIosDevice() && /safari/i.test(userAgent) && !/(crios|fxios|edgios|opios)/i.test(userAgent);
  }

  document.addEventListener('click', function (event) {
    var shareButton = event.target.closest('[data-footer-share-open]');
    if (shareButton) {
      event.preventDefault();
      openModal(document.getElementById(shareButton.getAttribute('aria-controls')), shareButton);
      return;
    }

    var iosButton = event.target.closest('[data-footer-ios-install]');
    if (iosButton) {
      event.preventDefault();
      var modal = document.getElementById(iosButton.getAttribute('aria-controls'));
      var note = modal && modal.querySelector('[data-footer-ios-note]');
      if (note && !isIosDevice()) note.textContent = 'Buka alamat aplikasi ini di Safari pada iPhone atau iPad, lalu ikuti langkah berikut.';
      else if (note && !isIosSafari()) note.textContent = 'Buka halaman ini menggunakan Safari, lalu ikuti langkah berikut.';
      else if (note) note.textContent = 'Di Safari, ikuti tiga langkah berikut untuk memasang aplikasi.';
      openModal(modal, iosButton);
      return;
    }

    var closeButton = event.target.closest('[data-footer-modal-close]');
    if (closeButton) {
      event.preventDefault();
      closeModal(closeButton.closest('[data-footer-modal]'));
    }
  });

  document.addEventListener('keydown', function (event) {
    if (!activeModal) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      closeModal(activeModal);
      return;
    }
    if (event.key !== 'Tab') return;
    var focusable = focusableElements(activeModal);
    if (!focusable.length) return;
    var first = focusable[0];
    var last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  updateInstallVisibility();
  window.addEventListener('pageshow', updateInstallVisibility);
  window.addEventListener('appinstalled', updateInstallVisibility);
  if (window.matchMedia) {
    var displayMode = window.matchMedia('(display-mode: standalone)');
    if (typeof displayMode.addEventListener === 'function') displayMode.addEventListener('change', updateInstallVisibility);
    else if (typeof displayMode.addListener === 'function') displayMode.addListener(updateInstallVisibility);
  }
}());
