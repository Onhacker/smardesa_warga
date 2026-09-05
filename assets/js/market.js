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
    if (!file) return;
    if (!/^image\/(?:jpeg|png|webp)$/i.test(file.type)) return;
    var url = URL.createObjectURL(file);
    target.innerHTML = '';
    var image = document.createElement('img');
    image.alt = 'Pratinjau logo toko';
    image.onload = function () { URL.revokeObjectURL(url); };
    image.src = url;
    target.appendChild(image);
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
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind);
  else bind();
}());
