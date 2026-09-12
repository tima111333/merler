/**
 * Снимки прототипа в headless-Chromium (Playwright берётся из соседнего проекта).
 * Запуск: node tools/shots.js
 */
const { createRequire } = require('module');
const path = require('path');
const fs = require('fs');

const req = createRequire('D:/vortex-rift-points/package.json');
const { chromium } = req('playwright');

const OUT = path.join(__dirname, '..', 'docs', 'shots');
const URL = process.env.MERLER_URL || 'http://localhost:8932/';
fs.mkdirSync(OUT, { recursive: true });

const shots = [
  { n: 'm-a-f1', q: 'layout=a&font=1', w: 375, h: 812 },
  { n: 'm-b-f1', q: 'layout=b&font=1', w: 375, h: 812 },
  { n: 'm-a-f2', q: 'layout=a&font=2', w: 375, h: 812 },
  { n: 'm-b-f2', q: 'layout=b&font=2', w: 375, h: 812 },
  { n: 'm-nophoto', q: 'layout=a&font=1&photos=off', w: 375, h: 812 },
  { n: 'm-dagestan', q: 'layout=a&font=1', w: 375, h: 812, to: '#sec-dagestanskaya-kuhnya' },
  { n: 'm-sheet', q: 'layout=a&font=1', w: 375, h: 812, sheet: true },
  { n: 'm-search', q: 'layout=a&font=1', w: 375, h: 812, search: 'креветк' },
  { n: 'm-demo', q: 'layout=a&font=1&demo=on', w: 375, h: 812, to: '#sec-pervye-blyuda' },
  { n: 'tablet', q: 'layout=a&font=1', w: 768, h: 1024 },
  { n: 'desktop', q: 'layout=a&font=1', w: 1280, h: 900 }
];

(async () => {
  const browser = await chromium.launch();
  for (const s of shots) {
    const ctx = await browser.newContext({
      viewport: { width: s.w, height: s.h },
      deviceScaleFactor: 2,
      isMobile: s.w < 700,
      hasTouch: s.w < 700
    });
    const page = await ctx.newPage();
    await page.goto(URL + '?panel=off&' + s.q, { waitUntil: 'networkidle' });
    if (s.to) {
      await page.evaluate((sel) => {
        document.querySelector(sel).scrollIntoView();
      }, s.to);
      await page.waitForTimeout(500);
    }
    if (s.sheet) {
      await page.evaluate(() => document.querySelectorAll('.card')[1].click());
      await page.waitForTimeout(500);
    }
    if (s.search) {
      await page.click('.js-search');
      await page.fill('.searchbar input', s.search);
      await page.waitForTimeout(400);
    }
    await page.screenshot({ path: path.join(OUT, s.n + '.png') });
    await ctx.close();
    console.log(s.n);
  }
  await browser.close();
})();
