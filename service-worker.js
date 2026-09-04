'use strict';

const SDW_CACHE_PREFIX = 'smartdesa-warga-static-';
const SDW_CACHE = SDW_CACHE_PREFIX + '2026-09-05-v58';
const scopeUrl = new URL(self.registration.scope);
const appPath = scopeUrl.pathname.endsWith('/') ? scopeUrl.pathname : scopeUrl.pathname + '/';
const offlineUrl = new URL('offline.html', scopeUrl).href;
const precache = [
  'offline.html',
  'manifest.webmanifest',
  'assets/pwa/icon-180.png',
  'assets/pwa/icon-192.png',
  'assets/pwa/icon-512.png',
  'assets/pwa/icon-maskable-512.png',
  'assets/v22/styles/bootstrap.min.css',
  'assets/v22/fonts/css/fontawesome-all.min.css',
  'assets/vendor/tabler-icons/tabler-warga.min.css?v=1',
  'assets/vendor/tabler-icons/fonts/tabler-icons8aff.woff2',
  'assets/css/simp-v22.min.css?v=1',
  'assets/css/warga.min.css?v=54',
  'assets/v22/scripts/bootstrap.min.js',
  'assets/v22/scripts/custom.min.js?v=1',
  'assets/js/warga.min.js?v=10'
].map(function (path) { return new URL(path, scopeUrl).href; });

function isStaticAsset(request, url) {
  if (request.method !== 'GET' || url.origin !== self.location.origin || !url.pathname.startsWith(appPath + 'assets/')) return false;
  if (request.headers.has('authorization') || request.headers.has('range') || request.headers.get('x-requested-with')) return false;
  return /\.(?:css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|otf)$/i.test(url.pathname);
}

self.addEventListener('install', function (event) {
  event.waitUntil(caches.open(SDW_CACHE).then(function (cache) { return cache.addAll(precache); }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (event) {
  event.waitUntil(caches.keys().then(function (keys) {
    return Promise.all(keys.map(function (key) { return key.startsWith(SDW_CACHE_PREFIX) && key !== SDW_CACHE ? caches.delete(key) : false; }));
  }).then(function () { return self.clients.claim(); }));
});

self.addEventListener('fetch', function (event) {
  var request = event.request;
  if (request.method !== 'GET') return;
  var url = new URL(request.url);
  if (url.origin !== self.location.origin) return;
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(function () { return caches.match(offlineUrl).then(function (response) { return response || Response.error(); }); }));
    return;
  }
  if (!isStaticAsset(request, url)) return;
  event.respondWith(caches.open(SDW_CACHE).then(function (cache) {
    return cache.match(request).then(function (cached) {
      if (cached) return cached;
      return fetch(request).then(function (response) { if (response.ok && response.type === 'basic') cache.put(request, response.clone()); return response; });
    });
  }));
});

self.addEventListener('message', function (event) { if (event.data && event.data.type === 'SKIP_WAITING') self.skipWaiting(); });
