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

  document.querySelectorAll('form[data-disable-submit]').forEach(function (form) {
    form.addEventListener('submit', function () {
      if (!form.checkValidity()) return;
      var button = form.querySelector('button[type="submit"]');
      if (!button) return;
      button.disabled = true;
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
      if (serviceCount) serviceCount.textContent = visible + ' layanan';
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
      input.required = !!field.required;
      input.setAttribute('data-dynamic-file-input', '');
      input.setAttribute('data-max-files', field.multiple ? '5' : '1');
      input.setAttribute('data-max-size-mb', String(Math.max(1, Math.min(10, Number(field.max_size_mb) || 5))));

      var list = document.createElement('div');
      list.className = 'warga-file-list';
      list.setAttribute('data-dynamic-file-list', '');
      var empty = document.createElement('span');
      empty.textContent = 'Belum ada berkas dipilih.';
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
        wrapper.appendChild(field.type === 'file' ? createFileControl(field, id) : createTextControl(field, id));
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

  var filterButtons = Array.prototype.slice.call(document.querySelectorAll('[data-request-filter]'));
  if (filterButtons.length) {
    var items = Array.prototype.slice.call(document.querySelectorAll('[data-request-item]'));
    var empty = document.querySelector('[data-filter-empty]');
    filterButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        var filter = button.getAttribute('data-request-filter');
        var visible = 0;
        filterButtons.forEach(function (candidate) { candidate.classList.toggle('active', candidate === button); });
        items.forEach(function (item) {
          var show = filter === 'all' || item.getAttribute('data-filter-group') === filter;
          item.classList.toggle('d-none', !show);
          if (show) visible += 1;
        });
        if (empty) empty.classList.toggle('d-none', visible !== 0);
      });
    });
  }

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
