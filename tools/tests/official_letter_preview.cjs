const { chromium } = require('playwright');
const fs = require('node:fs');
const http = require('node:http');
const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const css = fs.readFileSync(path.join(root, 'assets/css/warga.min.css'));
const js = fs.readFileSync(path.join(root, 'assets/js/warga.min.js'));
const output = process.env.SDW_TEST_SCREENSHOTS || '/tmp/sdw-official-letter-preview';
fs.mkdirSync(output, { recursive: true });

const pixel = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
const paragraphs = Array.from({ length: 58 }, (_, index) =>
  `<p>Paragraf ${index + 1}. Isi surat panjang untuk menguji halaman A4 berikutnya tanpa memotong blok tanda tangan.</p>`
).join('');
const letter = `<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; img-src data:; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'"><title>Surat Resmi</title><style>*{box-sizing:border-box}html,body{margin:0;background:#e5e7eb;font:12pt/1.45 Arial,sans-serif}.page{width:210mm;min-height:297mm;margin:0 auto;padding:16mm 18mm;overflow:visible;background:#fff}.isi{orphans:3;widows:3}.isi p{margin:0 0 8px}.signature-block{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:28px;text-align:center;break-inside:avoid-page;page-break-inside:avoid}.signature-block img{display:block;width:31mm;height:31mm;margin:auto;object-fit:contain}.signer-space img{width:58mm;height:24mm}@page{size:A4 portrait;margin:16mm 18mm}@media print{html,body{background:#fff}.page{width:auto;min-height:0;margin:0;padding:0}}</style></head><body><main class="page"><section class="isi">${paragraphs}</section><section class="signature-block"><div><img src="${pixel}" alt="QR surat"></div><div><div>Hesatom, 10 September 2026</div><div>Kepala Kampung</div><div class="signer-space"><img src="${pixel}" alt="Tanda tangan"></div><strong>KEPALA KAMPUNG</strong></div></section></main></body></html>`;

const shell = `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/warga.min.css"><style>body{margin:0}#page{padding:20px}.open-letter{padding:12px}</style></head><body><div id="page"><button class="open-letter" data-warga-letter-open data-html-url="/letter" data-html-name="surat-resmi.html">Lihat Surat</button></div><div id="warga-letter-modal" class="warga-letter-modal" hidden aria-hidden="true" role="dialog"><button class="warga-letter-modal-backdrop" data-warga-letter-close aria-label="Tutup"></button><section class="warga-letter-modal-panel"><header class="warga-letter-modal-header"><div><span>Surat Resmi</span><h2>Surat Keterangan</h2></div><button class="warga-letter-icon-button" data-warga-letter-close>Tutup</button></header><div class="warga-letter-modal-frame-wrap"><div class="warga-letter-modal-status" data-warga-letter-status>Memuat surat...</div><div class="warga-letter-modal-scroll" data-warga-letter-viewport><div class="warga-letter-modal-canvas" data-warga-letter-canvas hidden><iframe class="warga-letter-modal-frame" data-warga-letter-frame sandbox="allow-same-origin" hidden></iframe></div></div><div class="warga-letter-modal-gesture-layer" data-warga-letter-gesture-layer hidden></div><div class="warga-letter-modal-zoom" data-warga-letter-zoom hidden><button data-warga-letter-zoom-out>-</button><output data-warga-letter-zoom-level></output><button data-warga-letter-zoom-reset>Fit</button><button data-warga-letter-zoom-in>+</button></div><p data-warga-letter-zoom-hint hidden></p></div><footer class="warga-letter-modal-footer"><button data-warga-letter-close>Tutup</button><button data-warga-letter-download disabled>Unduh Surat</button></footer></section></div><script>window.SDW={serviceWorkerUrl:'/missing-service-worker.js',serviceWorkerScope:'/'};</script><script src="/warga.min.js"></script></body></html>`;

const server = http.createServer((request, response) => {
  if (request.url === '/warga.min.css') {
    response.writeHead(200, { 'Content-Type': 'text/css' });
    return response.end(css);
  }
  if (request.url === '/warga.min.js') {
    response.writeHead(200, { 'Content-Type': 'text/javascript' });
    return response.end(js);
  }
  if (request.url === '/letter') {
    response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' });
    return response.end(letter);
  }
  response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
  response.end(shell);
});

(async () => {
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const address = server.address();
  const browser = await chromium.launch({
    headless: true,
    ...(process.env.SDW_CHROME_PATH ? { executablePath: process.env.SDW_CHROME_PATH } : {})
  });
  try {
    for (const width of [390, 1200]) {
      const page = await browser.newPage({ viewport: { width, height: 850 } });
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto(`http://127.0.0.1:${address.port}/`);
      await page.locator('[data-warga-letter-open]').click();
      await page.locator('[data-warga-letter-frame]:not([hidden])').waitFor();
      await page.waitForFunction(() => {
        const frame = document.querySelector('[data-warga-letter-frame]');
        return frame && parseFloat(frame.style.height || '0') > 1123;
      });
      const result = await page.evaluate(() => {
        const frame = document.querySelector('[data-warga-letter-frame]');
        const canvas = document.querySelector('[data-warga-letter-canvas]');
        const documentNode = frame.contentDocument;
        const signature = documentNode.querySelector('.signature-block');
        return {
          frameHeight: parseFloat(frame.style.height),
          canvasHeight: parseFloat(canvas.style.height),
          documentHeight: documentNode.documentElement.scrollHeight,
          images: documentNode.images.length,
          signatureBottom: signature.getBoundingClientRect().bottom,
          signatureHeight: signature.getBoundingClientRect().height
        };
      });
      if (result.frameHeight <= 1123 || result.canvasHeight <= 1123 || result.images !== 2) {
        throw new Error(`Incomplete long-letter preview at ${width}px: ${JSON.stringify(result)}`);
      }
      if (result.signatureHeight < 100 || result.signatureBottom > result.frameHeight + 1) {
        throw new Error(`Signature block is clipped at ${width}px: ${JSON.stringify(result)}`);
      }
      const downloadEvent = page.waitForEvent('download');
      await page.locator('[data-warga-letter-download]').click();
      const download = await downloadEvent;
      if (download.suggestedFilename() !== 'surat-resmi.html') throw new Error('Unexpected HTML download name.');
      await page.screenshot({ path: path.join(output, `preview-${width}.png`) });
      await page.locator('[data-warga-letter-viewport]').evaluate(element => {
        element.scrollTop = element.scrollHeight;
      });
      await page.screenshot({ path: path.join(output, `preview-bottom-${width}.png`) });
      if (errors.length) throw new Error(errors.join('\n'));
      console.log(`PASS official HTML preview at ${width}px (${Math.round(result.frameHeight)}px document)`);
      await page.close();
    }
  } finally {
    await browser.close();
    await new Promise(resolve => server.close(resolve));
  }
})().catch(error => {
  console.error(error);
  server.close();
  process.exitCode = 1;
});
