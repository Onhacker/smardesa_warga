'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const repositoryRoot = path.resolve(__dirname, '..', '..');
const serviceWorkerPath = path.join(repositoryRoot, 'service-worker.js');
const serviceWorkerSource = fs.readFileSync(serviceWorkerPath, 'utf8');

function createCache(name, entries) {
  const values = new Map(entries || []);
  return {
    name,
    values,
    addAll: async function (urls) {
      urls.forEach(function (url) { values.set(url, {url}); });
    },
    match: async function (request) {
      const url = request && request.url ? request.url : String(request);
      return values.get(url) || undefined;
    },
    put: async function (request, response) {
      const url = request && request.url ? request.url : String(request);
      values.set(url, response);
    },
    keys: async function () {
      return Array.from(values.keys()).map(function (url) { return {url}; });
    },
    delete: async function (request) {
      const url = request && request.url ? request.url : String(request);
      return values.delete(url);
    }
  };
}

function runtime(initialCaches) {
  const handlers = {};
  const cacheMap = new Map();
  (initialCaches || []).forEach(function (cache) { cacheMap.set(cache.name, cache); });
  const calls = {deleted: [], opened: [], precached: []};
  const scope = 'https://warga.example/';
  const caches = {
    open: async function (name) {
      if (!cacheMap.has(name)) cacheMap.set(name, createCache(name));
      const cache = cacheMap.get(name);
      if (name.indexOf('static-') !== -1) {
        const originalAddAll = cache.addAll.bind(cache);
        cache.addAll = async function (urls) {
          calls.precached.push.apply(calls.precached, urls);
          return originalAddAll(urls);
        };
      }
      return cache;
    },
    keys: async function () { return Array.from(cacheMap.keys()); },
    delete: async function (name) {
      calls.deleted.push(name);
      return cacheMap.delete(name);
    }
  };
  const self = {
    registration: {
      scope,
      navigationPreload: {enable: async function () {}},
      showNotification: async function () {}
    },
    location: {origin: 'https://warga.example'},
    clients: {claim: async function () {}, matchAll: async function () { return []; }},
    skipWaiting: async function () {},
    addEventListener: function (name, handler) { handlers[name] = handler; }
  };
  const context = vm.createContext({self, URL, Promise, Number, Boolean, String, Array, Map, caches, fetch: async function () {}});
  vm.runInContext(
    serviceWorkerSource + '\nself.__cacheTest = {isStaticAsset, trimMarketplaceImageCache, SDW_IMAGE_CACHE, SDW_IMAGE_CACHE_MAX_ENTRIES, precache};',
    context,
    {filename: serviceWorkerPath}
  );
  return {api: self.__cacheTest, handlers, caches, cacheMap, calls};
}

function request(url) {
  return {
    method: 'GET',
    url,
    headers: {has: function () { return false; }, get: function () { return ''; }}
  };
}

let passed = 0;
async function test(label, callback) {
  await callback();
  passed += 1;
  process.stdout.write('PASS ' + label + '\n');
}

(async function () {
  const instance = runtime();

  await test('PDF.js stays out of the service-worker static cache', async function () {
    assert.equal(instance.api.isStaticAsset(request('https://warga.example/assets/vendor/pdfjs/pdf.min.mjs'), new URL('https://warga.example/assets/vendor/pdfjs/pdf.min.mjs')), false);
    assert.equal(instance.api.isStaticAsset(request('https://warga.example/assets/js/warga.min.js'), new URL('https://warga.example/assets/js/warga.min.js')), true);
  });

  await test('precache keeps one versioned launcher icon without duplicates', async function () {
    assert.deepEqual(Array.from(instance.api.precache), [
      'https://warga.example/offline.html',
      'https://warga.example/assets/pwa/icon-192.png?v=20260913-icon-1',
      'https://warga.example/assets/pwa/notification-badge.png?v=20260913-badge-2'
    ]);
  });

  await test('market image cache trimming keeps the newest protected response bounded', async function () {
    const entries = [];
    for (let index = 0; index < 125; index += 1) {
      entries.push(['https://warga.example/pasar/gambar/' + index + '?v=00000000000000000000', {url: String(index)}]);
    }
    const cache = createCache('smartdesa-warga-market-images-v2', entries);
    const protectedUrl = 'https://warga.example/pasar/gambar/124?v=00000000000000000000';
    await instance.api.trimMarketplaceImageCache(cache, protectedUrl);
    const keys = await cache.keys();
    assert.equal(keys.length, instance.api.SDW_IMAGE_CACHE_MAX_ENTRIES);
    assert.ok(keys.some(function (key) { return key.url === protectedUrl; }));
  });

  await test('activate removes old static and image buckets', async function () {
    const oldStatic = createCache('smartdesa-warga-static-old');
    const oldImages = createCache('smartdesa-warga-market-images-v1');
    const currentImages = createCache(instance.api.SDW_IMAGE_CACHE);
    const unrelated = createCache('unrelated-cache');
    const active = runtime([oldStatic, oldImages, currentImages, unrelated]);
    let completion;
    active.handlers.activate({waitUntil: function (promise) { completion = promise; }});
    await completion;
    assert.deepEqual(active.calls.deleted.sort(), [oldImages.name, oldStatic.name].sort());
    assert.ok(active.cacheMap.has(currentImages.name));
    assert.ok(active.cacheMap.has(unrelated.name));
  });

  process.stdout.write('OK: ' + passed + ' service-worker cache checks passed.\n');
})().catch(function (error) {
  process.stderr.write('FAIL: ' + (error && error.stack ? error.stack : error) + '\n');
  process.exitCode = 1;
});
