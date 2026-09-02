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
      if (label) label.textContent = online ? 'Terhubung' : 'Tidak terhubung';
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

  var requestForm = document.querySelector('[data-request-form]');
  if (requestForm) {
    var services = [];
    try { services = JSON.parse(requestForm.getAttribute('data-services') || '[]'); } catch (ignore) { services = []; }
    var select = requestForm.querySelector('[data-service-select]');
    var requirementBox = requestForm.querySelector('[data-service-requirements]');
    var requirementList = requestForm.querySelector('[data-requirement-list]');
    function updateRequirements() {
      var selected = services.find(function (service) { return String(service.slug) === String(select.value); });
      var requirements = selected && Array.isArray(selected.requirements) ? selected.requirements : [];
      requirementList.innerHTML = '';
      requirements.forEach(function (requirement) {
        var item = document.createElement('li');
        item.textContent = String(requirement);
        requirementList.appendChild(item);
      });
      requirementBox.classList.toggle('d-none', requirements.length === 0);
    }
    if (select) {
      select.addEventListener('change', updateRequirements);
      updateRequirements();
    }
  }

  var fileInput = document.querySelector('[data-file-input]');
  var fileList = document.querySelector('[data-file-list]');
  if (fileInput && fileList) {
    fileInput.addEventListener('change', function () {
      fileList.innerHTML = '';
      var files = Array.prototype.slice.call(fileInput.files || []);
      if (!files.length) {
        fileList.innerHTML = '<span>Belum ada berkas dipilih.</span>';
        return;
      }
      files.slice(0, 5).forEach(function (file) {
        var row = document.createElement('div');
        row.className = 'warga-file-item';
        var icon = document.createElement('i');
        icon.className = file.type === 'application/pdf' ? 'fa fa-file-pdf color-red-dark' : 'fa fa-file-image color-blue-dark';
        var label = document.createElement('span');
        label.textContent = file.name + ' · ' + Math.max(1, Math.round(file.size / 1024)) + ' KB';
        row.appendChild(icon);
        row.appendChild(label);
        fileList.appendChild(row);
      });
      if (files.length > 5) {
        var warning = document.createElement('span');
        warning.className = 'color-red-dark';
        warning.textContent = 'Maksimal lima berkas dapat dikirim.';
        fileList.appendChild(warning);
        fileInput.value = '';
      }
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
