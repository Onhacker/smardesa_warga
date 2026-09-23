(function () {
  'use strict';

  var config = window.SDW || {};
  var base = config.baseUrl || '/';
  var endpoints = config.passkeyEndpoints || {};

  function endpointUrl(path) {
    try { return new URL(String(path || ''), base).toString(); }
    catch (error) { return base + String(path || '').replace(/^\//, ''); }
  }

  function updateCsrf(data) {
    if (!data || !data.csrf || !data.csrf.name || !data.csrf.hash) return;
    config.csrfName = data.csrf.name;
    config.csrfHash = data.csrf.hash;
    document.querySelectorAll('input[name="' + String(data.csrf.name).replace(/["\\]/g, '\\$&') + '"]').forEach(function (input) {
      input.value = data.csrf.hash;
    });
  }

  function post(path, values) {
    var data = new URLSearchParams();
    Object.keys(values || {}).forEach(function (key) {
      data.set(key, values[key] === undefined || values[key] === null ? '' : String(values[key]));
    });
    if (config.csrfName && config.csrfHash) data.set(config.csrfName, config.csrfHash);
    return fetch(endpointUrl(path), {
      method: 'POST', credentials: 'same-origin', body: data, cache: 'no-store',
      headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
    }).then(function (response) {
      return response.text().then(function (text) {
        var body = null;
        try { body = text ? JSON.parse(text) : null; } catch (error) {}
        updateCsrf(body);
        if (!response.ok) {
          var failure = new Error(body && body.message ? body.message : 'Permintaan belum dapat diproses.');
          failure.status = response.status;
          throw failure;
        }
        return body || {};
      });
    }).then(function (body) {
      if (body && body.success === false) throw new Error(body.message || 'Permintaan belum dapat diproses.');
      return body;
    });
  }

  function b64ToBytes(value) {
    var text = String(value || '').replace(/-/g, '+').replace(/_/g, '/');
    while (text.length % 4) text += '=';
    var binary = atob(text);
    var bytes = new Uint8Array(binary.length);
    for (var i = 0; i < binary.length; i += 1) bytes[i] = binary.charCodeAt(i);
    return bytes;
  }

  function bytesToB64(value) {
    var bytes = value instanceof ArrayBuffer ? new Uint8Array(value) : new Uint8Array(value || []);
    var binary = '';
    for (var i = 0; i < bytes.length; i += 1) binary += String.fromCharCode(bytes[i]);
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
  }

  function prepareCreation(options) {
    var publicKey = options && options.publicKey ? options.publicKey : options;
    if (!publicKey) throw new Error('Opsi Passkey tidak valid.');
    publicKey.challenge = b64ToBytes(publicKey.challenge);
    if (publicKey.user && publicKey.user.id) publicKey.user.id = b64ToBytes(publicKey.user.id);
    (publicKey.excludeCredentials || []).forEach(function (item) { item.id = b64ToBytes(item.id); });
    return publicKey;
  }

  function prepareRequest(options) {
    var publicKey = options && options.publicKey ? options.publicKey : options;
    if (!publicKey) throw new Error('Opsi Passkey tidak valid.');
    publicKey.challenge = b64ToBytes(publicKey.challenge);
    (publicKey.allowCredentials || []).forEach(function (item) { item.id = b64ToBytes(item.id); });
    return publicKey;
  }

  function credentialPayload(credential) {
    if (!credential || !credential.response) throw new Error('Perangkat tidak mengembalikan credential.');
    var response = credential.response;
    var body = {
      id: credential.id || bytesToB64(credential.rawId),
      rawId: bytesToB64(credential.rawId),
      type: credential.type || 'public-key',
      response: {}
    };
    ['clientDataJSON', 'attestationObject', 'authenticatorData', 'signature', 'userHandle'].forEach(function (key) {
      if (response[key]) body.response[key] = bytesToB64(response[key]);
    });
    return body;
  }

  function setMessage(selector, message, isError) {
    var element = document.querySelector(selector);
    if (!element) return;
    element.textContent = message || '';
    element.hidden = !message;
    element.classList.toggle('is-error', !!isError);
    element.classList.toggle('is-success', !isError && !!message);
  }

  function setBusy(button, busy, label) {
    if (!button) return;
    if (busy) {
      button.__passkeyContent = button.innerHTML;
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      button.innerHTML = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i><span>' + (label || 'Memproses…') + '</span>';
    } else {
      button.disabled = false;
      button.removeAttribute('aria-busy');
      if (button.__passkeyContent) button.innerHTML = button.__passkeyContent;
    }
  }

  function supported() {
    return !!(window.isSecureContext && window.PublicKeyCredential && navigator.credentials && typeof navigator.credentials.create === 'function' && typeof navigator.credentials.get === 'function');
  }

  function platformAvailable() {
    if (!supported()) return Promise.resolve(false);
    if (typeof PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable !== 'function') return Promise.resolve(true);
    return PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().catch(function () { return false; });
  }

  function redirectAfterLogin(body) {
    window.location.assign(body && body.redirect ? body.redirect : (base + 'dashboard'));
  }

  function initAccount() {
    var card = document.querySelector('[data-passkey-security]');
    if (!card) return;
    var registerButton = card.querySelector('[data-passkey-register]');
    var pinForm = card.querySelector('[data-pin-form]');
    var pinDisable = card.querySelector('[data-pin-disable]');
    var password = card.querySelector('[data-security-current-password]');
    var statusNode = card.querySelector('[data-passkey-status]');
    var pinStatusNode = card.querySelector('[data-pin-status]');
    var deviceList = card.querySelector('[data-passkey-devices]');
    if (!registerButton && !pinForm) return;

    function renderStatus(status) {
      status = status || {};
      var passkeys = status.passkeys || [];
      var available = status.available !== false;
      if (!available) {
        if (statusNode) {
          statusNode.textContent = 'Aktif setelah migrasi keamanan server selesai.';
          statusNode.classList.remove('is-success');
        }
        if (pinStatusNode) {
          pinStatusNode.textContent = 'PIN belum tersedia pada server.';
          pinStatusNode.classList.remove('is-success');
        }
        if (registerButton) registerButton.hidden = true;
        if (pinForm) pinForm.querySelectorAll('input,button').forEach(function (field) { field.disabled = true; });
        if (pinDisable) pinDisable.hidden = true;
        return;
      }
      if (statusNode) {
        statusNode.textContent = passkeys.length ? passkeys.length + ' perangkat terdaftar.' : 'Belum diaktifkan.';
        statusNode.classList.toggle('is-success', passkeys.length > 0);
      }
      if (pinStatusNode) {
        pinStatusNode.textContent = status.pin_enabled ? 'PIN aktif.' : 'PIN belum diaktifkan.';
        pinStatusNode.classList.toggle('is-success', !!status.pin_enabled);
      }
      if (pinDisable) pinDisable.hidden = !status.pin_enabled;
      if (deviceList) {
        deviceList.innerHTML = '';
        passkeys.forEach(function (device) {
          var row = document.createElement('div');
          row.className = 'warga-passkey-device';
          row.innerHTML = '<span><i class="fa fa-mobile-alt" aria-hidden="true"></i><span class="warga-passkey-device-copy"><strong></strong><small></small></span></span><button type="button" class="btn btn-xs border-red-dark color-red-dark" data-passkey-revoke="' + String(device.id) + '">Cabut</button>';
          row.querySelector('strong').textContent = device.label || 'Perangkat';
          row.querySelector('small').textContent = device.last_used_at ? 'Terakhir digunakan ' + device.last_used_at : 'Belum digunakan';
          deviceList.appendChild(row);
        });
      }
    }

    function refresh() {
      if (!endpoints.status) return;
      fetch(endpointUrl(endpoints.status), {credentials: 'same-origin', cache: 'no-store', headers: {'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json'}})
        .then(function (response) { return response.json(); })
        .then(function (body) { if (body && body.status) renderStatus(body.status); })
        .catch(function () {});
    }

    platformAvailable().then(function (available) {
      if (registerButton) registerButton.hidden = !available;
      if (!available && statusNode && !supported()) statusNode.textContent = 'Perangkat/browser ini belum mendukung login biometrik.';
    });

    if (registerButton) registerButton.addEventListener('click', function () {
      var currentPassword = password ? password.value : window.prompt('Masukkan kata sandi saat ini untuk mengaktifkan login biometrik:');
      if (!currentPassword) return;
      setBusy(registerButton, true, 'Menyiapkan…');
      setMessage('[data-passkey-message]', '', false);
      post(endpoints.registerOptions, {current_password: currentPassword})
        .then(function (body) { return navigator.credentials.create({publicKey: prepareCreation(body.options)}); })
        .then(function (credential) { return post(endpoints.register, {credential: JSON.stringify(credentialPayload(credential)), label: navigator.userAgent.indexOf('iPhone') >= 0 ? 'iPhone' : (navigator.userAgent.indexOf('Android') >= 0 ? 'Android' : 'Perangkat ini')}); })
        .then(function (body) { setMessage('[data-passkey-message]', body.message || 'Login biometrik aktif.', false); refresh(); })
        .catch(function (error) { setMessage('[data-passkey-message]', error && error.name === 'NotAllowedError' ? 'Verifikasi dibatalkan atau tidak diselesaikan.' : (error.message || 'Login biometrik belum dapat diaktifkan.'), true); })
        .finally(function () { setBusy(registerButton, false); });
    });

    if (pinForm) pinForm.addEventListener('submit', function (event) {
      event.preventDefault();
      var submit = pinForm.querySelector('button[type="submit"]');
      var data = new FormData(pinForm);
      data.set('current_password', password ? password.value : data.get('current_password') || '');
      var values = {};
      data.forEach(function (value, key) { values[key] = value; });
      setBusy(submit, true, 'Menyimpan…');
      post(endpoints.pinEnable, values).then(function (body) { setMessage('[data-pin-message]', body.message || 'PIN aktif.', false); pinForm.reset(); refresh(); }).catch(function (error) { setMessage('[data-pin-message]', error.message || 'PIN belum dapat disimpan.', true); }).finally(function () { setBusy(submit, false); });
    });
    if (pinDisable) pinDisable.addEventListener('click', function () {
      var currentPassword = password ? password.value : window.prompt('Masukkan kata sandi saat ini untuk menonaktifkan PIN:');
      if (!currentPassword) return;
      setBusy(pinDisable, true, 'Menonaktifkan…');
      post(endpoints.pinDisable, {current_password: currentPassword}).then(function (body) { setMessage('[data-pin-message]', body.message || 'PIN dinonaktifkan.', false); refresh(); }).catch(function (error) { setMessage('[data-pin-message]', error.message || 'PIN belum dapat dinonaktifkan.', true); }).finally(function () { setBusy(pinDisable, false); });
    });
    if (deviceList) deviceList.addEventListener('click', function (event) {
      var button = event.target.closest('[data-passkey-revoke]');
      if (!button) return;
      var currentPassword = password ? password.value : window.prompt('Masukkan kata sandi saat ini untuk mencabut perangkat:');
      if (!currentPassword) return;
      setBusy(button, true, 'Mencabut…');
      post(endpoints.revoke, {id: button.getAttribute('data-passkey-revoke'), current_password: currentPassword}).then(function (body) { setMessage('[data-passkey-message]', body.message || 'Perangkat dicabut.', false); refresh(); }).catch(function (error) { setMessage('[data-passkey-message]', error.message || 'Perangkat belum dapat dicabut.', true); }).finally(function () { setBusy(button, false); });
    });
    refresh();
  }

  function initLogin() {
    var passkeyButton = document.querySelector('[data-passkey-login]');
    var pinToggle = document.querySelector('[data-pin-login-toggle]');
    var pinPanel = document.querySelector('[data-pin-login-panel]');
    var pinForm = document.querySelector('[data-pin-login-form]');
    if (!passkeyButton && !pinToggle && !pinForm) return;
    platformAvailable().then(function (available) { if (passkeyButton) passkeyButton.hidden = !available; });
    var previousFocus = null;
    function pinModalButtons() {
      if (!pinPanel) return [];
      return Array.prototype.slice.call(pinPanel.querySelectorAll('button:not([disabled]), input:not([disabled])')).filter(function (element) {
        return !element.hidden && element.offsetParent !== null && element.getAttribute('tabindex') !== '-1';
      });
    }
    function closePinModal(restoreFocus) {
      if (!pinPanel || pinPanel.hidden) return;
      pinPanel.hidden = true;
      pinPanel.setAttribute('aria-hidden', 'true');
      if (pinToggle) pinToggle.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('warga-dialog-open');
      var focusTarget = previousFocus;
      previousFocus = null;
      if (restoreFocus !== false && focusTarget && typeof focusTarget.focus === 'function') focusTarget.focus();
    }
    function openPinModal() {
      if (!pinPanel) return;
      previousFocus = document.activeElement;
      pinPanel.hidden = false;
      pinPanel.setAttribute('aria-hidden', 'false');
      if (pinToggle) pinToggle.setAttribute('aria-expanded', 'true');
      document.body.classList.add('warga-dialog-open');
      var error = pinPanel.querySelector('[data-pin-login-error]');
      if (error) { error.textContent = ''; error.hidden = true; }
      var input = pinPanel.querySelector('input[name="pin"]');
      if (input) {
        input.value = '';
        window.setTimeout(function () { if (!pinPanel.hidden) input.focus(); }, 30);
      }
    }
    if (pinToggle && pinPanel) pinToggle.addEventListener('click', function (event) {
      event.preventDefault();
      if (pinPanel.hidden) openPinModal(); else closePinModal(true);
    });
    if (pinPanel) {
      pinPanel.addEventListener('click', function (event) {
        var close = event.target.closest('[data-pin-login-close]');
        if (close) { event.preventDefault(); closePinModal(true); }
      });
      document.addEventListener('keydown', function (event) {
        if (!pinPanel || pinPanel.hidden) return;
        if (event.key === 'Escape') { event.preventDefault(); closePinModal(true); return; }
        if (event.key !== 'Tab') return;
        var focusable = pinModalButtons();
        if (!focusable.length) return;
        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
      });
    }
    if (passkeyButton) passkeyButton.addEventListener('click', function () {
      setBusy(passkeyButton, true, 'Menunggu…');
      setMessage('[data-login-auth-message]', '', false);
      post(endpoints.loginOptions, {identity: ''})
        .then(function (body) { return navigator.credentials.get({publicKey: prepareRequest(body.options)}); })
        .then(function (credential) { return post(endpoints.login, {credential: JSON.stringify(credentialPayload(credential))}); })
        .then(redirectAfterLogin)
        .catch(function (error) { setMessage('[data-login-auth-message]', error && error.name === 'NotAllowedError' ? 'Verifikasi dibatalkan atau waktunya habis.' : (error.message || 'Login biometrik belum berhasil.'), true); })
        .finally(function () { setBusy(passkeyButton, false); });
    });
    if (pinForm) pinForm.addEventListener('submit', function (event) {
      event.preventDefault();
      var submit = pinForm.querySelector('button[type="submit"]');
      var pinInput = pinForm.querySelector('input[name="pin"]');
      setBusy(submit, true, 'Memeriksa…');
      var pinError = pinForm.querySelector('[data-pin-login-error]');
      if (pinError) { pinError.textContent = ''; pinError.hidden = true; }
      post(endpoints.pinLogin, {identity: '', pin: pinInput ? pinInput.value : ''}).then(redirectAfterLogin).catch(function (error) {
        var message = error.message || 'Login PIN belum berhasil.';
        if (pinError) { pinError.textContent = message; pinError.hidden = false; }
      }).finally(function () { setBusy(submit, false); });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { initAccount(); initLogin(); });
  else { initAccount(); initLogin(); }
}());
