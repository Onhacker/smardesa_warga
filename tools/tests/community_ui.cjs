const { chromium } = require('playwright');
const fs = require('node:fs');
const base = process.env.SDW_TEST_URL || 'http://127.0.0.1:8097/';
if (!['127.0.0.1', 'localhost'].includes(new URL(base).hostname)) throw new Error('Use an isolated local demo server.');
const output = process.env.SDW_TEST_SCREENSHOTS || '/tmp/sdw-community-qa';
fs.mkdirSync(output, { recursive: true });
(async () => {
  const browser = await chromium.launch({
    headless: true,
    ...(process.env.SDW_CHROME_PATH ? { executablePath: process.env.SDW_CHROME_PATH } : {})
  });
  try {
    for (const role of ['warga', 'sekdes', 'kades']) {
      const context = await browser.newContext();
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message + ' @ ' + page.url()));
      await page.goto(base + 'login');
      await page.locator('input[name="identity"]').fill(role);
      await page.locator('input[name="password"]').fill('demo12345');
      await page.locator('button[type="submit"]').click();
      await page.waitForURL(role === 'warga' ? '**/dashboard' : '**/petugas');
      for (const width of [360, 390, 768, 1440]) {
        await page.setViewportSize({ width, height: 900 });
        for (const route of [role === 'warga' ? 'dashboard' : 'petugas', 'pengumuman', role === 'warga' ? 'surat' : 'petugas?status=submitted', 'pengaduan', 'akun', 'kontak']) {
          const response = await page.goto(base + route);
          if (response.status() !== 200) throw new Error(role + ' ' + route + ': HTTP ' + response.status());
          await page.waitForFunction(() => {
            const preloader = document.getElementById('preloader');
            return !preloader || getComputedStyle(preloader).opacity === '0' || getComputedStyle(preloader).display === 'none';
          });
          const body = await page.locator('body').innerText();
          if (/A PHP Error|Fatal error|Undefined (?:variable|array key)|Severity: Warning/.test(body)) throw new Error('PHP error on ' + route);
          const labels = await page.locator('#footer-bar > a > span').allTextContents();
          if (labels.join('|') !== 'Beranda|Pengumuman|Surat|Pengaduan|Akun') throw new Error('Wrong footer: ' + labels);
          const overflow = await page.evaluate(() => ({
            width: document.documentElement.clientWidth,
            scroll: document.documentElement.scrollWidth,
            labels: [...document.querySelectorAll('#footer-bar > a')].filter(el => el.scrollWidth > el.clientWidth + 1).map(el => el.textContent)
          }));
          if (overflow.scroll > overflow.width + 1 || overflow.labels.length) throw new Error(role + ' ' + route + ' ' + width + ': ' + JSON.stringify(overflow));
          if ([360, 1440].includes(width)) await page.screenshot({ path: `${output}/${role}-${route.replace(/[^a-z]/g,'-')}-${width}.png`, fullPage: true });
        }
        console.log(`PASS ${role}: 6 pages at ${width}px`);
      }
      if (errors.length) throw new Error(errors.join('\n'));
      await context.close();
    }
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
