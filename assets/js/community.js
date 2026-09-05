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
      document.querySelectorAll('[data-notification-count]').forEach(function(el){
        el.textContent=data.unread>0 ? String(data.unread) : '';
      });
    } catch (_) {} finally { clearTimeout(timeout); pending=false; }
  }
  function schedule() { clearTimeout(timer); poll().finally(function(){timer=setTimeout(schedule,60000);}); }
  document.addEventListener('visibilitychange',function(){if(!document.hidden)schedule();});
  window.addEventListener('online',schedule);
  schedule();

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
})();
