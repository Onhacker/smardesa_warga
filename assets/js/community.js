(function () {
  'use strict';
  var config = window.SDW || {}, base = config.baseUrl || '/';
  var button = document.querySelector('[data-push-toggle]');
  var status = document.querySelector('[data-push-status]');
  var subscribed = false;
  function message(text) { if (status) status.textContent = text; }
  function post(path, values) {
    var data = new URLSearchParams(values);
    data.set(config.csrfName, config.csrfHash);
    return fetch(base + path, {method:'POST', credentials:'same-origin', body:data, cache:'no-store'})
      .then(function (r) { if (!r.ok) throw new Error('Permintaan belum dapat disimpan. Muat ulang halaman lalu coba lagi.'); return r.json(); })
      .then(function (data) { if (data.csrf) { config.csrfName=data.csrf.name; config.csrfHash=data.csrf.hash; } return data; });
  }
  function keyBytes(value) {
    var decoded = atob(value.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4-value.length%4)%4));
    return Uint8Array.from(decoded, function (char) { return char.charCodeAt(0); });
  }
  function updateButton(active) {
    subscribed = active;
    if (button) button.innerHTML = '<i class="fa fa-bell"></i> ' + (active ? 'Nonaktifkan Notifikasi' : 'Aktifkan Notifikasi');
  }
  var supported = window.isSecureContext && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
  if (button) {
    if (!supported) { button.disabled=true; message('Notifikasi perangkat tidak didukung oleh browser ini.'); }
    else if (!config.vapidPublicKey) { button.disabled=true; message('Notifikasi perangkat belum diaktifkan oleh pengelola server.'); }
    else {
      navigator.serviceWorker.ready.then(function (reg) { return reg.pushManager.getSubscription(); })
        .then(function (sub) { updateButton(!!sub); if (sub) return post('notifikasi/push', {subscription:JSON.stringify(sub)}); })
        .catch(function () { message('Status notifikasi belum dapat diperiksa.'); });
      button.addEventListener('click', async function () {
        button.disabled = true;
        try {
          var permission = subscribed ? 'granted' : await Notification.requestPermission();
          if (permission !== 'granted') { message('Izin notifikasi belum diberikan. Periksa pengaturan situs pada browser.'); return; }
          var reg = await navigator.serviceWorker.ready;
          var sub = await reg.pushManager.getSubscription();
          if (subscribed && sub) {
            await post('notifikasi/push/hapus', {endpoint:sub.endpoint});
            await sub.unsubscribe(); updateButton(false); message('Notifikasi perangkat dinonaktifkan.');
          } else {
            sub = sub || await reg.pushManager.subscribe({userVisibleOnly:true, applicationServerKey:keyBytes(config.vapidPublicKey)});
            await post('notifikasi/push', {subscription:JSON.stringify(sub)});
            updateButton(true); message('Notifikasi perangkat aktif.');
          }
        } catch (error) { message(error.message || 'Notifikasi belum dapat diaktifkan.'); }
        finally { button.disabled=false; }
      });
    }
  }
  // Rebind an existing subscription after switching accounts; no permission prompt.
  if (supported && config.vapidPublicKey && !button) navigator.serviceWorker.ready
    .then(function (reg) { return reg.pushManager.getSubscription(); })
    .then(function (sub) { if (sub) return post('notifikasi/push', {subscription:JSON.stringify(sub)}); }).catch(function () {});
  var pending=false, stopped=false, timer;
  async function poll() {
    if (pending || stopped || document.hidden || !navigator.onLine) return;
    pending=true;
    var controller = new AbortController(), timeout = setTimeout(function(){controller.abort();},10000);
    try {
      var response=await fetch(base+'notifikasi/ringkasan',{credentials:'same-origin',cache:'no-store',signal:controller.signal});
      if (response.redirected || response.status===401) { stopped=true; return; }
      if (!response.ok) return;
      var data=await response.json();
      document.querySelectorAll('[data-notification-count]').forEach(function(el){el.textContent=data.unread>0 ? String(data.unread)+' baru' : '';});
    } catch (_) {} finally { clearTimeout(timeout); pending=false; }
  }
  function schedule() { clearTimeout(timer); poll().finally(function(){timer=setTimeout(schedule,60000);}); }
  document.addEventListener('visibilitychange',function(){if(!document.hidden)schedule();});
  window.addEventListener('online',schedule);
  schedule();
})();
