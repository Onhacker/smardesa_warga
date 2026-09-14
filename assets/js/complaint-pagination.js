(function () {
  'use strict';

  function init() {
    var list = document.querySelector('[data-complaint-list]');
    var wrap = document.querySelector('[data-complaint-pagination-wrap]');
    if (!list || !wrap || wrap.getAttribute('data-complaint-pagination-bound') === '1' || typeof window.fetch !== 'function') return;
    var dataUrl = list.getAttribute('data-complaint-data-url') || '';
    if (!dataUrl) return;
    wrap.setAttribute('data-complaint-pagination-bound', '1');

    function bind() {
      var nav = wrap.querySelector('[data-complaint-pagination]');
      if (!nav || nav.getAttribute('data-complaint-pagination-click-bound') === '1') return;
      nav.setAttribute('data-complaint-pagination-click-bound', '1');
      nav.addEventListener('click', function (event) {
        var target = event.target;
        while (target && target !== nav && (!target.getAttribute || !target.getAttribute('data-complaint-page'))) target = target.parentNode;
        if (!target || target === nav || target.disabled) return;
        event.preventDefault();
        load(target.getAttribute('data-complaint-page'), target, {history: 'push', focus: true});
      });
    }

    function updateUrl(page, mode) {
      if (!mode || !window.history || !window.history[mode + 'State']) return;
      try {
        var url = new URL(window.location.href);
        if (page > 1) url.searchParams.set('page', String(page));
        else url.searchParams.delete('page');
        window.history[mode + 'State']({complaintPage: page}, '', url.href);
      } catch (_) {}
    }

    function load(page, button, options) {
      page = Math.max(1, parseInt(page, 10) || 1);
      options = options || {};
      if (wrap.getAttribute('data-complaint-loading') === '1') return;
      wrap.setAttribute('data-complaint-loading', '1');
      wrap.setAttribute('aria-busy', 'true');
      if (button) button.disabled = true;
      var separator = dataUrl.indexOf('?') === -1 ? '?' : '&';
      window.fetch(dataUrl + separator + 'page=' + encodeURIComponent(page), {
        method: 'GET', credentials: 'same-origin', cache: 'no-store',
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
      }).then(function (response) {
        return response.text().then(function (raw) {
          var data = null;
          try { data = raw ? JSON.parse(raw) : null; } catch (_) {}
          if (!response.ok || !data || data.success !== true) {
            var error = new Error(data && data.message ? data.message : 'Daftar pengaduan belum dapat dimuat.');
            error.loginUrl = data && data.login_url ? data.login_url : '';
            throw error;
          }
          return data;
        });
      }).then(function (data) {
        if (typeof data.items_html === 'string') list.innerHTML = data.items_html;
        if (typeof data.pagination_html === 'string') wrap.innerHTML = data.pagination_html;
        var count = document.querySelector('[data-complaint-count]');
        if (count) count.textContent = String(parseInt(data.total, 10) || 0) + ' laporan';
        var status = document.querySelector('[data-complaint-pagination-status]');
        if (status) {
          status.textContent = 'Halaman ' + String(parseInt(data.page, 10) || 1) + ' berhasil dimuat.';
          status.classList.remove('is-error');
          status.classList.add('is-success');
          status.hidden = false;
        }
        updateUrl(parseInt(data.page, 10) || page, options.history || '');
        bind();
        if (options.focus) {
          list.setAttribute('tabindex', '-1');
          try { list.focus({preventScroll: true}); } catch (_) { list.focus(); }
          list.scrollIntoView({behavior: 'smooth', block: 'start'});
        }
      }).catch(function (error) {
        if (error && error.loginUrl) {
          window.location.assign(error.loginUrl);
          return;
        }
        var status = document.querySelector('[data-complaint-pagination-status]');
        if (status) {
          status.textContent = error && error.message ? error.message : 'Daftar pengaduan belum dapat dimuat.';
          status.classList.remove('is-success');
          status.classList.add('is-error');
          status.hidden = false;
        }
      }).then(function () {
        wrap.removeAttribute('data-complaint-loading');
        wrap.removeAttribute('aria-busy');
        bind();
      });
    }

    bind();
    document.addEventListener('sdw:complaints-updated', function () {
      var status = document.querySelector('[data-complaint-pagination-status]');
      if (status) {
        status.textContent = '';
        status.classList.remove('is-error', 'is-success');
        status.hidden = true;
      }
      bind();
    });
    window.addEventListener('popstate', function () {
      var page = 1;
      try { page = Math.max(1, parseInt(new URL(window.location.href).searchParams.get('page'), 10) || 1); } catch (_) {}
      load(page, null, {focus: false});
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
}());
