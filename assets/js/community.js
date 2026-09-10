(function () {
  'use strict';
  // AppKit v22's .double-slider uses perPage: 2, which makes landscape cards
  // fill almost half of a wide viewport. Keep its loop/autoplay behavior, but
  // give the home slider a compact, responsive card width like the original
  // v22 showcase. This wrapper runs before custom.min.js handles DOM ready and
  // leaves every other Splide instance untouched.
  (function tuneCommunitySplide() {
    var OriginalSplide = window.Splide;
    if (!OriginalSplide || window.__SDWCommunitySplideTuned) return;
    function compactSplide(root, options) {
      var element = typeof root === 'string' ? document.querySelector(root) : root;
      var tunedOptions = options ? Object.assign({}, options) : {};
      if (element && element.id === 'community-services-slider') {
        var viewport = window.innerWidth || document.documentElement.clientWidth || 360;
        var width = viewport <= 480 ? Math.min(316, Math.max(270, Math.round(viewport * .81))) : Math.min(360, Math.max(300, Math.round(viewport * .38)));
        tunedOptions.autoWidth = false;
        tunedOptions.fixedWidth = width;
        tunedOptions.perPage = 1;
        tunedOptions.gap = '14px';
        tunedOptions.padding = {left: 0, right: viewport <= 480 ? 42 : 70};
      }
      return new OriginalSplide(root, tunedOptions);
    }
    compactSplide.prototype = OriginalSplide.prototype;
    try { Object.setPrototypeOf(compactSplide, OriginalSplide); } catch (_) {}
    window.Splide = compactSplide;
    window.__SDWCommunitySplideTuned = true;
  }());
  var config = window.SDW || {}, base = config.baseUrl || '/', isAuthenticated = config.isAuthenticated === true || config.isAuthenticated === 1 || config.isAuthenticated === '1';
  // The account page and the navigation drawer can both expose the same
  // device-level preference. Keep every visible switch in sync instead of
  // binding only the first one found in the DOM.
  var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-push-toggle]'));
  var checkboxes = buttons.filter(function (control) { return control.matches('input[type="checkbox"]'); });
  var statuses = Array.prototype.slice.call(document.querySelectorAll('[data-push-status]'));
  var onboarding = document.querySelector('[data-notification-onboarding]');
  var subscribed = false;
  var onboardingKey = 'sdw-notification-onboarding-v1';
  function message(text) { statuses.forEach(function (node) { node.textContent = text; }); }
  function isInstalledExperience() {
    var installedMode = false;
    try {
      installedMode = ['standalone', 'minimal-ui', 'fullscreen', 'window-controls-overlay'].some(function (mode) {
        return !!(window.matchMedia && window.matchMedia('(display-mode: ' + mode + ')').matches);
      });
    } catch (_) {}
    return installedMode || window.navigator.standalone === true || /^android-app:\/\//i.test(document.referrer || '');
  }
  function permissionMessage(permission) {
    if (permission === 'denied') {
      var standalone = isInstalledExperience();
      return standalone
        ? 'Izin pemberitahuan belum tersinkron. Pastikan Info aplikasi > Pemberitahuan aktif, kembali ke aplikasi, lalu aktifkan sakelar. Jika masih gagal, buka ulang aplikasi.'
        : 'Izin diblokir oleh browser. Buka Pengaturan situs > Pemberitahuan, pilih Izinkan, lalu coba lagi.';
    }
    if (permission === 'default') return 'Klik sakelar untuk memberi izin pemberitahuan pada browser.';
    return '';
  }
  function storageGet(key) {
    try { return window.localStorage.getItem(key); } catch (_) { return null; }
  }
  function storageSet(key, value) {
    try { window.localStorage.setItem(key, value); } catch (_) {}
  }
  function updateCsrf(csrf) {
    if (!csrf || !csrf.name || !csrf.hash) return;
    config.csrfName = csrf.name;
    config.csrfHash = csrf.hash;
    document.querySelectorAll('input[name="' + String(csrf.name).replace(/["\\]/g, '\\$&') + '"]').forEach(function (input) {
      input.value = csrf.hash;
    });
  }
  function post(path, values) {
    var data = new URLSearchParams(values);
    data.set(config.csrfName, config.csrfHash);
    return fetch(base + path, {method:'POST', credentials:'same-origin', body:data, cache:'no-store', headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
      .then(function (r) {
        return r.text().then(function (text) {
          var payload = null;
          try { payload = text ? JSON.parse(text) : null; } catch (_) {}
          if (!r.ok) throw new Error(payload && payload.message ? payload.message : 'Permintaan belum dapat disimpan. Muat ulang halaman lalu coba lagi.');
          return payload || {};
        });
      })
      .then(function (data) { updateCsrf(data.csrf); return data; });
  }
  function keyBytes(value) {
    var decoded = atob(value.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4-value.length%4)%4));
    return Uint8Array.from(decoded, function (char) { return char.charCodeAt(0); });
  }
  function updateButton(active) {
    subscribed = !!active;
    buttons.forEach(function (control) {
      if (control.matches('input[type="checkbox"]')) {
        control.checked = subscribed;
        control.setAttribute('aria-checked', subscribed ? 'true' : 'false');
        control.setAttribute('aria-label', subscribed ? 'Matikan pemberitahuan' : 'Nyalakan pemberitahuan');
      } else {
        control.innerHTML = '<i class="fa fa-bell"></i> ' + (subscribed ? 'Nonaktifkan Pemberitahuan' : 'Aktifkan Pemberitahuan');
      }
    });
  }
  function setDisabled(disabled) { buttons.forEach(function (control) { control.disabled = !!disabled; }); }
  var supported = window.isSecureContext && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
  async function enableSubscription() {
    var permission = Notification.permission;
    // Keep this call in the click/change call stack so the browser can show
    // its native prompt.  Do not move it behind a timer or an unrelated fetch.
    // Android can grant the app-level permission while the web origin still
    // reports `denied` until it is queried again.  Calling requestPermission
    // from the explicit toggle action lets Chrome/Trusted Web Activity
    // resynchronise that state instead of permanently locking the switch.
    if (permission !== 'granted') {
      try { permission = await Notification.requestPermission(); }
      catch (error) { return {active:false, permission:Notification.permission, error:error}; }
    }
    if (permission !== 'granted') return {active:false, permission:permission};
    var reg = await navigator.serviceWorker.ready;
    var sub = await reg.pushManager.getSubscription();
    sub = sub || await reg.pushManager.subscribe({userVisibleOnly:true, applicationServerKey:keyBytes(config.vapidPublicKey)});
    if (isAuthenticated) await post('notifikasi/push', {subscription:JSON.stringify(sub)});
    return {active:true, permission:permission, subscription:sub};
  }
  async function disableSubscription() {
    var reg = await navigator.serviceWorker.ready;
    var sub = await reg.pushManager.getSubscription();
    if (sub) {
      // Disable the browser subscription first so a temporary server/connection
      // error cannot switch this device back on after the user's explicit tap.
      var endpoint = sub.endpoint;
      await sub.unsubscribe();
      if (await reg.pushManager.getSubscription()) {
        throw new Error('Pemberitahuan belum dapat dinonaktifkan. Coba lagi.');
      }
      if (isAuthenticated) {
        try { await post('notifikasi/push/hapus', {endpoint:endpoint}); }
        catch (_) { return {active:false, cleanupPending:true}; }
      }
    }
    return {active:false};
  }
  async function toggleSubscription(desired) {
    var previous = subscribed;
    setDisabled(true);
    try {
      if (!desired) {
        var disabledResult = await disableSubscription();
        updateButton(false);
        message(disabledResult.cleanupPending
          ? 'Pemberitahuan perangkat dinonaktifkan. Sinkronisasi server akan diperbarui saat Anda online.'
          : 'Pemberitahuan perangkat dinonaktifkan.');
        return;
      }
      var result = await enableSubscription();
      if (!result.active) {
        updateButton(previous);
        message(permissionMessage(result.permission) || 'Izin pemberitahuan belum diberikan. Periksa pengaturan situs pada browser.');
        return;
      }
      updateButton(true); message('Pemberitahuan perangkat aktif. Suara dan getar mengikuti pengaturan perangkat.');
    } catch (error) {
      updateButton(previous);
      if (Notification.permission === 'denied') message(permissionMessage('denied'));
      else message(error.message || 'Pemberitahuan belum dapat diaktifkan.');
    } finally { setDisabled(false); }
  }
  var pushRefreshTimer = 0, pushRefreshing = false;
  async function refreshPushState(rebind) {
    if (!buttons.length || !supported || !config.vapidPublicKey || pushRefreshing) return;
    pushRefreshing = true;
    try {
      var permission = Notification.permission;
      var reg = await navigator.serviceWorker.ready;
      var sub = await reg.pushManager.getSubscription();
      // A subscription left over from before the Android permission change is
      // not usable while the origin is denied.  Reflect the real state in the
      // switch, then let the next user click request/synchronise permission.
      var active = permission === 'granted' && !!sub;
      updateButton(active);
      if (active) {
        if (rebind && isAuthenticated) await post('notifikasi/push', {subscription:JSON.stringify(sub)});
      } else if (permission === 'granted') {
        message('Izin pemberitahuan sudah diberikan. Aktifkan sakelar untuk menerima pembaruan.');
      } else {
        message(permissionMessage(permission));
      }
    } catch (_) {
      message('Status pemberitahuan belum dapat diperiksa.');
    } finally { pushRefreshing = false; }
  }
  function schedulePushRefresh() {
    if (!buttons.length || document.hidden) return;
    clearTimeout(pushRefreshTimer);
    pushRefreshTimer = window.setTimeout(function () { refreshPushState(false); }, 120);
  }
  if (buttons.length) {
    if (!supported) { updateButton(false); setDisabled(true); message('Pemberitahuan perangkat tidak didukung oleh browser ini.'); }
    else if (!config.vapidPublicKey) { updateButton(false); setDisabled(true); message('Pemberitahuan perangkat belum diaktifkan oleh pengelola server.'); }
    else {
      refreshPushState(true);
      checkboxes.forEach(function (control) {
        control.addEventListener('change', function () { toggleSubscription(control.checked); });
      });
      buttons.filter(function (control) { return !control.matches('input[type="checkbox"]'); }).forEach(function (control) {
        control.addEventListener('click', function () { toggleSubscription(!subscribed); });
      });
      // Returning from Android's app-info/notification settings does not
      // reload the page.  Re-read Notification.permission and the browser
      // subscription whenever the TWA becomes visible/focused again.
      document.addEventListener('visibilitychange', function () { if (!document.hidden) schedulePushRefresh(); });
      window.addEventListener('focus', schedulePushRefresh);
      window.addEventListener('pageshow', schedulePushRefresh);
    }
  }
  // Rebind an existing subscription after switching accounts; no permission prompt.
  if (isAuthenticated && supported && config.vapidPublicKey && !buttons.length) navigator.serviceWorker.ready
    .then(function (reg) { return reg.pushManager.getSubscription(); })
    .then(function (sub) { if (sub) return post('notifikasi/push', {subscription:JSON.stringify(sub)}); }).catch(function () {});

  function closeNotificationOnboarding(markSeen) {
    if (!onboarding) return;
    if (markSeen) storageSet(onboardingKey, 'seen');
    onboarding.hidden = true;
    document.body.classList.remove('warga-dialog-open');
    var previous = onboarding.__previousFocus;
    onboarding.__previousFocus = null;
    if (previous && typeof previous.focus === 'function') previous.focus();
  }
  function showNotificationOnboarding() {
    if (!onboarding || storageGet(onboardingKey)) return;
    var blocked = Notification.permission === 'denied';
    var messageNode = onboarding.querySelector('[data-notification-onboarding-message]');
    var note = onboarding.querySelector('[data-notification-onboarding-note]');
    var enable = onboarding.querySelector('[data-notification-onboarding-enable]');
    var label = onboarding.querySelector('[data-notification-onboarding-enable-label]');
    var later = onboarding.querySelector('[data-notification-onboarding-close].warga-notification-onboarding-later');
    if (messageNode) messageNode.textContent = blocked
      ? 'Izin pemberitahuan saat ini diblokir. Aktifkan melalui Info aplikasi > Pemberitahuan agar pembaruan layanan dapat diterima.'
      : 'Izinkan pemberitahuan agar Anda segera mengetahui status surat dan pembaruan layanan Anda.';
    if (note) note.innerHTML = blocked
      ? '<i class="fa fa-info-circle" aria-hidden="true"></i>Buka pengaturan aplikasi, pilih Pemberitahuan, lalu aktifkan Izinkan pemberitahuan.'
      : '<i class="fa fa-shield-alt" aria-hidden="true"></i>Pemberitahuan hanya digunakan untuk pembaruan layanan akun Anda.';
    if (label) label.textContent = blocked ? 'Mengerti' : 'Izinkan Pemberitahuan';
    if (later) later.hidden = blocked;
    if (enable) enable.setAttribute('data-notification-onboarding-blocked', blocked ? '1' : '0');
    onboarding.__previousFocus = document.activeElement;
    onboarding.hidden = false;
    document.body.classList.add('warga-dialog-open');
    window.setTimeout(function () { if (enable && !onboarding.hidden) enable.focus(); }, 30);
  }
  function scheduleNotificationOnboarding() {
    // Ask on the first installed-app launch even before login. The browser
    // subscription is safely bound to the account by the existing rebind flow
    // after authentication.
    if (!onboarding || !isInstalledExperience() || !supported || !config.vapidPublicKey || storageGet(onboardingKey)) return;
    navigator.serviceWorker.ready.then(function (reg) { return reg.pushManager.getSubscription(); }).then(function (sub) {
      if (sub) { storageSet(onboardingKey, 'seen'); return; }
      window.setTimeout(showNotificationOnboarding, 650);
    }).catch(function () {});
  }
  if (onboarding) {
    onboarding.addEventListener('click', function (event) {
      var close = event.target.closest('[data-notification-onboarding-close]');
      if (close) { event.preventDefault(); closeNotificationOnboarding(true); return; }
      var enable = event.target.closest('[data-notification-onboarding-enable]');
      if (!enable || enable.disabled) return;
      event.preventDefault();
      if (enable.getAttribute('data-notification-onboarding-blocked') === '1') { closeNotificationOnboarding(true); return; }
      var icon = enable.querySelector('i');
      var label = enable.querySelector('[data-notification-onboarding-enable-label]');
      enable.disabled = true;
      if (icon) icon.className = 'fa fa-spinner fa-spin';
      if (label) label.textContent = 'Mengaktifkan…';
      enableSubscription().then(function (result) {
        if (!result.active) {
          if (result.permission === 'denied') storageSet(onboardingKey, 'seen');
          var messageNode = onboarding.querySelector('[data-notification-onboarding-message]');
          if (messageNode) messageNode.textContent = permissionMessage(result.permission) || 'Izin pemberitahuan belum diberikan. Anda dapat mengaktifkannya dari menu Akun.';
          if (enable) { enable.disabled = false; if (icon) icon.className = 'fa fa-bell'; if (label) label.textContent = 'Coba Lagi'; }
          return;
        }
        updateButton(true);
        storageSet(onboardingKey, 'seen');
        closeNotificationOnboarding(true);
        message('Pemberitahuan perangkat aktif. Suara dan getar mengikuti pengaturan perangkat.');
      }).catch(function (error) {
        if (enable) { enable.disabled = false; if (icon) icon.className = 'fa fa-bell'; if (label) label.textContent = 'Coba Lagi'; }
        var messageNode = onboarding.querySelector('[data-notification-onboarding-message]');
        if (messageNode) messageNode.textContent = error && error.message ? error.message : 'Pemberitahuan belum dapat diaktifkan. Periksa koneksi lalu coba lagi.';
      });
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !onboarding.hidden) { event.preventDefault(); closeNotificationOnboarding(true); }
    });
    if (document.readyState === 'complete') scheduleNotificationOnboarding();
    else window.addEventListener('load', scheduleNotificationOnboarding, {once:true});
  }
  function setUnreadCount(value) {
    var unread = Math.max(0, parseInt(value, 10) || 0);
    document.querySelectorAll('[data-notification-count]').forEach(function (element) {
      element.textContent = unread > 0 ? String(unread) : '';
      element.hidden = unread < 1;
      element.setAttribute('aria-hidden', unread > 0 ? 'false' : 'true');
    });
  }
  var pending=false, stopped=!isAuthenticated, timer;
  async function poll() {
    if (pending || stopped || document.hidden || !navigator.onLine) return;
    pending=true;
    var controller = new AbortController(), timeout = setTimeout(function(){controller.abort();},10000);
    try {
      var response=await fetch(base+'notifikasi/ringkasan',{credentials:'same-origin',cache:'no-store',signal:controller.signal,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
      if (response.redirected || response.status===401) { stopped=true; return; }
      if (!response.ok) return;
      var data=await response.json();
      setUnreadCount(data.unread);
    } catch (_) {} finally { clearTimeout(timeout); pending=false; }
  }
  function schedule() { clearTimeout(timer); poll().finally(function(){timer=setTimeout(schedule,60000);}); }
  if (isAuthenticated) {
    document.addEventListener('visibilitychange',function(){if(!document.hidden)schedule();});
    window.addEventListener('online',schedule);
    schedule();
  }

  // Per-item read state is persisted by /notifikasi/buka/{id}. Update the
  // visible count immediately while navigation follows that server endpoint.
  document.addEventListener('click', function (event) {
    var link = event.target && event.target.closest ? event.target.closest('[data-notification-open]') : null;
    if (!link || !link.classList.contains('is-unread')) return;
    link.classList.remove('is-unread');
    link.classList.add('is-read');
    var state = link.querySelector('.warga-notification-state');
    if (state) {
      state.textContent = 'Sudah dibaca';
      state.classList.remove('is-danger');
      state.classList.add('is-success');
    }
    var count = document.querySelector('[data-notification-count]:not([hidden])');
    if (count) setUnreadCount((parseInt(count.textContent, 10) || 1) - 1);
  });

  function complaintDialog() {
    var dialog = document.querySelector('[data-complaint-feedback-dialog]');
    if (dialog) return dialog;
    dialog = document.createElement('div');
    dialog.className = 'warga-confirm-dialog';
    dialog.hidden = true;
    dialog.setAttribute('data-complaint-feedback-dialog', '');
    dialog.setAttribute('role', 'alertdialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-labelledby', 'complaint-feedback-title');
    dialog.setAttribute('aria-describedby', 'complaint-feedback-message');
    dialog.innerHTML = '<button type="button" class="warga-confirm-backdrop" data-complaint-feedback-close aria-label="Tutup pesan"></button>' +
      '<div class="warga-confirm-panel"><span class="warga-confirm-icon" data-complaint-feedback-icon><i class="fa fa-check" aria-hidden="true"></i></span>' +
      '<h2 id="complaint-feedback-title">Berhasil</h2><p id="complaint-feedback-message"></p>' +
      '<div class="warga-confirm-actions"><button type="button" class="btn bg-teal-dark color-white" data-complaint-feedback-close>OK</button></div></div>';
    document.body.appendChild(dialog);
    dialog.querySelectorAll('[data-complaint-feedback-close]').forEach(function (button) {
      button.addEventListener('click', function () {
        dialog.hidden = true;
        document.body.classList.remove('warga-dialog-open');
        var previous = dialog.__previousFocus;
        if (previous && typeof previous.focus === 'function') previous.focus();
      });
    });
    return dialog;
  }

  function showComplaintFeedback(success, messageText) {
    var dialog = complaintDialog();
    dialog.__previousFocus = document.activeElement;
    var icon = dialog.querySelector('[data-complaint-feedback-icon]');
    var title = dialog.querySelector('#complaint-feedback-title');
    var messageNode = dialog.querySelector('#complaint-feedback-message');
    if (icon) {
      icon.classList.toggle('is-success', !!success);
      icon.innerHTML = '<i class="fa ' + (success ? 'fa-check' : 'fa-exclamation') + '" aria-hidden="true"></i>';
    }
    if (title) title.textContent = success ? 'Berhasil' : 'Belum berhasil';
    if (messageNode) messageNode.textContent = messageText || (success ? 'Data berhasil disimpan.' : 'Data belum dapat disimpan.');
    dialog.hidden = false;
    document.body.classList.add('warga-dialog-open');
    var close = dialog.querySelector('.warga-confirm-actions [data-complaint-feedback-close]');
    if (close) window.setTimeout(function () { close.focus(); }, 20);
  }

  document.addEventListener('keydown', function (event) {
    var dialog = document.querySelector('[data-complaint-feedback-dialog]');
    if (event.key !== 'Escape' || !dialog || dialog.hidden) return;
    var close = dialog.querySelector('[data-complaint-feedback-close]');
    if (close) close.click();
  });

  function setComplaintLoading(form, active) {
    var submit = form.querySelector('button[type="submit"]');
    if (!submit) return;
    var label = submit.querySelector('span');
    var icon = submit.querySelector('i');
    if (active) {
      form.setAttribute('data-ajax-submitting', '1');
      form.setAttribute('aria-busy', 'true');
      submit.disabled = true;
      submit.setAttribute('aria-busy', 'true');
      submit.classList.add('is-loading');
      if (label) { label.setAttribute('data-original-label', label.textContent || 'Kirim'); label.textContent = 'Mengirim…'; }
      if (icon) { icon.setAttribute('data-original-class', icon.className || 'fa fa-paper-plane color-white'); icon.className = 'fa fa-spinner fa-spin color-white'; }
    } else {
      form.removeAttribute('data-ajax-submitting');
      form.removeAttribute('aria-busy');
      submit.disabled = false;
      submit.removeAttribute('aria-busy');
      submit.classList.remove('is-loading');
      if (label) { label.textContent = label.getAttribute('data-original-label') || 'Kirim'; label.removeAttribute('data-original-label'); }
      if (icon) { icon.className = icon.getAttribute('data-original-class') || 'fa fa-paper-plane color-white'; icon.removeAttribute('data-original-class'); }
    }
  }

  function ajaxFormPayload(form) {
    var payload = new FormData(form);
    if (config.csrfName) payload.set(config.csrfName, config.csrfHash || '');
    return payload;
  }

  function bindComplaintForm(form) {
    if (!form || form.getAttribute('data-complaint-ajax-bound') === '1' || typeof window.fetch !== 'function') return;
    form.setAttribute('data-complaint-ajax-bound', '1');
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (form.getAttribute('data-ajax-submitting') === '1') return;
      if (!form.checkValidity()) { form.reportValidity(); return; }
      setComplaintLoading(form, true);
      window.fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        cache: 'no-store',
        body: ajaxFormPayload(form),
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
      }).then(function (response) {
        return response.text().then(function (raw) {
          var data = null;
          try { data = raw ? JSON.parse(raw) : null; } catch (_) {}
          if (data && data.csrf) updateCsrf(data.csrf);
          if (!response.ok || !data || data.success !== true) {
            var error = new Error(data && data.message ? data.message : 'Permintaan belum dapat disimpan.');
            error.payload = data || {};
            throw error;
          }
          return data;
        });
      }).then(function (data) {
        if (form.matches('[data-complaint-form]')) {
          var list = document.querySelector('[data-complaint-list]');
          var empty = list && list.querySelector('[data-complaint-empty]');
          if (empty) empty.remove();
          if (list && data.item_html) list.insertAdjacentHTML('afterbegin', data.item_html);
          var count = document.querySelector('[data-complaint-count]');
          if (count) count.textContent = String((parseInt(count.textContent, 10) || 0) + 1) + ' laporan';
          form.reset();
          // reset() restores the hidden input's original value, so put the
          // rotated CSRF hash returned by the JSON response back afterwards.
          updateCsrf({name: config.csrfName, hash: config.csrfHash});
          var details = form.closest('details');
          if (details) details.open = false;
        } else {
          var replies = document.querySelector('[data-complaint-replies]');
          if (replies && typeof data.replies_html === 'string') replies.innerHTML = data.replies_html;
          var replyCount = document.querySelector('[data-complaint-reply-count]');
          if (replyCount) replyCount.textContent = String(parseInt(data.reply_count, 10) || 0) + ' tanggapan';
          var statusNode = document.querySelector('[data-complaint-status]');
          if (statusNode && data.status_label) statusNode.textContent = data.status_label;
          var messageField = form.querySelector('[name="message"]');
          if (messageField) messageField.value = '';
        }
        showComplaintFeedback(true, data.message);
      }).catch(function (error) {
        showComplaintFeedback(false, error && error.message ? error.message : 'Permintaan belum dapat dikirim. Periksa koneksi lalu coba lagi.');
      }).then(function () { setComplaintLoading(form, false); });
    });
  }

  document.querySelectorAll('[data-complaint-form], [data-complaint-reply-form]').forEach(bindComplaintForm);

  // AppKit v22's double-slider advances every four seconds.  Keep the
  // community slider equally useful when its lightweight scroll-snap markup
  // is used (Splide-marked sliders are initialized by custom.min.js instead).
  function initCommunitySliderAutoplay() {
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    document.querySelectorAll('.community-v22-slider').forEach(function (slider) {
      if (slider.classList.contains('splide') || slider.querySelector('.splide__track')) return;
      var slides = Array.prototype.slice.call(slider.querySelectorAll('.community-v22-slide'));
      if (slides.length < 2 || slider.dataset.autoplayReady === '1') return;
      slider.dataset.autoplayReady = '1';
      var index = 0, paused = false, timeoutId;
      function nearestIndex() {
        var left = slider.scrollLeft, nearest = 0, distance = Infinity;
        slides.forEach(function (slide, slideIndex) {
          var currentDistance = Math.abs(slide.offsetLeft - left);
          if (currentDistance < distance) { distance = currentDistance; nearest = slideIndex; }
        });
        return nearest;
      }
      function stop() { paused = true; clearTimeout(timeoutId); }
      function restart() {
        paused = false;
        clearTimeout(timeoutId);
        timeoutId = setTimeout(advance, 4000);
      }
      function advance() {
        if (paused || document.hidden || !document.body.contains(slider)) return;
        index = (nearestIndex() + 1) % slides.length;
        var target = slides[index];
        // Returning to the first card mirrors Splide's loop while keeping the
        // native scroll-snap implementation accessible and touch friendly.
        slider.scrollTo({left: target.offsetLeft, behavior: 'smooth'});
        timeoutId = setTimeout(advance, 4000);
      }
      slider.addEventListener('mouseenter', stop);
      slider.addEventListener('mouseleave', restart);
      slider.addEventListener('focusin', stop);
      slider.addEventListener('focusout', function (event) {
        if (!slider.contains(event.relatedTarget)) restart();
      });
      slider.addEventListener('touchstart', stop, {passive:true});
      slider.addEventListener('touchend', restart, {passive:true});
      document.addEventListener('visibilitychange', function () { if (document.hidden) stop(); else restart(); });
      restart();
    });
  }
  initCommunitySliderAutoplay();

  // Splide mounts after this file has been evaluated (custom.min.js runs its
  // DOMContentLoaded handler first) and creates cloned slide nodes.  The
  // initial media-skeleton pass in warga.js runs before those clones exist,
  // so a clone can inherit `warga-media-loading` from an image that was still
  // loading at mount time.  Rebind once the DOM is ready to clear completed
  // cached images and attach load/error handlers to every clone as well.
  document.addEventListener('DOMContentLoaded', function () {
    if (window.SDW && typeof window.SDW.bindMediaSkeletons === 'function') {
      window.SDW.bindMediaSkeletons(document);
    }
  });
})();
