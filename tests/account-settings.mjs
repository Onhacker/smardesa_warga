// Run with `node tests/account-settings.mjs` against the local demo app.
// Uses a separate in-memory cookie jar; never changes a real account.
import assert from 'node:assert/strict';

const root = 'http://localhost/smartdesa-warga/';
const cookies = new Map();
let checks = 0;
async function page(path, fields) {
  const response = await fetch(root + path, {
    method: fields ? 'POST' : 'GET',
    headers: {
      Cookie: [...cookies].map(([key, value]) => key + '=' + value).join('; '),
      ...(fields ? {'Content-Type': 'application/x-www-form-urlencoded'} : {})
    },
    body: fields ? new URLSearchParams(fields) : undefined,
    redirect: 'manual'
  });
  for (const cookie of response.headers.getSetCookie()) {
    const raw = cookie.split(';')[0], index = raw.indexOf('=');
    cookies.set(raw.slice(0, index), raw.slice(index + 1));
  }
  return {status: response.status, headers: response.headers, html: await response.text()};
}
function csrf(result) {
  const input = result.html.match(/<input[^>]*name="sdw_csrf_token"[^>]*>/)?.[0];
  assert(input, 'Missing CSRF field');
  return input.match(/value="([^"]+)"/)[1];
}
function redirects(result, label) {
  assert([302, 303].includes(result.status), label + ': HTTP ' + result.status);
  checks++;
}
function contains(result, pattern, label) {
  assert.match(result.html, pattern, label);
  checks++;
}

let result = await page('login');
let token = csrf(result);
redirects(await page('login', {sdw_csrf_token: token, identity: 'warga@demo.local', password: 'demo12345'}), 'Demo login');
result = await page('akun/edit');
contains(result, /Mode demo menyimpan/, 'Safety guard: test only demo sessions');
assert.match(result.headers.get('cache-control'), /no-store/);
token = csrf(result);
const contact = (overrides = {}) => page('akun/edit', {
  sdw_csrf_token: token, email: 'qa.account@demo.local', phone: '0812 9999-8888', current_password: 'demo12345', ...overrides
});
contains(await contact({current_password: 'incorrect'}), /Kata sandi saat ini tidak sesuai/, 'Reject wrong password');
contains(await contact({email: 'not-email'}), /Email belum valid/, 'Reject invalid email');
contains(await contact({phone: '123'}), /Nomor telepon harus/, 'Reject invalid phone');
contains(await contact({email: '', phone: ''}), /Isi minimal email/, 'Require contact');
contains(await contact({email: 'sekdes@demo.local'}), /sudah digunakan akun lain/, 'Reject duplicate contact');
redirects(await contact(), 'Save contact');
result = await page('akun');
contains(result, /qa.account@demo.local/, 'Updated email displayed');
contains(result, /081299998888/, 'Phone normalized');
contains(result, /Data akun berhasil diperbarui/, 'Contact success message');
result = await page('akun/ganti-password');
assert.match(result.headers.get('cache-control'), /no-store/);
token = csrf(result);
const password = (overrides = {}) => page('akun/ganti-password', {
  sdw_csrf_token: token, current_password: 'demo12345', new_password: 'SmartQa!12345', password_confirm: 'SmartQa!12345', ...overrides
});
contains(await password({password_confirm: 'different'}), /Konfirmasi kata sandi belum sama/, 'Reject mismatch');
contains(await password({new_password: 'short', password_confirm: 'short'}), /Kata sandi baru harus/, 'Reject short password');
contains(await password({current_password: 'incorrect'}), /Kata sandi saat ini tidak sesuai/, 'Reject wrong current password');
redirects(await password(), 'Change password');
result = await page('akun');
contains(result, /Kata sandi berhasil diubah/, 'Password success message');
token = csrf(result);
redirects(await page('logout', {sdw_csrf_token: token}), 'Logout');
result = await page('login');
token = csrf(result);
result = await page('login', {sdw_csrf_token: token, identity: 'qa.account@demo.local', password: 'demo12345'});
assert.equal(result.status, 200, 'Old password rejected');
checks++;
redirects(await page('login', {sdw_csrf_token: token, identity: 'qa.account@demo.local', password: 'SmartQa!12345'}), 'Login with updated contact/password');
result = await page('akun/edit');
token = csrf(result);
redirects(await contact({email: 'warga@demo.local', phone: '081234567890', current_password: 'SmartQa!12345'}), 'Restore demo contact');
redirects(await password({current_password: 'SmartQa!12345', new_password: 'demo12345', password_confirm: 'demo12345'}), 'Restore demo password');
result = await page('akun/edit', {email: 'qa.account@demo.local', phone: '081299998888', current_password: 'demo12345'});
assert.equal(result.status, 403, 'Missing CSRF rejected');
checks++;
console.log(`PASS: ${checks} account checks; no-store verified; demo values restored.`);
