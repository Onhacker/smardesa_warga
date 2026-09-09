(function () {
  'use strict';

  function productImageInputs(form) {
    return form ? Array.prototype.slice.call(form.querySelectorAll('[data-market-images]')) : [];
  }

  function productImageFiles(form) {
    var files = [];
    productImageInputs(form).forEach(function (input) {
      files = files.concat(Array.prototype.slice.call(input.files || []));
    });
    return files;
  }

  function updatePreview(form, target) {
    if (!form || !target) return;
    target.innerHTML = '';
    var files = productImageFiles(form);
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

  function formatPriceInput(input) {
    if (!input) return;
    var digits = String(input.value || '').replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 13);
    input.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function bindPriceInput(input) {
    if (!input || input.getAttribute('data-market-price-bound') === '1') return;
    input.setAttribute('data-market-price-bound', '1');
    formatPriceInput(input);
    input.addEventListener('input', function () { formatPriceInput(input); });
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

  function bindMediaSkeletons(root) {
    if (window.SDW && typeof window.SDW.bindMediaSkeletons === 'function') {
      window.SDW.bindMediaSkeletons(root || document);
    }
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
    var productsTitle = root.querySelector('[data-market-products-title]');
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

    function syncCategoryTitle() {
      if (!productsTitle) return;
      var title = 'Semua produk';
      if (categorySelect && categorySelect.value) {
        var option = categorySelect.options[categorySelect.selectedIndex];
        if (option && option.textContent.trim()) title = option.textContent.trim();
      }
      productsTitle.textContent = title;
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
        bindMediaSkeletons(list);
        state.page = Math.max(1, parseInt(data.page, 10) || page);
        state.pages = Math.max(1, parseInt(data.pages, 10) || state.page);
        state.perPage = Math.max(1, parseInt(data.per_page, 10) || state.perPage);
        state.hasMore = data.has_more === true || state.page < state.pages;
        updateCount(data.count);
        syncStatus();
        syncCategoryTitle();
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
        syncCategoryTitle();
        requestCatalog(1, false, true);
      });
    });
    root.querySelectorAll('[data-market-query-clear]').forEach(function (button) {
      button.addEventListener('click', function () {
        state.query = '';
        state.category = '';
        state.sort = 'newest';
        if (categorySelect) categorySelect.value = '';
        if (sortSelect) sortSelect.value = 'newest';
        syncStatus();
        syncCategoryTitle();
        closeSearch();
        requestCatalog(1, false, true);
      });
    });
    root.querySelectorAll('[data-market-search-clear]').forEach(function (button) {
      button.addEventListener('click', function () {
        state.query = '';
        state.category = '';
        state.sort = 'newest';
        if (categorySelect) categorySelect.value = '';
        if (sortSelect) sortSelect.value = 'newest';
        syncStatus();
        syncCategoryTitle();
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
    syncCategoryTitle();
    syncPagination();
    updateCount(parseInt((count && count.textContent) || '0', 10));
    if (sentinel) setHidden(sentinel, !state.hasMore);
  }

  function bindContactModal() {
    var modal = document.querySelector('[data-market-contact-modal]');
    if (!modal || modal.getAttribute('data-market-contact-bound') === '1') return;
    modal.setAttribute('data-market-contact-bound', '1');
    var lastFocus = null;

    function focusableItems() {
      return Array.prototype.slice.call(modal.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'))
        .filter(function (item) { return item.offsetParent !== null; });
    }

    function open(trigger) {
      lastFocus = trigger || document.activeElement;
      modal.hidden = false;
      document.body.classList.add('market-contact-open');
      var items = focusableItems();
      var preferred = modal.querySelector('[data-market-contact-action]') || modal.querySelector('[data-market-contact-close]');
      window.setTimeout(function () {
        if (preferred && items.indexOf(preferred) !== -1) preferred.focus();
      }, 20);
    }

    function close() {
      if (modal.hidden) return;
      modal.hidden = true;
      document.body.classList.remove('market-contact-open');
      if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
      lastFocus = null;
    }

    document.querySelectorAll('[data-market-contact-open]').forEach(function (button) {
      button.addEventListener('click', function () { open(button); });
    });
    modal.querySelectorAll('[data-market-contact-close]').forEach(function (button) {
      button.addEventListener('click', close);
    });
    document.addEventListener('keydown', function (event) {
      if (modal.hidden) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        close();
        return;
      }
      if (event.key !== 'Tab') return;
      var items = focusableItems();
      if (!items.length) return;
      var first = items[0];
      var last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });
  }

  function bindReviewModals() {
    var modal = document.querySelector('[data-market-review-modal]');
    if (!modal || modal.getAttribute('data-market-review-bound') === '1') return;
    modal.setAttribute('data-market-review-bound', '1');
    var form = modal.querySelector('[data-market-review-form]');
    var picker = modal.querySelector('.market-review-picker');
    var title = modal.querySelector('[data-market-review-title]');
    var productLabel = modal.querySelector('[data-market-review-product-label]');
    var ratingLabel = modal.querySelector('[data-market-review-rating-label]');
    var comment = modal.querySelector('[data-market-review-comment]');
    var status = modal.querySelector('[data-market-review-status]');
    var submit = modal.querySelector('.market-review-submit');
    var loginNotice = modal.querySelector('[data-market-review-login-notice]');
    var authenticated = !!form && form.getAttribute('data-review-authenticated') === '1';
    var selectedRating = 0;
    var lastFocus = null;

    function setSubmitLoading(loading) {
      if (!submit) return;
      if (loading) {
        if (submit.getAttribute('data-loading') === '1') return;
        submit.setAttribute('data-loading', '1');
        submit.disabled = true;
        submit.setAttribute('aria-busy', 'true');
        submit.setAttribute('aria-disabled', 'true');
        submit.classList.add('is-loading');
        if (form) form.setAttribute('aria-busy', 'true');
        var label = submit.querySelector('span');
        var icon = submit.querySelector('i');
        if (label) {
          label.setAttribute('data-original-label', label.textContent || '');
          label.textContent = 'Mengirim…';
        }
        if (icon) {
          icon.setAttribute('data-original-class', icon.className || '');
          icon.className = 'fa fa-spinner fa-spin';
        }
        return;
      }
      submit.removeAttribute('data-loading');
      submit.disabled = false;
      submit.removeAttribute('aria-busy');
      submit.removeAttribute('aria-disabled');
      submit.classList.remove('is-loading');
      if (form) form.removeAttribute('aria-busy');
      var label = submit.querySelector('span');
      var icon = submit.querySelector('i');
      if (label) {
        label.textContent = label.getAttribute('data-original-label') || 'Kirim ulasan';
        label.removeAttribute('data-original-label');
      }
      if (icon) {
        icon.className = icon.getAttribute('data-original-class') || 'fa fa-paper-plane color-white';
        icon.removeAttribute('data-original-class');
      }
    }

    function setStatus(message, type, loginUrl) {
      if (!status) return;
      status.textContent = '';
      status.className = 'market-review-status' + (type ? ' is-' + type : '');
      if (message) {
        var messageNode = document.createElement((type === 'login' || loginUrl) ? 'strong' : 'span');
        if (type === 'login' || loginUrl) messageNode.className = 'market-review-status-message';
        messageNode.textContent = message;
        status.appendChild(messageNode);
      }
      if (loginUrl) {
        var link = document.createElement('a');
        link.href = loginUrl;
        link.className = 'bg-red-dark color-white';
        link.textContent = 'Masuk sekarang';
        status.appendChild(link);
      }
    }

    function updatePicker() {
      if (!picker) return;
      picker.querySelectorAll('[data-review-rating]').forEach(function (button) {
        var value = parseInt(button.getAttribute('data-review-rating') || '0', 10);
        button.classList.toggle('is-selected', value <= selectedRating);
        button.setAttribute('aria-checked', value === selectedRating ? 'true' : 'false');
      });
      if (ratingLabel) ratingLabel.textContent = selectedRating ? selectedRating + ' dari 5 bintang' : 'Pilih bintang';
    }

    function updateStars(container, average) {
      if (!container) return;
      var rounded = Math.max(0, Math.min(5, Math.round(parseFloat(average) || 0)));
      container.querySelectorAll('i.fa-star').forEach(function (star, index) {
        star.classList.toggle('is-filled', index < rounded);
        star.classList.toggle('is-empty', index >= rounded);
      });
    }

    function updateProductRating(productId, summary) {
      var average = summary && summary.rating_average !== undefined ? parseFloat(summary.rating_average) || 0 : 0;
      var count = summary && summary.rating_count !== undefined ? parseInt(summary.rating_count, 10) || 0 : 0;
      document.querySelectorAll('[data-market-review-open]').forEach(function (trigger) {
        if ((trigger.getAttribute('data-review-product-id') || '') !== productId) return;
        updateStars(trigger.querySelector('[data-market-rating-stars]'), average);
        var countNode = trigger.querySelector('[data-market-rating-count]');
        if (countNode) countNode.textContent = count ? (average.toFixed(1).replace('.', ',') + ' (' + count + ')') : 'Beri rating';
      });
      var summaryRoot = document.querySelector('[data-market-review-summary]');
      if (!summaryRoot || (summaryRoot.getAttribute('data-review-product-id') || productId) !== productId) return;
      var averageNode = summaryRoot.querySelector('[data-market-rating-average]');
      var countNode = summaryRoot.querySelector('[data-market-rating-count]');
      if (averageNode) averageNode.textContent = average.toFixed(1).replace('.', ',');
      if (countNode) countNode.textContent = count ? count + ' ulasan' : 'Belum ada ulasan';
      updateStars(summaryRoot.querySelector('[data-market-rating-stars]'), average);
    }

    function updateReviewList(data) {
      var list = document.querySelector('[data-market-review-list]');
      if (!list || !data || !data.review_html) return;
      var reviewId = data.review && String(data.review.id || '');
      list.querySelectorAll('[data-review-id]').forEach(function (item) {
        if (reviewId && item.getAttribute('data-review-id') === reviewId) item.remove();
      });
      var empty = list.querySelector('[data-market-review-empty]');
      if (empty) empty.remove();
      list.insertAdjacentHTML('afterbegin', data.review_html);
    }

    function open(trigger) {
      if (!form || !trigger) return;
      lastFocus = document.activeElement;
      selectedRating = 0;
      form.setAttribute('data-review-url', trigger.getAttribute('data-review-url') || '');
      form.setAttribute('data-review-product-id', trigger.getAttribute('data-review-product-id') || '');
      var name = trigger.getAttribute('data-review-product-name') || 'produk ini';
      if (title) title.textContent = 'Beri rating';
      if (productLabel) productLabel.textContent = 'Bagaimana pengalaman Anda dengan ' + name + '?';
      if (comment) comment.value = '';
      setStatus('', '');
      updatePicker();
      modal.hidden = false;
      document.body.classList.add('market-review-open');
      var first = picker && picker.querySelector('[data-review-rating]');
      if (first && authenticated) setTimeout(function () { first.focus(); }, 20);
      if (loginNotice && !authenticated) {
        var loginLink = loginNotice.querySelector('a');
        if (loginLink) setTimeout(function () { loginLink.focus(); }, 20);
      }
    }

    function close() {
      modal.hidden = true;
      document.body.classList.remove('market-review-open');
      if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
      lastFocus = null;
    }

    if (picker) picker.querySelectorAll('[data-review-rating]').forEach(function (button) {
      button.addEventListener('click', function () {
        selectedRating = parseInt(button.getAttribute('data-review-rating') || '0', 10) || 0;
        updatePicker();
      });
    });
    modal.querySelectorAll('[data-market-review-close]').forEach(function (button) { button.addEventListener('click', close); });
    document.addEventListener('click', function (event) {
      var trigger = event.target.closest ? event.target.closest('[data-market-review-open]') : null;
      if (trigger) {
        event.preventDefault();
        event.stopPropagation();
        open(trigger);
        return;
      }
      var closeButton = event.target.closest ? event.target.closest('[data-market-review-close]') : null;
      if (closeButton && modal.contains(closeButton)) close();
    });
    document.addEventListener('keydown', function (event) {
      var trigger = event.target.closest ? event.target.closest('[data-market-review-open]') : null;
      if (trigger && (event.key === 'Enter' || event.key === ' ')) {
        event.preventDefault();
        open(trigger);
      } else if (event.key === 'Escape' && !modal.hidden) {
        event.preventDefault();
        close();
      }
    });
    if (form) form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (submit && submit.getAttribute('data-loading') === '1') return;
      if (!authenticated) {
        var loginLink = loginNotice && loginNotice.querySelector('a');
        setStatus('Login diperlukan. Silakan masuk terlebih dahulu untuk memberi rating.', 'login', loginLink ? loginLink.href : '');
        return;
      }
      if (!selectedRating) { setStatus('Pilih jumlah bintang terlebih dahulu.', 'error'); return; }
      var text = comment ? comment.value.trim() : '';
      if (text.length > 0 && text.length < 3) { setStatus('Komentar minimal 3 karakter atau boleh dikosongkan.', 'error'); if (comment) comment.focus(); return; }
      var endpoint = form.getAttribute('data-review-url') || '';
      if (!endpoint || typeof window.fetch !== 'function') { setStatus('Rating belum dapat dikirim. Coba muat ulang halaman.', 'error'); return; }
      var config = window.SDW || {};
      var body = new URLSearchParams();
      if (config.csrfName) body.set(config.csrfName, config.csrfHash || '');
      body.set('rating', String(selectedRating));
      body.set('comment', text);
      setSubmitLoading(true);
      setStatus('Menyimpan ulasan…', '');
      window.fetch(endpoint, { method: 'POST', credentials: 'same-origin', body: body, cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function (response) {
          return response.text().then(function (raw) {
            var data = null;
            try { data = raw ? JSON.parse(raw) : null; } catch (_) {}
            if (data && data.csrf) { config.csrfName = data.csrf.name; config.csrfHash = data.csrf.hash; }
            if (!response.ok || !data || data.success !== true) {
              var error = new Error(data && data.message ? data.message : 'Rating belum dapat disimpan.');
              error.payload = data || {};
              throw error;
            }
            return data;
          });
        })
        .then(function (data) {
          var productId = form.getAttribute('data-review-product-id') || '';
          updateProductRating(productId, data.summary || {});
          updateReviewList(data);
          setStatus(data.message || 'Rating dan komentar berhasil disimpan.', 'success');
          window.setTimeout(close, 850);
        })
        .catch(function (error) {
          var payload = error && error.payload ? error.payload : {};
          setStatus(error && error.message ? error.message : 'Rating belum dapat disimpan.', payload.login_url ? 'login' : 'error', payload.login_url || '');
        })
        .then(function () { setSubmitLoading(false); });
    });
  }

  function bind() {
    document.querySelectorAll('[data-market-price]').forEach(bindPriceInput);
    document.querySelectorAll('[data-market-product-form]').forEach(function (form) {
      var inputs = productImageInputs(form);
      var primaryInput = form.querySelector('#market-product-images') || inputs[0];
      var preview = form.querySelector('[data-market-image-preview]');
      var imagesRequired = form.getAttribute('data-market-images-required') === '1';
      var existingImageCount = Math.max(0, Number(form.getAttribute('data-market-existing-images')) || 0);

      function validateImages() {
        var count = productImageFiles(form).length;
        var message = '';
        if (existingImageCount + count > 6) message = 'Maksimal enam foto dapat digunakan untuk satu produk.';
        else if (imagesRequired && count < 1) message = 'Ambil atau pilih minimal satu foto produk.';
        if (primaryInput) primaryInput.setCustomValidity(message);
        return message === '';
      }

      inputs.forEach(function (input) {
        input.addEventListener('change', function () {
          validateImages();
          updatePreview(form, preview);
        });
      });
      validateImages();
      form.addEventListener('submit', function (event) {
        if (validateImages()) return;
        event.preventDefault();
        if (primaryInput) primaryInput.reportValidity();
      });
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
        bindMediaSkeletons(gallery);
        gallery.querySelectorAll('[data-market-gallery-thumb]').forEach(function (item) { item.classList.remove('is-active'); });
        thumb.classList.add('is-active');
      });
    });
    document.querySelectorAll('[data-market-catalog]').forEach(bindCatalog);
    bindMediaSkeletons(document);
    bindContactModal();
    bindReviewModals();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind);
  else bind();
}());
