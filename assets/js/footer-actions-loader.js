(function () {
  'use strict';

  var controls = document.querySelectorAll('[data-footer-contact-open], [data-footer-share-open], [data-footer-ios-install]');
  if (!controls.length) return;

  var loading = null;
  function loadFooterActions() {
    if (loading) return loading;
    loading = new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      var baseUrl = (window.SDW && window.SDW.baseUrl) || '/';
      if (baseUrl.charAt(baseUrl.length - 1) !== '/') baseUrl += '/';
      script.src = (window.SDW && window.SDW.footerActionsUrl) || baseUrl + 'assets/js/footer-actions.js';
      script.async = true;
      script.onload = function () { resolve(script); };
      script.onerror = function () { loading = null; reject(new Error('Fitur footer belum dapat dimuat.')); };
      (document.head || document.documentElement).appendChild(script);
    });
    return loading;
  }

  function replay(target) {
    if (!target || !document.documentElement.contains(target)) return;
    target.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, view: window }));
  }

  var replaying = false;
  document.addEventListener('click', function (event) {
    if (replaying) return;
    var target = event.target.closest('[data-footer-contact-open], [data-footer-share-open], [data-footer-ios-install]');
    if (!target || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    loadFooterActions().then(function () {
      replaying = true;
      try { replay(target); } finally { replaying = false; }
    }).catch(function () {});
  }, true);
}());
