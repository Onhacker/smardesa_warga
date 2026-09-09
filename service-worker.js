'use strict';

const SDW_CACHE_PREFIX = 'smartdesa-warga-static-';
const SDW_CACHE = SDW_CACHE_PREFIX + '2026-09-09-market-image-cache-84';
// Product images are versioned by the server (`?v=<token>`), so they can live
// in a separate cache across static-shell releases without serving stale data.
const SDW_IMAGE_CACHE = 'smartdesa-warga-market-images-v1';
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
  'assets/pwa/notification-badge.png',
  'assets/v22/styles/bootstrap.min.css',
  'assets/v22/fonts/css/fontawesome-all.min.css',
  'assets/vendor/tabler-icons/tabler-warga.min.css?v=1',
  'assets/vendor/tabler-icons/fonts/tabler-icons8aff.woff2',
  'assets/css/simp-v22.min.css?v=1',
  'assets/css/warga.min.css?v=113',
  'assets/v22/scripts/bootstrap.min.js',
  'assets/v22/scripts/custom.min.js?v=3',
  'assets/js/warga.min.js?v=22',
  'assets/js/community.min.js?v=17',
  'assets/css/community.min.css?v=25',
  'assets/css/market.css?v=21',
  'assets/js/market.js?v=10',
  'assets/images/market-product-placeholder.svg',
  'assets/v22/images/pictures/pasar-layanan.webp',
  'assets/v22/images/pictures/surat-layanan.webp',
  'assets/v22/images/pictures/pengaduan-layanan.webp',
  'assets/v22/images/pictures/pengumuman-layanan.webp',
  'assets/v22/images/pictures/notifikasi-layanan.webp',
  'assets/v22/images/pictures/kontak-lembaga.webp'
].map(function (path) { return new URL(path, scopeUrl).href; });

function isStaticAsset(request, url) {
  if (request.method !== 'GET' || url.origin !== self.location.origin || !url.pathname.startsWith(appPath + 'assets/')) return false;
  if (request.headers.has('authorization') || request.headers.has('range') || request.headers.get('x-requested-with')) return false;
  return /\.(?:css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|otf)$/i.test(url.pathname);
}

function isMarketplaceImage(request, url) {
  if (request.method !== 'GET' || url.origin !== self.location.origin) return false;
  var route = appPath + 'pasar/gambar/';
  if (!url.pathname.startsWith(route) || !/^\d+$/.test(url.pathname.slice(route.length))) return false;
  if (!/^[a-f0-9]{20}$/i.test(url.searchParams.get('v') || '')) return false;
  if (request.headers.has('authorization') || request.headers.has('range') || request.headers.get('x-requested-with')) return false;
  return true;
}

function marketplaceImageFamily(url) {
  return url.pathname + '::' + (url.searchParams.get('variant') === 'thumb' ? 'thumb' : 'full');
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
  if (isMarketplaceImage(request, url)) {
    event.respondWith(caches.open(SDW_IMAGE_CACHE).then(function (cache) {
      return cache.match(request).then(function (cached) {
        if (cached) return cached;
        return fetch(request).then(function (response) {
          // Draft/private images deliberately remain network-only. Published
          // image responses carry an explicit public cache header from PHP.
          var cacheControl = response.headers.get('Cache-Control') || '';
          if (response.ok && response.type === 'basic' && /\bpublic\b/i.test(cacheControl) && !/\bno-store\b/i.test(cacheControl)) {
            return cache.put(request, response.clone()).then(function () {
              // Keep one version per image/variant so repeated product edits
              // do not leave unbounded old entries in Cache Storage.
              var family = marketplaceImageFamily(url);
              return cache.keys().then(function (keys) {
                return Promise.all(keys.filter(function (key) {
                  var keyUrl = new URL(key.url);
                  return key.url !== request.url && marketplaceImageFamily(keyUrl) === family;
                }).map(function (key) { return cache.delete(key); }));
              });
            }).catch(function () {}).then(function () { return response; });
          }
          return response;
        });
      });
    }));
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

self.addEventListener('push', function (event) {
  var data = {};
  try { data = event.data ? event.data.json() : {}; } catch (_) {}
  var url = new URL(data.url || 'notifikasi', scopeUrl);
  if (url.origin !== scopeUrl.origin || !url.pathname.startsWith(appPath)) url = new URL('notifikasi', scopeUrl);
  event.waitUntil(self.registration.showNotification(data.title || 'SmartDesa Warga', {
    body: data.body || 'Ada pembaruan layanan untuk Anda.',
    icon: new URL('assets/pwa/icon-192.png',scopeUrl).href,
    badge: new URL('assets/pwa/notification-badge.png',scopeUrl).href,
    tag: data.tag || 'sdw-notification', renotify: true, silent: false,
    vibrate: [200,100,200],
    data: {url:url.href}
  }));
});
self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = new URL(event.notification.data && event.notification.data.url || 'notifikasi',scopeUrl);
  if (url.origin !== scopeUrl.origin || !url.pathname.startsWith(appPath)) url = new URL('notifikasi',scopeUrl);
  event.waitUntil(self.clients.matchAll({type:'window',includeUncontrolled:true}).then(async function(clients) {
    for (var client of clients) {
      if (client.url.startsWith(scopeUrl.href) && 'focus' in client) { await client.navigate(url.href); return client.focus(); }
    }
    return self.clients.openWindow(url.href);
  }));
});
