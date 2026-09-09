(function () {
  'use strict';
  // Global notification/push runtime. Community pages use the equivalent
  // code in community.js; every other route loads this smaller bundle.
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
        ? 'Izin notifikasi belum tersinkron. Pastikan Info aplikasi > Notifikasi aktif, kembali ke aplikasi, lalu aktifkan sakelar. Jika masih gagal, buka ulang aplikasi.'
        : 'Izin diblokir oleh browser. Buka Pengaturan situs > Notifikasi, pilih Izinkan, lalu coba lagi.';
    }
    if (permission === 'default') return 'Klik sakelar untuk memberi izin notifikasi pada browser.';
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
      } else {
        control.innerHTML = '<i class="fa fa-bell"></i> ' + (subscribed ? 'Nonaktifkan Notifikasi' : 'Aktifkan Notifikasi');
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
      if (isAuthenticated) await post('notifikasi/push/hapus', {endpoint:sub.endpoint});
      await sub.unsubscribe();
    }
    return {active:false};
  }
  async function toggleSubscription(desired) {
    var previous = subscribed;
    setDisabled(true);
    try {
      if (!desired) {
        await disableSubscription();
        updateButton(false); message('Notifikasi perangkat dinonaktifkan.');
        return;
      }
      var result = await enableSubscription();
      if (!result.active) {
        updateButton(previous);
        message(permissionMessage(result.permission) || 'Izin notifikasi belum diberikan. Periksa pengaturan situs pada browser.');
        return;
      }
      updateButton(true); message('Notifikasi perangkat aktif. Suara dan getar mengikuti pengaturan perangkat.');
    } catch (error) {
      updateButton(previous);
      if (Notification.permission === 'denied') message(permissionMessage('denied'));
      else message(error.message || 'Notifikasi belum dapat diaktifkan.');
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
        message('Izin notifikasi sudah diberikan. Aktifkan sakelar untuk menerima pembaruan.');
      } else {
        message(permissionMessage(permission));
      }
    } catch (_) {
      message('Status notifikasi belum dapat diperiksa.');
    } finally { pushRefreshing = false; }
  }
  function schedulePushRefresh() {
    if (!buttons.length || document.hidden) return;
    clearTimeout(pushRefreshTimer);
    pushRefreshTimer = window.setTimeout(function () { refreshPushState(false); }, 120);
  }
  if (buttons.length) {
    if (!supported) { updateButton(false); setDisabled(true); message('Notifikasi perangkat tidak didukung oleh browser ini.'); }
    else if (!config.vapidPublicKey) { updateButton(false); setDisabled(true); message('Notifikasi perangkat belum diaktifkan oleh pengelola server.'); }
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
      ? 'Izin notifikasi saat ini diblokir. Aktifkan melalui Info aplikasi > Notifikasi agar pembaruan layanan dapat diterima.'
      : 'Izinkan notifikasi agar Anda segera mengetahui status surat dan pembaruan layanan Anda.';
    if (note) note.innerHTML = blocked
      ? '<i class="fa fa-info-circle" aria-hidden="true"></i>Buka pengaturan aplikasi, pilih Notifikasi, lalu aktifkan Izinkan notifikasi.'
      : '<i class="fa fa-shield-alt" aria-hidden="true"></i>Notifikasi hanya digunakan untuk pembaruan layanan akun Anda.';
    if (label) label.textContent = blocked ? 'Mengerti' : 'Izinkan Notifikasi';
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
          if (messageNode) messageNode.textContent = permissionMessage(result.permission) || 'Izin notifikasi belum diberikan. Anda dapat mengaktifkannya dari menu Akun.';
          if (enable) { enable.disabled = false; if (icon) icon.className = 'fa fa-bell'; if (label) label.textContent = 'Coba Lagi'; }
          return;
        }
        updateButton(true);
        storageSet(onboardingKey, 'seen');
        closeNotificationOnboarding(true);
        message('Notifikasi perangkat aktif. Suara dan getar mengikuti pengaturan perangkat.');
      }).catch(function (error) {
        if (enable) { enable.disabled = false; if (icon) icon.className = 'fa fa-bell'; if (label) label.textContent = 'Coba Lagi'; }
        var messageNode = onboarding.querySelector('[data-notification-onboarding-message]');
        if (messageNode) messageNode.textContent = error && error.message ? error.message : 'Notifikasi belum dapat diaktifkan. Periksa koneksi lalu coba lagi.';
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

})();
