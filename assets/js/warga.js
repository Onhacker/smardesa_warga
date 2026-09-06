(function () {
  'use strict';

  var body = document.body;
  var config = window.SDW || {};

  /*
   * AppKit's theme handler is normally initialised by its dynamic menu
   * loader. SmartDesa renders the menu server-side, so that loader does not
   * run and the theme controls would otherwise be inert. Keep the handler
   * here with the application script that is loaded on every page.
   */
  function applyTheme(theme) {
    var dark = theme === 'dark';
    body.classList.toggle('theme-dark', dark);
    body.classList.toggle('theme-light', !dark);
    document.querySelectorAll('input[data-toggle-theme]').forEach(function (input) {
      input.checked = dark;
    });
  }

  var savedTheme = '';
  try { savedTheme = localStorage.getItem('SIMP-Theme') || ''; } catch (error) { savedTheme = ''; }
  if (savedTheme === 'dark-mode') applyTheme('dark');
  else if (savedTheme === 'light-mode') applyTheme('light');

  document.addEventListener('click', function (event) {
    var target = event.target;
    var themeControl = target && typeof target.closest === 'function'
      ? target.closest('[data-toggle-theme]')
      : null;
    if (!themeControl) return;
    event.preventDefault();
    var nextTheme = body.classList.contains('theme-dark') ? 'light' : 'dark';
    applyTheme(nextTheme);
    try { localStorage.setItem('SIMP-Theme', nextTheme + '-mode'); } catch (error) {}
  });

  function updateConnectivity() {
    var online = navigator.onLine;
    document.querySelectorAll('[data-connectivity]').forEach(function (element) {
      element.classList.toggle('is-offline', !online);
      var label = element.querySelector('[data-connectivity-label]');
      if (label) label.textContent = online ? 'Online' : 'Offline';
    });
  }
  window.addEventListener('online', updateConnectivity);
  window.addEventListener('offline', updateConnectivity);
  updateConnectivity();

  function bindMediaSkeletons(root) {
    var scope = root && typeof root.querySelectorAll === 'function' ? root : document;
    var images = scope.querySelectorAll([
      '[data-dashboard-media-card] > img',
      '.market-product-media > img',
      '.market-product-hero-image > img',
      '.market-product-thumb > img'
    ].join(','));

    images.forEach(function (image) {
      var frame = image.parentElement;
      if (!frame) return;

      function finish() {
        frame.classList.remove('warga-media-loading');
        frame.removeAttribute('aria-busy');
      }

      if (image.getAttribute('data-warga-media-bound') !== '1') {
        image.setAttribute('data-warga-media-bound', '1');
        image.addEventListener('load', finish);
        image.addEventListener('error', finish);
      }

      if (image.complete) {
        finish();
      } else {
        frame.classList.add('warga-media-loading');
        frame.setAttribute('aria-busy', 'true');
      }
    });

    var dashboard = document.querySelector('[data-dashboard-home]');
    if (dashboard) dashboard.setAttribute('aria-busy', 'false');
  }

  config.bindMediaSkeletons = bindMediaSkeletons;
  bindMediaSkeletons(document);

  function directChild(element, tagName) {
    if (!element) return null;
    var children = element.children || [];
    for (var index = 0; index < children.length; index += 1) {
      if (children[index].tagName && children[index].tagName.toLowerCase() === tagName) return children[index];
    }
    return null;
  }

  function pageSkeleton() {
    var skeleton = document.querySelector('[data-warga-page-skeleton]');
    if (skeleton) return skeleton;
    skeleton = document.createElement('div');
    skeleton.className = 'warga-page-skeleton';
    skeleton.hidden = true;
    skeleton.setAttribute('data-warga-page-skeleton', '');
    skeleton.setAttribute('role', 'status');
    skeleton.setAttribute('aria-live', 'polite');
    skeleton.setAttribute('aria-label', 'Memuat halaman');
    skeleton.innerHTML = '<span class="visually-hidden">Memuat halaman…</span>' +
      '<div class="warga-page-skeleton-inner" aria-hidden="true">' +
        '<span class="warga-page-skeleton-hero"></span>' +
        '<span class="warga-page-skeleton-heading"></span>' +
        '<span class="warga-page-skeleton-grid"><i></i><i></i><i></i><i></i></span>' +
        '<span class="warga-page-skeleton-row"><i></i><b></b></span>' +
        '<span class="warga-page-skeleton-row"><i></i><b></b></span>' +
        '<span class="warga-page-skeleton-row"><i></i><b></b></span>' +
      '</div>';
    document.body.appendChild(skeleton);
    return skeleton;
  }

  function showNavigationLoader(link) {
    body.classList.add('warga-is-navigating');
    var skeleton = pageSkeleton();
    skeleton.hidden = false;

    if (link && link.closest && link.closest('#footer-bar')) {
      var footerIcon = directChild(link, 'i') || link.querySelector('i');
      if (footerIcon) {
        footerIcon.setAttribute('data-warga-footer-loading', '1');
      }
      link.classList.add('is-navigating');
      link.setAttribute('aria-busy', 'true');
    }

    if (link && link.closest && link.closest('#menu-main .warga-menu-list')) {
      var menuLabel = directChild(link, 'span');
      if (menuLabel) {
        menuLabel.setAttribute('data-warga-original-label', menuLabel.textContent || '');
        menuLabel.textContent = 'Memuat…';
      }
      link.classList.add('is-navigating');
      link.setAttribute('aria-busy', 'true');
    }
  }

  function resetNavigationLoader() {
    body.classList.remove('warga-is-navigating');
    var skeleton = document.querySelector('[data-warga-page-skeleton]');
    if (skeleton) skeleton.hidden = true;
    document.querySelectorAll('#footer-bar a.is-navigating, #menu-main .warga-menu-list > a.is-navigating').forEach(function (link) {
      var icon = directChild(link, 'i');
      if (icon && icon.hasAttribute('data-warga-original-class')) {
        icon.className = icon.getAttribute('data-warga-original-class') || '';
        icon.removeAttribute('data-warga-original-class');
      }
      if (icon && icon.hasAttribute('data-warga-footer-loading')) {
        icon.removeAttribute('data-warga-footer-loading');
      }
      var label = directChild(link, 'span');
      if (label && label.hasAttribute('data-warga-original-label')) {
        label.textContent = label.getAttribute('data-warga-original-label') || '';
        label.removeAttribute('data-warga-original-label');
      }
      link.classList.remove('is-navigating');
      link.removeAttribute('aria-busy');
    });
  }

  document.addEventListener('click', function (event) {
    if (event.defaultPrevented || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    var target = event.target;
    if (!target || typeof target.closest !== 'function') return;
    if (target.closest('[data-toggle-theme], [data-menu], [data-market-review-open], [data-market-page-link], [data-list-page], [data-list-filter], [data-list-reset], [data-list-retry]')) return;
    var link = target.closest('a[href]');
    if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
    var href = (link.getAttribute('href') || '').trim();
    if (!href || href === '#' || href.indexOf('javascript:') === 0) return;
    var url;
    try { url = new URL(link.href, window.location.href); } catch (error) { return; }
    if (!/^https?:$/.test(url.protocol) || url.origin !== window.location.origin) return;
    if (url.href === window.location.href) return;
    if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;
    showNavigationLoader(link);
  });

  window.addEventListener('pageshow', resetNavigationLoader);

  document.querySelectorAll('form[data-disable-submit]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) return;
      if (form.getAttribute('data-submitting') === '1') {
        event.preventDefault();
        return;
      }
      form.setAttribute('data-submitting', '1');
      form.setAttribute('aria-busy', 'true');
      form.classList.add('is-submitting');
      var button = event.submitter || form.querySelector('button[type="submit"]');
      if (!button) return;
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      button.setAttribute('aria-disabled', 'true');
      var label = button.querySelector('span');
      if (label) label.textContent = 'Memproses...';
      var icon = button.querySelector('i');
      if (icon) icon.className = 'fa fa-spinner fa-spin ms-2';
    });
  });

  var serviceSearch = document.querySelector('[data-service-search]');
  if (serviceSearch) {
    var serviceItems = document.querySelectorAll('[data-service-name]');
    var serviceEmpty = document.querySelector('[data-service-empty]');
    var serviceCount = document.querySelector('[data-service-count]');
    function filterServices() {
      var words = serviceSearch.value.toLocaleLowerCase('id').trim().split(/\s+/).filter(Boolean);
      var visible = 0;
      serviceItems.forEach(function (item) {
        var name = item.getAttribute('data-service-name').toLocaleLowerCase('id').replace(/-/g, ' ');
        var matches = words.every(function (word) { return name.indexOf(word) !== -1; });
        item.hidden = !matches;
        if (matches) visible++;
      });
      if (serviceEmpty) serviceEmpty.hidden = visible !== 0;
      if (serviceCount) serviceCount.textContent = visible + ' surat';
    }
    serviceSearch.addEventListener('input', filterServices);
    serviceSearch.addEventListener('search', filterServices);
    filterServices();
  }

  var requestForm = document.querySelector('[data-request-form]');
  if (requestForm) {
    var services = [];
    try { services = JSON.parse(requestForm.getAttribute('data-services') || '[]'); } catch (ignore) { services = []; }
    var select = requestForm.querySelector('[data-service-select]');
    var requirementBox = requestForm.querySelector('[data-service-requirements]');
    var requirementList = requestForm.querySelector('[data-requirement-list]');
    var dynamicSection = requestForm.querySelector('[data-dynamic-form-section]');
    var dynamicFields = requestForm.querySelector('[data-form-fields]');
    var dynamicDescription = requestForm.querySelector('[data-dynamic-form-description]');
    var supportingStep = requestForm.querySelector('[data-supporting-step]');
    var availability = requestForm.querySelector('[data-service-availability]');
    var initialFields = {};
    var existingDocuments = [];
    var editMode = requestForm.getAttribute('data-edit-mode') === '1';
    try {
      var initialValue = JSON.parse(requestForm.getAttribute('data-initial-fields') || '{}');
      if (initialValue && typeof initialValue === 'object' && !Array.isArray(initialValue)) initialFields = initialValue;
    } catch (ignoreInitialFields) { initialFields = {}; }
    try {
      var existingValue = JSON.parse(requestForm.getAttribute('data-existing-documents') || '[]');
      if (Array.isArray(existingValue)) existingDocuments = existingValue;
    } catch (ignoreExistingDocuments) { existingDocuments = []; }

    function selectedService() {
      if (!select) return null;
      return services.find(function (service) { return String(service.slug) === String(select.value); }) || null;
    }

    function serviceFields(service) {
      var schema = service && service.form_schema && typeof service.form_schema === 'object' ? service.form_schema : {};
      return Array.isArray(schema.fields) ? schema.fields : [];
    }

    function updateRequirements(service) {
      var requirements = service && Array.isArray(service.requirements) ? service.requirements.slice(0) : [];
      if (!requirementList || !requirementBox) return;
      requirementList.textContent = '';
      requirements.forEach(function (requirement) {
        var item = document.createElement('li');
        item.textContent = String(requirement);
        requirementList.appendChild(item);
      });
      requirementBox.classList.toggle('d-none', requirements.length === 0);
    }

    function createTextControl(field, id) {
      var control;
      if (field.type === 'textarea') {
        control = document.createElement('textarea');
        control.rows = 4;
      } else if (field.type === 'select') {
        control = document.createElement('select');
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Pilih ' + String(field.label || 'jawaban').toLowerCase();
        control.appendChild(placeholder);
        (Array.isArray(field.options) ? field.options : []).forEach(function (option) {
          if (!option || typeof option !== 'object') return;
          var item = document.createElement('option');
          item.value = String(option.value || '');
          item.textContent = String(option.label || option.value || '');
          control.appendChild(item);
        });
      } else {
        control = document.createElement('input');
        control.type = ['date', 'number', 'tel', 'email'].indexOf(String(field.type || '')) !== -1 ? field.type : 'text';
      }
      control.id = id;
      control.name = 'warga_fields[' + String(field.key || '') + ']';
      control.className = 'form-control';
      control.required = !!field.required;
      if (field.placeholder && field.type !== 'select') control.placeholder = String(field.placeholder);
      if (field.max_length && field.type !== 'date' && field.type !== 'number' && field.type !== 'select') {
        control.maxLength = Math.max(1, Math.min(5000, Number(field.max_length) || 500));
      }
      return control;
    }

    function createFileControl(field, id) {
      var fragment = document.createDocumentFragment();
      var uploadLabel = document.createElement('label');
      uploadLabel.className = 'warga-dynamic-upload';
      uploadLabel.htmlFor = id;
      var icon = document.createElement('i');
      icon.className = 'fa fa-paperclip';
      var title = document.createElement('strong');
      title.textContent = field.multiple ? 'Pilih Berkas' : 'Pilih Satu Berkas';
      var hint = document.createElement('span');
      hint.textContent = 'JPG, PNG, atau PDF · maksimal ' + Math.max(1, Math.min(10, Number(field.max_size_mb) || 5)) + ' MB';
      uploadLabel.appendChild(icon);
      uploadLabel.appendChild(title);
      uploadLabel.appendChild(hint);

      var input = document.createElement('input');
      input.type = 'file';
      input.id = id;
      input.name = 'warga_files[' + String(field.key || '') + '][]';
      // Keep required inputs focusable so native validation can lead the user
      // back to the matching upload zone instead of failing silently.
      input.className = 'warga-file-input-native';
      input.accept = String(field.accept || 'image/jpeg,image/png,application/pdf');
      input.multiple = !!field.multiple;
      var existingForField = existingDocuments.filter(function (document) {
        return document && String(document.field_key || '') === String(field.key || '');
      });
      // A revision may retain an existing required file. The server still
      // validates the requirement; the browser should not force a needless
      // re-upload unless the citizen chooses to replace it.
      input.required = !!field.required && existingForField.length === 0;
      input.setAttribute('data-dynamic-file-input', '');
      input.setAttribute('data-max-files', field.multiple ? '5' : '1');
      input.setAttribute('data-max-size-mb', String(Math.max(1, Math.min(10, Number(field.max_size_mb) || 5))));

      var list = document.createElement('div');
      list.className = 'warga-file-list';
      list.setAttribute('data-dynamic-file-list', '');
      var empty = document.createElement('span');
      empty.textContent = existingForField.length
        ? 'Berkas tersimpan akan tetap digunakan jika tidak diganti.'
        : 'Belum ada berkas dipilih.';
      list.appendChild(empty);
      fragment.appendChild(uploadLabel);
      fragment.appendChild(input);
      fragment.appendChild(list);
      return fragment;
    }

    function renderDynamicForm(service) {
      if (!dynamicFields || !dynamicSection) return;
      var fields = serviceFields(service);
      dynamicFields.textContent = '';
      fields.forEach(function (field, index) {
        if (!field || typeof field !== 'object' || !field.key || !field.label) return;
        var id = 'warga-field-' + String(field.key).replace(/[^a-z0-9_-]/gi, '-') + '-' + index;
        var wrapper = document.createElement('div');
        wrapper.className = 'warga-dynamic-field' + (field.type === 'file' || field.type === 'textarea' ? ' is-wide' : '');
        var label = document.createElement('label');
        label.htmlFor = id;
        label.textContent = String(field.label);
        if (field.required) {
          var required = document.createElement('em');
          required.textContent = '*';
          label.appendChild(required);
        }
        wrapper.appendChild(label);
        var control = field.type === 'file' ? createFileControl(field, id) : createTextControl(field, id);
        wrapper.appendChild(control);
        if (field.type !== 'file' && Object.prototype.hasOwnProperty.call(initialFields, field.key)
          && control && typeof control.value !== 'undefined') {
          control.value = String(initialFields[field.key] == null ? '' : initialFields[field.key]);
        }
        if (field.help) {
          var help = document.createElement('small');
          help.textContent = String(field.help);
          wrapper.appendChild(help);
        }
        dynamicFields.appendChild(wrapper);
      });
      var hasFields = dynamicFields.children.length > 0;
      dynamicSection.classList.toggle('d-none', !hasFields);
      if (supportingStep) supportingStep.textContent = hasFields ? '4' : '3';
      if (dynamicDescription) dynamicDescription.textContent = service && service.description ? String(service.description) : 'Lengkapi isian yang dibutuhkan untuk layanan ini.';
    }

    function updateSelectedService() {
      var service = selectedService();
      updateRequirements(service);
      renderDynamicForm(service);
      if (availability) {
        var enabled = !service || service.submission_enabled !== false;
        availability.textContent = enabled ? '' : (service.availability_note || 'Layanan ini belum dapat diajukan melalui aplikasi warga.');
        availability.classList.toggle('d-none', enabled);
      }
    }
    if (select) {
      select.addEventListener('change', updateSelectedService);
      updateSelectedService();
    }
  }

  function updateFileList(input, list, maxFiles, maxSizeMb) {
      list.textContent = '';
      var files = Array.prototype.slice.call(input.files || []);
      if (!files.length) {
        var empty = document.createElement('span');
        empty.textContent = 'Belum ada berkas dipilih.';
        list.appendChild(empty);
        return true;
      }
      if (files.length > maxFiles) {
        var countWarning = document.createElement('span');
        countWarning.className = 'color-red-dark';
        countWarning.textContent = 'Maksimal ' + maxFiles + ' berkas dapat dipilih.';
        list.appendChild(countWarning);
        input.value = '';
        return false;
      }
      var oversized = false;
      files.forEach(function (file) {
        if (file.size < 1 || file.size > maxSizeMb * 1024 * 1024) oversized = true;
        var row = document.createElement('div');
        row.className = 'warga-file-item';
        var icon = document.createElement('i');
        icon.className = file.type === 'application/pdf' ? 'fa fa-file-pdf color-red-dark' : 'fa fa-file-image color-blue-dark';
        var label = document.createElement('span');
        label.textContent = file.name + ' · ' + Math.max(1, Math.round(file.size / 1024)) + ' KB';
        row.appendChild(icon);
        row.appendChild(label);
        list.appendChild(row);
      });
      if (oversized) {
        var sizeWarning = document.createElement('span');
        sizeWarning.className = 'color-red-dark';
        sizeWarning.textContent = 'Setiap berkas maksimal ' + maxSizeMb + ' MB.';
        list.appendChild(sizeWarning);
        input.value = '';
        return false;
      }
      return true;
  }

  var fileInput = document.querySelector('[data-file-input]');
  var fileList = document.querySelector('[data-file-list]');
  if (fileInput && fileList) {
    fileInput.addEventListener('change', function () { updateFileList(fileInput, fileList, 5, 5); });
  }
  if (requestForm) {
    requestForm.addEventListener('change', function (event) {
      var input = event.target;
      if (!input || !input.hasAttribute('data-dynamic-file-input')) return;
      var list = input.parentNode.querySelector('[data-dynamic-file-list]');
      if (!list) return;
      updateFileList(input, list, Math.max(1, Number(input.getAttribute('data-max-files')) || 1), Math.max(1, Number(input.getAttribute('data-max-size-mb')) || 5));
    });
  }

  document.querySelectorAll('[data-paged-list]').forEach(function (listing) {
    if (!window.fetch || !window.URL || !window.FormData) return;
    var form = listing.querySelector('[data-list-search]');
    var results = listing.querySelector('[data-list-results]');
    var feedback = listing.querySelector('[data-list-feedback]');
    var errorBox = listing.querySelector('[data-list-error]');
    var errorMessage = listing.querySelector('[data-list-error-message]');
    var retry = listing.querySelector('[data-list-retry]');
    var login = listing.querySelector('[data-list-login]');
    var filterLinks = listing.querySelectorAll('[data-list-filter]');
    var pendingController = null;
    var sequence = 0;
    var lastAttempt = null;

    function searchUrl(status) {
      var url = new URL(form.action, window.location.href);
      new FormData(form).forEach(function (value, key) {
        if (String(value).trim()) url.searchParams.set(key, String(value).trim());
      });
      if (status) url.searchParams.set('status', status);
      url.searchParams.set('page', '1');
      return url;
    }

    function syncFilters(url) {
      ['q', 'date', 'status'].forEach(function (name) {
        var field = form.elements.namedItem(name);
        if (field) field.value = url.searchParams.get(name) || (name === 'status' ? 'all' : '');
      });
      var status = url.searchParams.get('status') || 'all';
      filterLinks.forEach(function (link) {
        var value = link.getAttribute('data-list-filter');
        var active = value === status;
        link.classList.toggle('active', active);
        if (active) link.setAttribute('aria-current', 'true');
        else link.removeAttribute('aria-current');
        link.href = searchUrl(value).href;
      });
    }

    function loadPage(url, scrollToResults) {
      var requestNumber = ++sequence;
      if (pendingController) pendingController.abort();
      pendingController = window.AbortController ? new AbortController() : null;
      lastAttempt = { url: url, scroll: scrollToResults };
      errorBox.hidden = true;
      feedback.classList.remove('visually-hidden');
      feedback.textContent = 'Memuat data…';
      results.setAttribute('aria-busy', 'true');
      listing.classList.add('is-loading');
      var options = {
        method: 'GET', credentials: 'same-origin', cache: 'no-store',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      };
      if (pendingController) options.signal = pendingController.signal;
      fetch(url.href, options).then(function (response) {
        if (response.redirected || response.status === 401 || response.status === 403) {
          var authError = new Error('Sesi Anda telah berakhir. Silakan masuk kembali.');
          authError.sessionExpired = true;
          throw authError;
        }
        if (!response.ok || (response.headers.get('Content-Type') || '').indexOf('application/json') === -1) {
          throw new Error('Data belum dapat dimuat. Silakan coba lagi.');
        }
        return response.json();
      }).then(function (data) {
        if (requestNumber !== sequence) return;
        if (!data || typeof data.html !== 'string' || !Number.isFinite(Number(data.page))) {
          throw new Error('Data belum dapat dimuat. Silakan coba lagi.');
        }
        // The same escaped, authenticated partial renders initial and AJAX results.
        results.innerHTML = data.html;
        url.searchParams.set('page', String(data.page));
        if (data.filters) {
          ['q', 'date', 'status'].forEach(function (name) {
            if (!form.elements.namedItem(name)) return;
            if (data.filters[name]) url.searchParams.set(name, data.filters[name]);
            else url.searchParams.delete(name);
          });
        }
        syncFilters(url);
        try { window.history.replaceState(window.history.state, '', url.href); } catch (ignore) {}
        var summary = results.querySelector('[data-list-summary]');
        feedback.classList.add('visually-hidden');
        feedback.textContent = (summary ? summary.textContent : 'Data diperbarui.') + '. Halaman ' + data.page + ' dari ' + data.pages + '.';
        if (scrollToResults) {
          results.setAttribute('tabindex', '-1');
          results.focus({ preventScroll: true });
          results.scrollIntoView({ block: 'start', behavior: 'auto' });
        }
      }).catch(function (error) {
        if (requestNumber !== sequence || error.name === 'AbortError') return;
        feedback.textContent = '';
        errorMessage.textContent = error.sessionExpired ? error.message : 'Data belum dapat dimuat. Periksa koneksi internet lalu coba lagi.';
        retry.hidden = !!error.sessionExpired;
        login.hidden = !error.sessionExpired;
        errorBox.hidden = false;
      }).finally(function () {
        if (requestNumber !== sequence) return;
        results.setAttribute('aria-busy', 'false');
        listing.classList.remove('is-loading');
        pendingController = null;
      });
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      loadPage(searchUrl(), false);
    });
    listing.addEventListener('click', function (event) {
      var control = event.target.closest('[data-list-page], [data-list-filter], [data-list-reset], [data-list-retry]');
      if (!control || !listing.contains(control) || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button > 0) return;
      event.preventDefault();
      if (control.hasAttribute('data-list-retry')) {
        if (lastAttempt) loadPage(lastAttempt.url, lastAttempt.scroll);
      } else if (control.hasAttribute('data-list-filter')) {
        loadPage(searchUrl(control.getAttribute('data-list-filter')), false);
      } else {
        loadPage(new URL(control.href, window.location.href), control.hasAttribute('data-list-page'));
      }
    });
  });

  var staffActionForm = document.querySelector('[data-staff-action-form]');
  if (staffActionForm) {
    var staffNote = staffActionForm.querySelector('[data-staff-note]');
    staffActionForm.addEventListener('submit', function (event) {
      var submitter = event.submitter;
      var needsNote = submitter && submitter.hasAttribute('data-requires-note');
      if (staffNote) {
        staffNote.classList.toggle('is-invalid', needsNote && !staffNote.value.trim());
        if (needsNote && !staffNote.value.trim()) {
          event.preventDefault();
          staffNote.required = true;
          staffNote.focus();
          staffNote.reportValidity();
          return;
        }
        staffNote.required = false;
      }
      if (submitter) submitter.disabled = true;
    });
    if (staffNote) staffNote.addEventListener('input', function () { staffNote.classList.remove('is-invalid'); });
  }

  (function initOfficialLetterModal() {
    var opener = document.querySelector('[data-warga-letter-open]');
    var modal = document.getElementById('warga-letter-modal');
    if (!opener || !modal) return;
    if (modal.parentNode !== document.body) document.body.appendChild(modal);
    var frame = modal.querySelector('[data-warga-letter-frame]');
    var status = modal.querySelector('[data-warga-letter-status]');
    var download = modal.querySelector('[data-warga-letter-download]');
    var activeOpener = null;
    var currentHtml = '';
    var currentName = 'surat-resmi.html';
    var controller = null;
    var sequence = 0;
    var pageContent = document.getElementById('page');
    var pageWasInert = false;

    function close() {
      sequence++;
      if (controller) controller.abort();
      controller = null;
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('warga-letter-modal-open');
      if (pageContent) pageContent.inert = pageWasInert;
      if (frame) { frame.hidden = true; frame.srcdoc = ''; }
      currentHtml = '';
      if (download) download.disabled = true;
      if (activeOpener && typeof activeOpener.focus === 'function') activeOpener.focus();
      activeOpener = null;
    }

    function open(button) {
      var url = button.getAttribute('data-html-url');
      if (!url || !frame || !status) return;
      if (new URL(url, window.location.href).origin !== window.location.origin) return;
      var requestNumber = ++sequence;
      activeOpener = button;
      currentHtml = '';
      currentName = (button.getAttribute('data-html-name') || 'surat-resmi.html').replace(/[\\/:*?"<>|]+/g, '-');
      if (!/\.html?$/i.test(currentName)) currentName += '.html';
      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('warga-letter-modal-open');
      if (pageContent) { pageWasInert = pageContent.inert; pageContent.inert = true; }
      modal.querySelector('.warga-letter-icon-button').focus();
      frame.hidden = true;
      frame.srcdoc = '';
      status.classList.remove('is-error');
      status.hidden = false;
      status.textContent = 'Memuat surat...';
      if (download) download.disabled = true;
      if (controller) controller.abort();
      controller = window.AbortController ? new AbortController() : null;
      var options = { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'text/html' } };
      if (controller) options.signal = controller.signal;
      fetch(url, options).then(function (response) {
        if (response.redirected || response.status === 401 || response.status === 403) throw new Error('Sesi Anda telah berakhir. Silakan masuk kembali.');
        if (!response.ok || (response.headers.get('Content-Type') || '').toLowerCase().indexOf('text/html') === -1) throw new Error('Surat belum dapat dimuat.');
        return response.text();
      }).then(function (html) {
        if (requestNumber !== sequence || modal.hidden) return;
        if (!html || html.length > 8 * 1024 * 1024) throw new Error('Ukuran surat tidak dapat ditampilkan.');
        currentHtml = html;
        frame.srcdoc = html;
        frame.hidden = false;
        status.hidden = true;
        if (download) download.disabled = false;
      }).catch(function (error) {
        if (error.name === 'AbortError' || requestNumber !== sequence || modal.hidden) return;
        status.hidden = false;
        status.classList.add('is-error');
        status.textContent = error.message || 'Surat belum dapat dimuat. Coba lagi.';
      });
    }

    document.addEventListener('click', function (event) {
      var button = event.target.closest('[data-warga-letter-open], [data-warga-letter-close], [data-warga-letter-download]');
      if (!button) return;
      if (button.hasAttribute('data-warga-letter-open')) { event.preventDefault(); open(button); }
      else if (button.hasAttribute('data-warga-letter-close')) { event.preventDefault(); close(); }
      else if (button.hasAttribute('data-warga-letter-download') && currentHtml) {
        event.preventDefault();
        var blobUrl = URL.createObjectURL(new Blob([currentHtml], { type: 'text/html;charset=utf-8' }));
        var link = document.createElement('a');
        link.href = blobUrl; link.download = currentName; link.rel = 'noopener';
        document.body.appendChild(link); link.click(); link.remove();
        window.setTimeout(function () { URL.revokeObjectURL(blobUrl); }, 1000);
      }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !modal.hidden) { event.preventDefault(); close(); }
      if (event.key === 'Tab' && !modal.hidden) {
        var buttons = Array.prototype.slice.call(modal.querySelectorAll('.warga-letter-modal-panel button:not(:disabled)'));
        var first = buttons[0], last = buttons[buttons.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
      }
    });
  }());

  document.querySelectorAll('[data-password-toggle]').forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      var id = toggle.getAttribute('aria-controls');
      var input = id ? document.getElementById(id) : null;
      if (!input) return;
      var visible = input.type === 'text';
      input.type = visible ? 'password' : 'text';
      toggle.setAttribute('aria-pressed', visible ? 'false' : 'true');
      toggle.setAttribute('aria-label', visible ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
      var icon = toggle.querySelector('i');
      if (icon) icon.className = visible ? 'fa fa-eye' : 'fa fa-eye-slash';
    });
  });

  (function initLogoutConfirmation() {
    var activeForm = null;
    var activeTrigger = null;
    var dialog = null;
    function close() {
      if (!dialog) return;
      dialog.hidden = true;
      document.body.classList.remove('warga-dialog-open');
      if (activeTrigger && typeof activeTrigger.focus === 'function') activeTrigger.focus();
      activeForm = null; activeTrigger = null;
    }
    function ensureDialog() {
      if (dialog) return dialog;
      dialog = document.createElement('div');
      dialog.className = 'warga-confirm-dialog';
      dialog.hidden = true;
      dialog.setAttribute('role', 'dialog');
      dialog.setAttribute('aria-modal', 'true');
      dialog.setAttribute('aria-labelledby', 'warga-confirm-title');
      dialog.innerHTML = '<button type="button" class="warga-confirm-backdrop" data-confirm-cancel aria-label="Tutup"></button><div class="warga-confirm-panel"><span class="warga-confirm-icon" aria-hidden="true"><i class="fa fa-sign-out-alt"></i></span><h2 id="warga-confirm-title">Keluar dari akun?</h2><p>Anda yakin ingin keluar dari akun ini?</p><div class="warga-confirm-actions"><button type="button" class="btn warga-confirm-cancel" data-confirm-cancel>Tidak</button><button type="button" class="btn bg-red-dark color-white" data-confirm-accept>Ya, keluar</button></div></div>';
      document.body.appendChild(dialog);
      dialog.addEventListener('click', function (event) {
        var target = event.target.closest('[data-confirm-cancel], [data-confirm-accept]');
        if (!target) return;
        if (target.hasAttribute('data-confirm-cancel')) { event.preventDefault(); close(); return; }
        if (!activeForm) return;
        event.preventDefault();
        var form = activeForm;
        close();
        form.setAttribute('data-logout-confirmed', 'true');
        if (HTMLFormElement.prototype.submit) HTMLFormElement.prototype.submit.call(form);
      });
      return dialog;
    }
    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!form || !form.matches('[data-logout-form]') || form.getAttribute('data-logout-confirmed') === 'true') return;
      event.preventDefault();
      activeForm = form;
      activeTrigger = form.querySelector('button[type="submit"]');
      var box = ensureDialog();
      box.hidden = false;
      document.body.classList.add('warga-dialog-open');
      var cancel = box.querySelector('[data-confirm-cancel]:not(.warga-confirm-backdrop)');
      if (cancel) cancel.focus();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && dialog && !dialog.hidden) { event.preventDefault(); close(); }
      if (event.key === 'Tab' && dialog && !dialog.hidden) {
        var buttons = dialog.querySelectorAll('.warga-confirm-panel button:not(:disabled)');
        var first = buttons[0], last = buttons[buttons.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
      }
    });
  }());

  var deferredInstall = null;
  var installPanels = Array.prototype.slice.call(document.querySelectorAll('[data-pwa-install-panel]'));
  function updateInstallStatus(message, installed) {
    installPanels.forEach(function (panel) {
      panel.classList.toggle('is-installed', !!installed);
      var status = panel.querySelector('[data-pwa-install-status]');
      if (status && message) status.textContent = message;
    });
  }
  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    deferredInstall = event;
    updateInstallStatus('Aplikasi siap dipasang pada perangkat ini.', false);
  });
  document.querySelectorAll('[data-pwa-install]').forEach(function (button) {
    button.addEventListener('click', function () {
      if (!deferredInstall) {
        updateInstallStatus('Instalasi tersedia melalui menu aplikasi pada browser.', false);
        return;
      }
      deferredInstall.prompt();
      deferredInstall.userChoice.then(function (choice) {
        if (choice.outcome === 'accepted') updateInstallStatus('SmartDesa Warga sudah terpasang.', true);
        deferredInstall = null;
      });
    });
  });
  if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) updateInstallStatus('SmartDesa Warga sudah terpasang.', true);

  if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(config.serviceWorkerUrl || ((config.baseUrl || '/') + 'service-worker.js'), {scope: config.serviceWorkerScope || config.baseUrl || '/'}).catch(function () {});
    });
  }
})();
