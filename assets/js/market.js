(function () {
  'use strict';

  function updatePreview(input, target) {
    if (!input || !target) return;
    target.innerHTML = '';
    var files = Array.prototype.slice.call(input.files || []);
    if (!files.length) {
      var empty = document.createElement('span');
      empty.textContent = 'Belum ada foto dipilih.';
      target.appendChild(empty);
      return;
    }
    files.slice(0, 6).forEach(function (file, index) {
      if (!/^image\/(?:jpeg|png|webp)$/i.test(file.type)) return;
      var figure = document.createElement('figure');
      figure.setAttribute('data-index', String(index + 1));
      var image = document.createElement('img');
      image.alt = file.name || ('Foto ' + (index + 1));
      image.loading = 'lazy';
      image.decoding = 'async';
      figure.appendChild(image);
      target.appendChild(figure);
      var url = URL.createObjectURL(file);
      image.onload = function () { URL.revokeObjectURL(url); };
      image.src = url;
    });
  }

  function updateLogo(input, target) {
    if (!input || !target) return;
    var file = input.files && input.files[0];
    if (!file || !/^image\/(?:jpeg|png|webp)$/i.test(file.type)) return;
    var url = URL.createObjectURL(file);
    target.innerHTML = '';
    var image = document.createElement('img');
    image.alt = 'Pratinjau logo toko';
    image.onload = function () { URL.revokeObjectURL(url); };
    image.src = url;
    target.appendChild(image);
  }

  function debounce(fn, delay) {
    var timer = null;
    return function () {
      var args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () { fn.apply(null, args); }, delay);
    };
  }

  function setHidden(element, hidden) {
    if (!element) return;
    element.hidden = !!hidden;
  }

  function skeletonMarkup() {
    var html = '';
    for (var index = 0; index < 4; index += 1) {
      html += '<div class="market-product-skeleton" aria-hidden="true">' +
        '<div class="market-product-skeleton-media"></div>' +
        '<div class="market-product-skeleton-copy">' +
        '<div class="market-product-skeleton-line"></div>' +
        '<div class="market-product-skeleton-line short"></div>' +
        '<div class="market-product-skeleton-line short"></div>' +
        '</div></div>';
    }
    return html;
  }

  function bindCatalog(root) {
    if (!root || root.getAttribute('data-market-bound') === '1') return;
    root.setAttribute('data-market-bound', '1');
    if (typeof window.fetch !== 'function') return;

    var list = root.querySelector('[data-market-product-list]');
    var endpoint = root.getAttribute('data-market-endpoint');
    var loading = root.querySelector('[data-market-loading]');
    var sentinel = root.querySelector('[data-market-sentinel]');
    var count = root.querySelector('[data-market-count]');
    var resultNote = root.querySelector('[data-market-result-note]');
    var pagination = root.querySelector('[data-market-pagination]');
    var categorySelect = root.querySelector('#market-category');
    var sortSelect = root.querySelector('#market-sort');
    var queryField = root.querySelector('[data-market-query-field]');
    var status = root.querySelector('[data-market-filter-status]');
    var queryLabel = root.querySelector('[data-market-query-label]');
    var modal = root.querySelector('[data-market-search-modal]');
    var searchInput = root.querySelector('[data-market-search-input]');
    var searchForm = root.querySelector('[data-market-search-form]');
    var modalCategory = root.querySelector('[data-market-modal-category]');
    var modalSort = root.querySelector('[data-market-modal-sort]');
    var lastFocus = null;
    if (!list || !endpoint) return;

    var state = {
      page: Math.max(1, parseInt(root.getAttribute('data-market-page') || '1', 10) || 1),
      pages: Math.max(1, parseInt(root.getAttribute('data-market-pages') || '1', 10) || 1),
      perPage: Math.max(1, parseInt(root.getAttribute('data-market-per-page') || '12', 10) || 12),
      query: searchInput ? searchInput.value.trim() : (queryField ? queryField.value.trim() : ''),
      category: categorySelect ? categorySelect.value : '',
      sort: sortSelect ? sortSelect.value : 'newest',
      loading: false,
      hasMore: false,
      sequence: 0,
      controller: null,
      scrollQueued: false
    };
    state.hasMore = state.page < state.pages;
    root.classList.add('is-ajax');

    function updateCount(total) {
      if (count) count.textContent = String(Math.max(0, parseInt(total, 10) || 0)) + ' produk';
      if (resultNote) {
        if (!total) resultNote.textContent = state.query ? 'Tidak ada produk yang cocok' : 'Katalog sedang diperbarui';
        else if (state.query) resultNote.textContent = 'Hasil pencarian warga';
        else resultNote.textContent = 'Temukan yang Anda butuhkan';
      }
    }

    function syncStatus() {
      if (queryField) queryField.value = state.query;
      if (searchInput && document.activeElement !== searchInput) searchInput.value = state.query;
      if (queryLabel) queryLabel.textContent = state.query;
      setHidden(status, state.query === '');
      if (modalCategory) modalCategory.value = state.category;
      if (modalSort) modalSort.value = state.sort;
    }

    function syncUrl() {
      if (!window.history || !window.history.replaceState || typeof window.URL !== 'function') return;
      try {
        var url = new URL(window.location.href);
        ['q', 'category_id', 'sort', 'page', 'per_page'].forEach(function (key) { url.searchParams.delete(key); });
        if (state.query) url.searchParams.set('q', state.query);
        if (state.category) url.searchParams.set('category_id', state.category);
        if (state.sort && state.sort !== 'newest') url.searchParams.set('sort', state.sort);
        if (state.page > 1) url.searchParams.set('page', String(state.page));
        window.history.replaceState({}, '', url.pathname + (url.search ? url.search : '') + url.hash);
      } catch (error) { /* Keep the server-rendered URL when History API is unavailable. */ }
    }

    function syncPagination() {
      if (!pagination) return;
      pagination.querySelectorAll('[data-market-page-link]').forEach(function (link) {
        var active = parseInt(link.getAttribute('data-market-page-link') || '0', 10) === state.page;
        link.classList.toggle('is-active', active);
        if (active) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
      });
    }

    function setLoading(active) {
      setHidden(loading, !active);
      root.setAttribute('aria-busy', active ? 'true' : 'false');
      if (sentinel) sentinel.classList.toggle('is-loading', active);
    }

    function requestCatalog(page, append, updateHistory) {
      page = Math.max(1, parseInt(page, 10) || 1);
      if (state.loading && append) return;
      if (state.controller && !append) state.controller.abort();
      state.loading = true;
      state.sequence += 1;
      var sequence = state.sequence;
      state.controller = typeof window.AbortController === 'function' ? new AbortController() : null;
      setLoading(true);
      if (!append) list.innerHTML = skeletonMarkup();

      var params = new URLSearchParams();
      params.set('page', String(page));
      params.set('per_page', String(state.perPage));
      if (state.query) params.set('q', state.query);
      if (state.category) params.set('category_id', state.category);
      if (state.sort) params.set('sort', state.sort);
      var requestUrl = endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') + params.toString();
      var options = { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } };
      if (state.controller) options.signal = state.controller.signal;

      window.fetch(requestUrl, options).then(function (response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
      }).then(function (data) {
        if (sequence !== state.sequence) return;
        if (!data || data.success !== true) throw new Error('Respons katalog tidak valid.');
        var itemsHtml = typeof data.items_html === 'string' ? data.items_html.trim() : '';
        if (append) {
          if (itemsHtml) list.insertAdjacentHTML('beforeend', itemsHtml);
        } else {
          list.innerHTML = itemsHtml || (typeof data.empty_html === 'string' ? data.empty_html : '');
        }
        state.page = Math.max(1, parseInt(data.page, 10) || page);
        state.pages = Math.max(1, parseInt(data.pages, 10) || state.page);
        state.perPage = Math.max(1, parseInt(data.per_page, 10) || state.perPage);
        state.hasMore = data.has_more === true || state.page < state.pages;
        updateCount(data.count);
        syncStatus();
        syncPagination();
        if (sentinel) setHidden(sentinel, !state.hasMore);
        if (updateHistory) syncUrl();
      }).catch(function (error) {
        if (sequence !== state.sequence || (error && error.name === 'AbortError')) return;
        if (!append) {
          list.innerHTML = '<div class="market-empty-state"><span class="market-empty-icon"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span><h3>Produk belum dapat dimuat</h3><p>Periksa koneksi Anda lalu coba lagi.</p><button type="button" class="market-filter-submit" data-market-retry><i class="fa fa-refresh color-white" aria-hidden="true"></i><span class="color-white">Coba lagi</span></button></div>';
          var retry = list.querySelector('[data-market-retry]');
          if (retry) retry.addEventListener('click', function () { requestCatalog(1, false, false); });
        }
        if (resultNote) resultNote.textContent = 'Koneksi belum tersedia';
      }).then(function () {
        if (sequence !== state.sequence) return;
        state.loading = false;
        state.controller = null;
        setLoading(false);
      });
    }

    function openSearch() {
      if (!modal) return;
      lastFocus = document.activeElement;
      syncStatus();
      modal.hidden = false;
      document.body.classList.add('market-search-open');
      if (searchInput) setTimeout(function () { searchInput.focus(); searchInput.select(); }, 20);
    }

    function closeSearch() {
      if (!modal) return;
      modal.hidden = true;
      document.body.classList.remove('market-search-open');
      if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
    }

    root.querySelectorAll('[data-market-search-open]').forEach(function (button) { button.addEventListener('click', openSearch); });
    root.querySelectorAll('[data-market-search-close]').forEach(function (button) { button.addEventListener('click', closeSearch); });
    root.querySelectorAll('[data-market-auto-filter]').forEach(function (select) {
      select.addEventListener('change', function () {
        state.category = categorySelect ? categorySelect.value : '';
        state.sort = sortSelect ? sortSelect.value : 'newest';
        syncStatus();
        requestCatalog(1, false, true);
      });
    });
    root.querySelectorAll('[data-market-query-clear]').forEach(function (button) {
      button.addEventListener('click', function () {
        state.query = '';
        syncStatus();
        closeSearch();
        requestCatalog(1, false, true);
      });
    });
    root.querySelectorAll('[data-market-search-clear]').forEach(function (button) {
      button.addEventListener('click', function () {
        state.query = '';
        syncStatus();
        closeSearch();
        requestCatalog(1, false, true);
      });
    });
    if (searchForm) searchForm.addEventListener('submit', function (event) {
      event.preventDefault();
      state.query = searchInput ? searchInput.value.trim() : '';
      syncStatus();
      closeSearch();
      requestCatalog(1, false, true);
    });
    if (searchInput) {
      var liveSearch = debounce(function () {
        state.query = searchInput.value.trim();
        syncStatus();
        requestCatalog(1, false, true);
      }, 350);
      searchInput.addEventListener('input', liveSearch);
    }
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal && !modal.hidden) closeSearch();
    });
    if (pagination) pagination.querySelectorAll('[data-market-page-link]').forEach(function (link) {
      link.addEventListener('click', function (event) {
        event.preventDefault();
        requestCatalog(parseInt(link.getAttribute('data-market-page-link') || '1', 10), false, true);
      });
    });

    function loadNext() {
      if (state.hasMore && !state.loading) requestCatalog(state.page + 1, true, false);
    }
    if (sentinel && typeof window.IntersectionObserver === 'function') {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) { if (entry.isIntersecting) loadNext(); });
      }, { rootMargin: '0px 0px 450px 0px' });
      observer.observe(sentinel);
    } else {
      window.addEventListener('scroll', function () {
        if (state.scrollQueued) return;
        state.scrollQueued = true;
        window.setTimeout(function () {
          state.scrollQueued = false;
          if (!sentinel || sentinel.getBoundingClientRect().top - window.innerHeight < 450) loadNext();
        }, 100);
      }, { passive: true });
    }
    syncStatus();
    syncPagination();
    updateCount(parseInt((count && count.textContent) || '0', 10));
    if (sentinel) setHidden(sentinel, !state.hasMore);
  }

  function bind() {
    document.querySelectorAll('[data-market-images]').forEach(function (input) {
      var form = input.closest('form');
      var preview = form && form.querySelector('[data-market-image-preview]');
      input.addEventListener('change', function () { updatePreview(input, preview); });
    });
    document.querySelectorAll('[data-market-logo]').forEach(function (input) {
      var form = input.closest('form');
      var preview = form && form.querySelector('[data-market-logo-preview]');
      input.addEventListener('change', function () { updateLogo(input, preview); });
    });
    document.querySelectorAll('[data-market-gallery-thumb]').forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        var gallery = thumb.closest('[data-market-gallery]');
        var main = gallery && gallery.querySelector('[data-market-gallery-main]');
        if (!main) return;
        main.src = thumb.getAttribute('data-image') || main.src;
        gallery.querySelectorAll('[data-market-gallery-thumb]').forEach(function (item) { item.classList.remove('is-active'); });
        thumb.classList.add('is-active');
      });
    });
    document.querySelectorAll('[data-market-catalog]').forEach(bindCatalog);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind);
  else bind();
}());
