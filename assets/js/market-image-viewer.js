(function () {
  'use strict';

  var modal = document.querySelector('[data-market-image-viewer-modal]');
  if (!modal) {
    window.SDWMarketImageViewerReady = true;
    return;
  }
  if (modal.parentNode !== document.body) document.body.appendChild(modal);

  var viewport = modal.querySelector('[data-market-image-viewer-viewport]');
  var stage = modal.querySelector('[data-market-image-viewer-stage]');
  var image = modal.querySelector('[data-market-image-viewer-image]');
  var gesture = modal.querySelector('[data-market-image-viewer-gesture]');
  var status = modal.querySelector('[data-market-image-viewer-status]');
  var statusText = modal.querySelector('[data-market-image-viewer-status-text]');
  var spinner = modal.querySelector('[data-market-image-viewer-spinner]');
  var title = modal.querySelector('[data-market-image-viewer-title]');
  var zoom = modal.querySelector('[data-market-image-viewer-zoom]');
  var zoomOut = modal.querySelector('[data-market-image-viewer-zoom-out]');
  var zoomIn = modal.querySelector('[data-market-image-viewer-zoom-in]');
  var zoomLevel = modal.querySelector('[data-market-image-viewer-zoom-level]');
  var hint = modal.querySelector('[data-market-image-viewer-hint]');
  var page = document.getElementById('page');
  var pageWasInert = false;
  var activeTrigger = null;
  var sequence = 0;
  var baseWidth = 1;
  var baseHeight = 1;
  var fitScale = 1;
  var currentScale = 1;
  var minScale = .1;
  var maxScale = 3;
  var pointers = {};
  var panStart = null;
  var pinchStart = null;

  function setStatus(message, isError) {
    if (!status) return;
    status.hidden = false;
    status.classList.toggle('is-error', Boolean(isError));
    if (statusText) statusText.textContent = message;
    if (spinner) spinner.hidden = Boolean(isError);
  }

  function updateZoomLabel() {
    if (zoomLevel) zoomLevel.textContent = Math.round(currentScale * 100) + '%';
    if (zoomOut) zoomOut.disabled = currentScale <= minScale + .001;
    if (zoomIn) zoomIn.disabled = currentScale >= maxScale - .001;
  }

  function clampScale(value) {
    return Math.max(minScale, Math.min(maxScale, Number(value) || fitScale));
  }

  function setScale(value, focus) {
    if (!viewport || !stage || !image) return;
    var nextScale = clampScale(value);
    var previousScale = currentScale || nextScale;
    var rect = viewport.getBoundingClientRect();
    var focusX = focus && Number.isFinite(focus.x) ? focus.x - rect.left : null;
    var focusY = focus && Number.isFinite(focus.y) ? focus.y - rect.top : null;
    var oldLeft = stage.offsetLeft;
    var oldTop = stage.offsetTop;
    var contentX = focusX === null ? null : (viewport.scrollLeft - oldLeft + focusX) / previousScale;
    var contentY = focusY === null ? null : (viewport.scrollTop - oldTop + focusY) / previousScale;

    stage.style.width = Math.max(1, baseWidth * nextScale) + 'px';
    stage.style.height = Math.max(1, baseHeight * nextScale) + 'px';
    image.style.width = baseWidth + 'px';
    image.style.height = baseHeight + 'px';
    image.style.transform = 'scale(' + nextScale + ')';
    currentScale = nextScale;
    updateZoomLabel();

    if (contentX !== null) {
      viewport.scrollLeft = Math.max(0, stage.offsetLeft + contentX * nextScale - focusX);
      viewport.scrollTop = Math.max(0, stage.offsetTop + contentY * nextScale - focusY);
    }
  }

  function calculateFitScale() {
    if (!viewport || baseWidth < 1 || baseHeight < 1) return 1;
    var styles = window.getComputedStyle ? window.getComputedStyle(viewport) : null;
    var horizontalPadding = styles ? (parseFloat(styles.paddingLeft) || 0) + (parseFloat(styles.paddingRight) || 0) : 0;
    var verticalPadding = styles ? (parseFloat(styles.paddingTop) || 0) + (parseFloat(styles.paddingBottom) || 0) : 0;
    var widthScale = Math.max(1, viewport.clientWidth - horizontalPadding) / baseWidth;
    var heightScale = Math.max(1, viewport.clientHeight - verticalPadding) / baseHeight;
    return Math.min(1, widthScale, heightScale);
  }

  function resetZoom() {
    fitScale = calculateFitScale();
    minScale = Math.max(.05, fitScale * .75);
    maxScale = Math.max(2, Math.min(4, fitScale * 6));
    setScale(fitScale);
    if (viewport) {
      viewport.scrollLeft = 0;
      viewport.scrollTop = 0;
    }
  }

  function zoomBy(multiplier, event) {
    var focus = null;
    if (event && Number.isFinite(event.clientX)) {
      focus = {x: event.clientX, y: event.clientY};
    } else if (viewport) {
      var rect = viewport.getBoundingClientRect();
      focus = {x: rect.left + viewport.clientWidth / 2, y: rect.top + viewport.clientHeight / 2};
    }
    setScale(currentScale * multiplier, focus);
  }

  function pointerList() {
    return Object.keys(pointers).map(function (key) { return pointers[key]; });
  }

  function pointerDistance(first, second) {
    var x = first.x - second.x;
    var y = first.y - second.y;
    return Math.sqrt(x * x + y * y);
  }

  function pointerMidpoint(first, second) {
    return {x: (first.x + second.x) / 2, y: (first.y + second.y) / 2};
  }

  function handlePointerDown(event) {
    if (!gesture || !viewport) return;
    event.preventDefault();
    pointers[event.pointerId] = {x: event.clientX, y: event.clientY};
    try { gesture.setPointerCapture(event.pointerId); } catch (_) {}
    var points = pointerList();
    if (points.length === 1) {
      panStart = {x: event.clientX, y: event.clientY, left: viewport.scrollLeft, top: viewport.scrollTop};
      pinchStart = null;
    } else if (points.length >= 2) {
      pinchStart = {distance: Math.max(1, pointerDistance(points[0], points[1])), scale: currentScale};
      panStart = null;
    }
  }

  function handlePointerMove(event) {
    if (!Object.prototype.hasOwnProperty.call(pointers, event.pointerId) || !viewport) return;
    event.preventDefault();
    pointers[event.pointerId] = {x: event.clientX, y: event.clientY};
    var points = pointerList();
    if (points.length >= 2) {
      if (!pinchStart) pinchStart = {distance: Math.max(1, pointerDistance(points[0], points[1])), scale: currentScale};
      setScale(pinchStart.scale * pointerDistance(points[0], points[1]) / pinchStart.distance, pointerMidpoint(points[0], points[1]));
    } else if (points.length === 1 && panStart) {
      viewport.scrollLeft = Math.max(0, panStart.left - (event.clientX - panStart.x));
      viewport.scrollTop = Math.max(0, panStart.top - (event.clientY - panStart.y));
    }
  }

  function handlePointerEnd(event) {
    delete pointers[event.pointerId];
    try { gesture.releasePointerCapture(event.pointerId); } catch (_) {}
    var points = pointerList();
    if (points.length < 2) pinchStart = null;
    if (points.length === 1 && viewport) {
      panStart = {x: points[0].x, y: points[0].y, left: viewport.scrollLeft, top: viewport.scrollTop};
    } else if (!points.length) {
      panStart = null;
    }
  }

  function hideViewerContent() {
    if (stage) {
      stage.hidden = true;
      stage.style.width = '';
      stage.style.height = '';
    }
    if (image) {
      image.hidden = true;
      image.removeAttribute('src');
      image.style.width = '';
      image.style.height = '';
      image.style.transform = '';
    }
    if (gesture) gesture.hidden = true;
    if (zoom) zoom.hidden = true;
    if (hint) hint.hidden = true;
  }

  function close() {
    sequence += 1;
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('market-image-viewer-open');
    if (page) page.inert = pageWasInert;
    hideViewerContent();
    pointers = {};
    panStart = null;
    pinchStart = null;
    if (activeTrigger && typeof activeTrigger.focus === 'function') activeTrigger.focus();
    activeTrigger = null;
  }

  function reveal(token) {
    if (token !== sequence || modal.hidden || !image || !image.naturalWidth) return;
    baseWidth = Math.max(1, image.naturalWidth);
    baseHeight = Math.max(1, image.naturalHeight);
    if (stage) stage.hidden = false;
    image.hidden = false;
    if (gesture) gesture.hidden = false;
    if (zoom) zoom.hidden = false;
    if (hint) hint.hidden = false;
    if (status) status.hidden = true;
    window.requestAnimationFrame(resetZoom);
  }

  function open(trigger) {
    if (!image || !viewport) return;
    var sourceImage = trigger.querySelector('[data-market-gallery-main]') || document.querySelector('[data-market-gallery-main]');
    var source = sourceImage ? (sourceImage.currentSrc || sourceImage.getAttribute('src') || '') : '';
    if (!source) return;
    try {
      var parsed = new URL(source, window.location.href);
      if (!/^https?:$/.test(parsed.protocol) && parsed.protocol !== 'data:') return;
      source = parsed.href;
    } catch (_) { return; }

    var token = ++sequence;
    activeTrigger = trigger;
    pointers = {};
    panStart = null;
    pinchStart = null;
    hideViewerContent();
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('market-image-viewer-open');
    if (page) {
      pageWasInert = page.inert;
      page.inert = true;
    }
    if (title && sourceImage && sourceImage.alt) title.textContent = sourceImage.alt;
    image.alt = sourceImage && sourceImage.alt ? sourceImage.alt : 'Foto produk';
    setStatus('Memuat gambar…', false);
    var closeButton = modal.querySelector('.market-image-viewer-close');
    if (closeButton) closeButton.focus();

    var completed = false;
    function loaded() {
      if (completed || token !== sequence) return;
      completed = true;
      var decoded = typeof image.decode === 'function' ? image.decode().catch(function () {}) : Promise.resolve();
      decoded.then(function () { reveal(token); });
    }
    function failed() {
      if (completed || token !== sequence) return;
      completed = true;
      setStatus('Gambar belum dapat ditampilkan. Coba lagi.', true);
    }
    image.addEventListener('load', loaded, {once: true});
    image.addEventListener('error', failed, {once: true});
    image.src = source;
    if (image.complete && image.naturalWidth) window.setTimeout(loaded, 0);
  }

  document.addEventListener('click', function (event) {
    var control = event.target && typeof event.target.closest === 'function'
      ? event.target.closest('[data-market-image-viewer-open], [data-market-image-viewer-close], [data-market-image-viewer-zoom-in], [data-market-image-viewer-zoom-out], [data-market-image-viewer-zoom-reset]')
      : null;
    if (!control) return;
    if (control.hasAttribute('data-market-image-viewer-open')) {
      event.preventDefault();
      open(control);
    } else if (control.hasAttribute('data-market-image-viewer-close')) {
      event.preventDefault();
      close();
    } else if (control.hasAttribute('data-market-image-viewer-zoom-in')) {
      event.preventDefault();
      zoomBy(1.2);
    } else if (control.hasAttribute('data-market-image-viewer-zoom-out')) {
      event.preventDefault();
      zoomBy(1 / 1.2);
    } else if (control.hasAttribute('data-market-image-viewer-zoom-reset')) {
      event.preventDefault();
      resetZoom();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (modal.hidden) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      close();
      return;
    }
    if (event.key !== 'Tab') return;
    var items = Array.prototype.slice.call(modal.querySelectorAll('button:not([disabled]), [tabindex]:not([tabindex="-1"])'))
      .filter(function (item) { return !item.hidden && item.offsetParent !== null; });
    var first = items[0];
    var last = items[items.length - 1];
    if (!first || !last) return;
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  if (gesture && viewport) {
    gesture.addEventListener('pointerdown', handlePointerDown);
    gesture.addEventListener('pointermove', handlePointerMove);
    gesture.addEventListener('pointerup', handlePointerEnd);
    gesture.addEventListener('pointercancel', handlePointerEnd);
    gesture.addEventListener('dblclick', function (event) {
      event.preventDefault();
      if (currentScale > fitScale + .05) resetZoom();
      else setScale(Math.min(maxScale, Math.max(1, fitScale * 1.8)), {x: event.clientX, y: event.clientY});
    });
    viewport.addEventListener('wheel', function (event) {
      if (!event.ctrlKey && !event.metaKey) return;
      event.preventDefault();
      zoomBy(event.deltaY < 0 ? 1.1 : 1 / 1.1, event);
    }, {passive: false});
    window.addEventListener('resize', function () {
      if (!modal.hidden && stage && !stage.hidden) resetZoom();
    });
  }

  window.SDWMarketImageViewerReady = true;
}());
