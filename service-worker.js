'use strict';

const SDW_CACHE_PREFIX = 'smartdesa-warga-static-';
const SDW_CACHE = SDW_CACHE_PREFIX + '2026-09-21-region-labels-106';
// Product images are versioned by the server (`?v=<token>`), so they can live
// in a separate cache across static-shell releases without serving stale data.
const SDW_IMAGE_CACHE_PREFIX = 'smartdesa-warga-market-images-';
const SDW_IMAGE_CACHE = SDW_IMAGE_CACHE_PREFIX + 'v2';
// Keep the image bucket bounded even when a device browses many products. A
// product can have one full-size and one thumbnail entry, so 120 entries keep
// roughly the last 60 products while preventing unbounded Cache Storage use.
const SDW_IMAGE_CACHE_MAX_ENTRIES = 120;
const SDW_ICON_URL = 'assets/pwa/icon-192.png?v=20260913-icon-1';
const scopeUrl = new URL(self.registration.scope);
const appPath = scopeUrl.pathname.endsWith('/') ? scopeUrl.pathname : scopeUrl.pathname + '/';
const assetPath = new URL('assets/', scopeUrl).pathname;
const pdfAssetPath = new URL('assets/vendor/pdfjs/', scopeUrl).pathname;
const marketplaceImagePath = new URL('pasar/gambar/', scopeUrl).pathname;
const privateAnnouncementPath = new URL('pengumuman/', scopeUrl).pathname;
const offlineUrl = new URL('offline.html', scopeUrl).href;
const notificationFallbackUrl = new URL('notifikasi', scopeUrl);
const precache = [
  'offline.html',
  SDW_ICON_URL,
  'assets/pwa/notification-badge.png?v=20260913-badge-2'
].map(function (path) { return new URL(path, scopeUrl).href; });

function isStaticAsset(request, url) {
  if (request.method !== 'GET' || url.origin !== self.location.origin || !url.pathname.startsWith(assetPath)) return false;
  // PDF.js is loaded only when a PDF viewer is opened. Let the browser's HTTP
  // cache handle it instead of retaining ~1.8 MB of parser/worker files in
  // the service-worker static bucket for every user.
  if (url.pathname.startsWith(pdfAssetPath)) return false;
  if (request.headers.has('authorization') || request.headers.has('range') || request.headers.get('x-requested-with')) return false;
  return /\.(?:css|m?js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|otf)$/i.test(url.pathname);
}

function isMarketplaceImage(request, url) {
  if (request.method !== 'GET' || url.origin !== self.location.origin) return false;
  if (!url.pathname.startsWith(marketplaceImagePath) || !/^\d+$/.test(url.pathname.slice(marketplaceImagePath.length))) return false;
  if (!/^[a-f0-9]{20}$/i.test(url.searchParams.get('v') || '')) return false;
  if (request.headers.has('authorization') || request.headers.has('range') || request.headers.get('x-requested-with')) return false;
  return true;
}

// Announcement attachments are authenticated, tenant-scoped files. Never
// put them into a Cache Storage bucket, even when the browser requests an
// image/PDF with a mode that could otherwise look cacheable.
function isPrivateAnnouncementAttachment(request, url) {
  if (request.method !== 'GET' || url.origin !== self.location.origin) return false;
  return url.pathname.startsWith(privateAnnouncementPath) && /\/lampiran$/i.test(url.pathname);
}

function marketplaceImageFamily(url) {
  return url.pathname + '::' + (url.searchParams.get('variant') === 'thumb' ? 'thumb' : 'full');
}

function cacheKeyUrl(key) {
  return key && key.url ? key.url : String(key || '');
}

async function trimMarketplaceImageCache(cache, protectedUrl) {
  var keys = await cache.keys();
  if (keys.length <= SDW_IMAGE_CACHE_MAX_ENTRIES) return;
  var removeCount = keys.length - SDW_IMAGE_CACHE_MAX_ENTRIES;
  // Cache.keys() is insertion ordered in Cache Storage. Preserve the request
  // just stored (important when many image requests finish concurrently), then
  // evict the oldest remaining entries first.
  var candidates = keys.filter(function (key) { return cacheKeyUrl(key) !== protectedUrl; });
  return Promise.all(candidates.slice(0, removeCount).map(function (key) { return cache.delete(key); }));
}

function notificationUrl(value) {
  try {
    var url = new URL(typeof value === 'string' && value.trim() ? value : 'notifikasi', scopeUrl);
    if (url.origin !== scopeUrl.origin || !url.pathname.startsWith(appPath)) return notificationFallbackUrl;
    return url;
  } catch (_) {
    return notificationFallbackUrl;
  }
}

function isAppWindow(client) {
  try {
    var url = new URL(client.url);
    return url.origin === scopeUrl.origin && url.pathname.startsWith(appPath);
  } catch (_) {
    return false;
  }
}

async function openNotificationTarget(url) {
  var windows;
  try {
    windows = await self.clients.matchAll({type: 'window', includeUncontrolled: true});
  } catch (_) {
    return self.clients.openWindow(url.href);
  }
  var appWindows = windows.filter(isAppWindow);

  // Reuse an already-open detail page first. Comparing href preserves the
  // notification id, query string and hash instead of only matching a route.
  for (var i = 0; i < appWindows.length; i += 1) {
    if (appWindows[i].url === url.href && 'focus' in appWindows[i]) {
      try {
        var existing = await appWindows[i].focus();
        if (existing) return existing;
      } catch (_) {}
    }
  }

  // Navigating the active PWA window keeps an installed app in standalone
  // mode. A failed/stale WindowClient must not abort the click event: try the
  // next client and finally open the exact target in a new app window.
  appWindows.sort(function (left, right) {
    return Number(Boolean(right.focused)) - Number(Boolean(left.focused)) ||
      Number(right.visibilityState === 'visible') - Number(left.visibilityState === 'visible');
  });
  for (var j = 0; j < appWindows.length; j += 1) {
    var client = appWindows[j];
    if (!('navigate' in client)) continue;
    try {
      var navigated = await client.navigate(url.href);
      if (navigated && 'focus' in navigated) {
        var focused = await navigated.focus();
        if (focused) return focused;
      }
    } catch (_) {}
  }

  return self.clients.openWindow(url.href);
}

self.addEventListener('install', function (event) {
  event.waitUntil(caches.open(SDW_CACHE).then(function (cache) { return cache.addAll(precache); }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (event) {
  var enableNavigationPreload = self.registration.navigationPreload
    ? self.registration.navigationPreload.enable().catch(function () {})
    : Promise.resolve();
  var removeOldCaches = caches.keys().then(function (keys) {
    return Promise.all(keys.map(function (key) {
      var oldStaticCache = key.startsWith(SDW_CACHE_PREFIX) && key !== SDW_CACHE;
      var oldImageCache = key.startsWith(SDW_IMAGE_CACHE_PREFIX) && key !== SDW_IMAGE_CACHE;
      return (oldStaticCache || oldImageCache) ? caches.delete(key) : false;
    }));
  });
  var trimCurrentImageCache = removeOldCaches.then(function () {
    return caches.open(SDW_IMAGE_CACHE).then(function (cache) {
      return trimMarketplaceImageCache(cache).catch(function () {});
    });
  });
  event.waitUntil(Promise.all([enableNavigationPreload, removeOldCaches, trimCurrentImageCache]).then(function () { return self.clients.claim(); }));
});

self.addEventListener('fetch', function (event) {
  var request = event.request;
  if (request.method !== 'GET') return;
  var url = new URL(request.url);
  if (url.origin !== self.location.origin) return;
  if (isPrivateAnnouncementAttachment(request, url)) {
    event.respondWith(fetch(request));
    return;
  }
  if (request.mode === 'navigate') {
    event.respondWith(Promise.resolve(event.preloadResponse).then(function (preloaded) {
      return preloaded || fetch(request);
    }).catch(function () {
      return caches.match(offlineUrl).then(function (response) { return response || Response.error(); });
    }));
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
              }).then(function () { return trimMarketplaceImageCache(cache, request.url); });
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

function updateAppBadge(value) {
  // The Badging API is optional and is not exposed by every Android/TWA
  // runtime. Treat a missing API (or a rejected call) as a no-op so it never
  // prevents the visible notification from being shown.
  var count = Number(value);
  if (!Number.isFinite(count) || count < 0 || typeof navigator === 'undefined') return Promise.resolve();
  try {
    if (count > 0 && typeof navigator.setAppBadge === 'function') {
      return Promise.resolve(navigator.setAppBadge(Math.floor(count))).catch(function () {});
    }
    if (count <= 0 && typeof navigator.clearAppBadge === 'function') {
      return Promise.resolve(navigator.clearAppBadge()).catch(function () {});
    }
  } catch (_) {}
  return Promise.resolve();
}

self.addEventListener('message', function (event) {
  if (!event.data) return;
  if (event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
    return;
  }
  if (event.data.type === 'SDW_SET_APP_BADGE') {
    var badgeUpdate = updateAppBadge(event.data.unreadCount);
    if (event.waitUntil) event.waitUntil(badgeUpdate);
  }
});

self.addEventListener('push', function (event) {
  var data = {};
  try { data = event.data ? event.data.json() : {}; } catch (_) {}
  var url = notificationUrl(data.url);
  var badgeUpdate = updateAppBadge(data.unreadCount);
  event.waitUntil(Promise.all([badgeUpdate, self.registration.showNotification(data.title || 'SI DAPULIK', {
    body: data.body || 'Ada pembaruan layanan untuk Anda.',
    icon: new URL(SDW_ICON_URL, scopeUrl).href,
    // Android renders `badge` as a monochrome alpha mask. Never use the
    // opaque, full-colour launcher icon here or it becomes a solid circle.
    badge: new URL('assets/pwa/notification-badge.png?v=20260913-badge-2',scopeUrl).href,
    tag: data.tag || 'sdw-notification', renotify: true, silent: false,
    vibrate: [200,100,200],
    navigate: url.href,
    data: {url:url.href}
  })]));
});
self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var data = event.notification.data || {};
  event.waitUntil(openNotificationTarget(notificationUrl(data.url)));
});
