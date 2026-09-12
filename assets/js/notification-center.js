(function () {
  'use strict';

  var config = window.SDW || {};
  var base = config.baseUrl || '/';
  var authenticated = config.isAuthenticated === true || config.isAuthenticated === 1 || config.isAuthenticated === '1';

  function setUnreadCount(value) {
    var unread = Math.max(0, parseInt(value, 10) || 0);
    document.querySelectorAll('[data-notification-count]').forEach(function (badge) {
      badge.textContent = unread > 0 ? String(unread) : '';
      badge.hidden = unread < 1;
      badge.setAttribute('aria-hidden', unread > 0 ? 'false' : 'true');
    });
  }

  function visibleUnreadCount() {
    var badge = document.querySelector('[data-notification-count]:not([hidden])');
    return badge ? Math.max(0, parseInt(badge.textContent, 10) || 0) : 0;
  }

  function focusableElements(container) {
    return Array.prototype.slice.call(container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])'))
      .filter(function (element) { return !element.hidden && element.getAttribute('aria-hidden') !== 'true'; });
  }

  function initNotificationCenter() {
    var modal = document.querySelector('[data-notification-center]');
    var triggers = Array.prototype.slice.call(document.querySelectorAll('[data-notification-center-trigger]'));
    if (!authenticated || !modal || !triggers.length || !window.fetch) return;

    if (modal.parentNode !== document.body) document.body.appendChild(modal);
    var results = modal.querySelector('[data-notification-center-results]');
    var description = modal.querySelector('[data-notification-center-description]');
    var errorBox = modal.querySelector('[data-notification-center-error]');
    var retry = modal.querySelector('[data-notification-center-retry]');
    var controller = null;
    var requestSequence = 0;
    var activePage = 1;
    var previousFocus = null;

    function loadingMarkup() {
      return '<div class="warga-notification-center-loading" aria-hidden="true"><i></i><i></i><i></i></div>';
    }

    function close() {
      if (modal.hidden) return;
      if (controller) controller.abort();
      controller = null;
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('warga-notification-center-open');
      if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
      previousFocus = null;
    }

    function load(page) {
      activePage = Math.max(1, parseInt(page, 10) || 1);
      var sequence = ++requestSequence;
      if (controller) controller.abort();
      controller = window.AbortController ? new AbortController() : null;
      results.setAttribute('aria-busy', 'true');
      results.innerHTML = loadingMarkup();
      errorBox.hidden = true;
      if (description) description.textContent = 'Memuat pemberitahuan terbaru…';
      var options = {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
      };
      if (controller) options.signal = controller.signal;

      fetch(base + 'notifikasi/belum-dibaca?page=' + encodeURIComponent(activePage), options).then(function (response) {
        if (response.redirected || response.status === 401 || response.status === 403) {
          var authError = new Error('Sesi login telah berakhir.');
          authError.sessionExpired = true;
          throw authError;
        }
        if (!response.ok || (response.headers.get('Content-Type') || '').indexOf('application/json') === -1) {
          throw new Error('Pemberitahuan belum dapat dimuat.');
        }
        return response.json();
      }).then(function (data) {
        if (sequence !== requestSequence) return;
        if (!data || typeof data.html !== 'string') throw new Error('Pemberitahuan belum dapat dimuat.');
        results.innerHTML = data.html;
        activePage = Math.max(1, parseInt(data.page, 10) || 1);
        var unread = Math.max(0, parseInt(data.unread, 10) || 0);
        setUnreadCount(unread);
        if (description) description.textContent = unread > 0
          ? unread + ' pemberitahuan belum dibaca'
          : 'Tidak ada pemberitahuan yang belum dibaca.';
      }).catch(function (error) {
        if (sequence !== requestSequence || error.name === 'AbortError') return;
        results.innerHTML = '';
        errorBox.firstChild.nodeValue = error.sessionExpired
          ? 'Sesi login telah berakhir. '
          : 'Pemberitahuan belum dapat dimuat. ';
        errorBox.hidden = false;
        if (description) description.textContent = 'Periksa koneksi lalu coba lagi.';
      }).finally(function () {
        if (sequence !== requestSequence) return;
        results.setAttribute('aria-busy', 'false');
        controller = null;
      });
    }

    function open(trigger) {
      previousFocus = trigger || document.activeElement;
      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('warga-notification-center-open');
      load(1);
      var closeButton = modal.querySelector('.warga-notification-center-close');
      if (closeButton) window.setTimeout(function () { closeButton.focus(); }, 20);
    }

    triggers.forEach(function (trigger) {
      trigger.addEventListener('click', function (event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button > 0) return;
        // With no red badge the ordinary link remains the fastest path to the
        // complete notification history, exactly like a normal navigation.
        if (visibleUnreadCount() < 1) return;
        event.preventDefault();
        open(trigger);
      });
    });

    modal.addEventListener('click', function (event) {
      if (event.target.closest('[data-notification-center-close]')) {
        event.preventDefault();
        close();
        return;
      }
      var pageButton = event.target.closest('[data-notification-center-page]');
      if (pageButton && !pageButton.disabled) {
        event.preventDefault();
        load(pageButton.getAttribute('data-notification-center-page'));
      }
    });
    if (retry) retry.addEventListener('click', function () { load(activePage); });

    document.addEventListener('keydown', function (event) {
      if (modal.hidden) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        close();
        return;
      }
      if (event.key !== 'Tab') return;
      var focusable = focusableElements(modal);
      if (!focusable.length) return;
      var first = focusable[0], last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault(); last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault(); first.focus();
      }
    });
  }

  function initNotificationSearch() {
    var modal = document.querySelector('[data-notification-search-modal]');
    var triggers = Array.prototype.slice.call(document.querySelectorAll('[data-notification-search-open]'));
    if (!modal || !triggers.length) return;
    var previousFocus = null;
    var form = modal.querySelector('[data-list-search]');

    function close() {
      if (modal.hidden) return;
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('warga-notification-search-open');
      if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
      previousFocus = null;
    }

    function open(trigger) {
      previousFocus = trigger || document.activeElement;
      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('warga-notification-search-open');
      var search = modal.querySelector('input[type="search"]');
      if (search) window.setTimeout(function () { search.focus(); }, 20);
    }

    triggers.forEach(function (trigger) {
      trigger.addEventListener('click', function (event) { event.preventDefault(); open(trigger); });
    });
    modal.addEventListener('click', function (event) {
      if (event.target.closest('[data-notification-search-close]')) {
        event.preventDefault(); close();
      }
      if (event.target.closest('[data-list-reset]')) close();
    });
    if (form) form.addEventListener('submit', function () { close(); });
    document.addEventListener('keydown', function (event) {
      if (modal.hidden) return;
      if (event.key === 'Escape') { event.preventDefault(); close(); return; }
      if (event.key !== 'Tab') return;
      var focusable = focusableElements(modal);
      if (!focusable.length) return;
      var first = focusable[0], last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
  }

  initNotificationCenter();
  initNotificationSearch();
})();
