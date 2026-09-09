'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const repositoryRoot = path.resolve(__dirname, '..', '..');
const serviceWorkerPath = path.join(repositoryRoot, 'service-worker.js');
const serviceWorkerSource = fs.readFileSync(serviceWorkerPath, 'utf8');

function runtime(scope, windows) {
  const handlers = {};
  const calls = {
    matchOptions: [],
    opened: [],
    shown: []
  };
  const scopeUrl = new URL(scope);
  const clientList = Array.isArray(windows) ? windows : [];
  const self = {
    registration: {
      scope,
      showNotification: async function (title, options) {
        calls.shown.push({title, options});
      }
    },
    location: {origin: scopeUrl.origin},
    clients: {
      matchAll: async function (options) {
        calls.matchOptions.push(options);
        return clientList;
      },
      openWindow: async function (url) {
        calls.opened.push(url);
        return {url};
      }
    },
    addEventListener: function (name, handler) {
      handlers[name] = handler;
    }
  };
  const context = vm.createContext({
    self,
    URL,
    Promise,
    Number,
    Boolean,
    caches: {},
    fetch: function () {}
  });

  vm.runInContext(
    serviceWorkerSource + '\nself.__notificationTest = { notificationUrl, openNotificationTarget };',
    context,
    {filename: serviceWorkerPath}
  );

  return {api: self.__notificationTest, calls, handlers};
}

let passed = 0;

async function test(label, callback) {
  await callback();
  passed += 1;
  process.stdout.write('PASS ' + label + '\n');
}

(async function () {
  await test('relative notification routes resolve inside root and subpath scopes', async function () {
    const root = runtime('https://warga.example/', []);
    assert.equal(
      root.api.notificationUrl('notifikasi/buka/11111111-1111-4111-8111-111111111111').href,
      'https://warga.example/notifikasi/buka/11111111-1111-4111-8111-111111111111'
    );

    const subpath = runtime('https://warga.example/smartdesa-warga/', []);
    assert.equal(
      subpath.api.notificationUrl('notifikasi/buka/22222222-2222-4222-8222-222222222222').href,
      'https://warga.example/smartdesa-warga/notifikasi/buka/22222222-2222-4222-8222-222222222222'
    );
  });

  await test('malformed, cross-origin, and out-of-scope targets use notification fallback', async function () {
    const instance = runtime('https://warga.example/smartdesa-warga/', []);
    const fallback = 'https://warga.example/smartdesa-warga/notifikasi';
    assert.equal(instance.api.notificationUrl('http://[::1').href, fallback);
    assert.equal(instance.api.notificationUrl('https://attacker.example/notifikasi').href, fallback);
    assert.equal(instance.api.notificationUrl('/other-app/notifikasi').href, fallback);
  });

  await test('a rejected exact-page focus falls back to opening the target', async function () {
    const target = 'https://warga.example/notifikasi/buka/33333333-3333-4333-8333-333333333333';
    const exactWindow = {
      url: target,
      focused: true,
      visibilityState: 'visible',
      focus: async function () { throw new Error('stale client'); },
      navigate: async function () { return null; }
    };
    const instance = runtime('https://warga.example/', [exactWindow]);

    await instance.api.openNotificationTarget(instance.api.notificationUrl(target));

    assert.deepEqual(instance.calls.opened, [target]);
    assert.equal(instance.calls.matchOptions.length, 1);
    assert.equal(instance.calls.matchOptions[0].type, 'window');
    assert.equal(instance.calls.matchOptions[0].includeUncontrolled, true);
  });

  await test('an existing PWA window navigates to and focuses the notification target', async function () {
    const target = 'https://warga.example/notifikasi/buka/44444444-4444-4444-8444-444444444444';
    const calls = {navigated: [], focused: 0};
    const navigatedWindow = {
      url: target,
      focus: async function () {
        calls.focused += 1;
        return this;
      }
    };
    const existingWindow = {
      url: 'https://warga.example/dashboard',
      focused: false,
      visibilityState: 'hidden',
      navigate: async function (url) {
        calls.navigated.push(url);
        return navigatedWindow;
      }
    };
    const instance = runtime('https://warga.example/', [existingWindow]);

    await instance.api.openNotificationTarget(instance.api.notificationUrl(target));

    assert.deepEqual(calls.navigated, [target]);
    assert.equal(calls.focused, 1);
    assert.deepEqual(instance.calls.opened, []);
  });

  await test('the exact notification URL opens when no app window exists', async function () {
    const target = 'https://warga.example/smartdesa-warga/notifikasi/buka/55555555-5555-4555-8555-555555555555';
    const unrelatedWindow = {
      url: 'https://other.example/dashboard',
      focus: async function () { throw new Error('must not be focused'); }
    };
    const instance = runtime('https://warga.example/smartdesa-warga/', [unrelatedWindow]);

    await instance.api.openNotificationTarget(instance.api.notificationUrl(target));

    assert.deepEqual(instance.calls.opened, [target]);
  });

  process.stdout.write('OK: ' + passed + ' service-worker notification checks passed.\n');
})().catch(function (error) {
  process.stderr.write('FAIL: ' + (error && error.stack ? error.stack : error) + '\n');
  process.exitCode = 1;
});
