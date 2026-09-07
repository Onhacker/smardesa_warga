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
  var button = document.querySelector('[data-push-toggle]');
  var checkbox = button && button.matches('input[type="checkbox"]');
  var status = document.querySelector('[data-push-status]');
  var subscribed = false;
  function message(text) { if (status) status.textContent = text; }
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
      .then(function (r) { if (!r.ok) throw new Error('Permintaan belum dapat disimpan. Muat ulang halaman lalu coba lagi.'); return r.json(); })
      .then(function (data) { updateCsrf(data.csrf); return data; });
  }
  function keyBytes(value) {
    var decoded = atob(value.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4-value.length%4)%4));
    return Uint8Array.from(decoded, function (char) { return char.charCodeAt(0); });
  }
  function updateButton(active) {
    subscribed = !!active;
    if (!button) return;
    if (checkbox) {
      button.checked = subscribed;
      button.setAttribute('aria-checked', subscribed ? 'true' : 'false');
    } else {
      button.innerHTML = '<i class="fa fa-bell"></i> ' + (subscribed ? 'Nonaktifkan Notifikasi' : 'Aktifkan Notifikasi');
    }
  }
  function setDisabled(disabled) { if (button) button.disabled = !!disabled; }
  var supported = window.isSecureContext && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
  if (button) {
    if (!supported) { updateButton(false); setDisabled(true); message('Notifikasi perangkat tidak didukung oleh browser ini.'); }
    else if (!config.vapidPublicKey) { updateButton(false); setDisabled(true); message('Notifikasi perangkat belum diaktifkan oleh pengelola server.'); }
    else {
      navigator.serviceWorker.ready.then(function (reg) { return reg.pushManager.getSubscription(); })
        .then(function (sub) { updateButton(!!sub); if (sub) return post('notifikasi/push', {subscription:JSON.stringify(sub)}); })
        .catch(function () { message('Status notifikasi belum dapat diperiksa.'); });
      async function toggleSubscription(desired) {
        var previous = subscribed;
        setDisabled(true);
        try {
          var permission = desired ? (subscribed ? 'granted' : await Notification.requestPermission()) : 'granted';
          if (permission !== 'granted') {
            updateButton(previous);
            message('Izin notifikasi belum diberikan. Periksa pengaturan situs pada browser.');
            return;
          }
          var reg = await navigator.serviceWorker.ready;
          var sub = await reg.pushManager.getSubscription();
          if (!desired && subscribed && sub) {
            await post('notifikasi/push/hapus', {endpoint:sub.endpoint});
            await sub.unsubscribe(); updateButton(false); message('Notifikasi perangkat dinonaktifkan.');
          } else if (desired) {
            sub = sub || await reg.pushManager.subscribe({userVisibleOnly:true, applicationServerKey:keyBytes(config.vapidPublicKey)});
            await post('notifikasi/push', {subscription:JSON.stringify(sub)});
            updateButton(true); message('Notifikasi perangkat aktif.');
          } else updateButton(false);
        } catch (error) {
          updateButton(previous);
          message(error.message || 'Notifikasi belum dapat diaktifkan.');
        } finally { setDisabled(false); }
      }
      if (checkbox) {
        button.addEventListener('change', function () { toggleSubscription(button.checked); });
      } else {
        button.addEventListener('click', function () { toggleSubscription(!subscribed); });
      }
    }
  }
  // Rebind an existing subscription after switching accounts; no permission prompt.
  if (isAuthenticated && supported && config.vapidPublicKey && !button) navigator.serviceWorker.ready
    .then(function (reg) { return reg.pushManager.getSubscription(); })
    .then(function (sub) { if (sub) return post('notifikasi/push', {subscription:JSON.stringify(sub)}); }).catch(function () {});
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
    if (state) state.textContent = 'Sudah dibaca';
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
      if (label) { label.setAttribute('data-original-label', label.textContent || 'Kirim'); label.textContent = 'Mengirim…'; }
      if (icon) { icon.setAttribute('data-original-class', icon.className || 'fa fa-paper-plane'); icon.className = 'fa fa-spinner fa-spin'; }
    } else {
      form.removeAttribute('data-ajax-submitting');
      form.removeAttribute('aria-busy');
      submit.disabled = false;
      submit.removeAttribute('aria-busy');
      if (label) { label.textContent = label.getAttribute('data-original-label') || 'Kirim'; label.removeAttribute('data-original-label'); }
      if (icon) { icon.className = icon.getAttribute('data-original-class') || 'fa fa-paper-plane'; icon.removeAttribute('data-original-class'); }
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
