/** Диагностика: что вылезает за правый край на телефоне. */
const { createRequire } = require('module');
const req = createRequire('D:/vortex-rift-points/package.json');
const { chromium } = req('playwright');

(async () => {
  const b = await chromium.launch();
  const ctx = await b.newContext({ viewport: { width: 375, height: 812 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });
  const p = await ctx.newPage();
  const errors = [];
  p.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
  p.on('pageerror', (e) => errors.push('pageerror: ' + e.message));
  await p.goto('http://localhost:8932/', { waitUntil: 'networkidle' });

  const r = await p.evaluate(() => {
    const w = document.documentElement.clientWidth;
    const out = [];
    document.querySelectorAll('*').forEach((el) => {
      const b = el.getBoundingClientRect();
      if (b.right > w + 1 || b.left < -1) {
        out.push({
          tag: el.tagName.toLowerCase(),
          cls: el.className && el.className.toString().slice(0, 40),
          left: Math.round(b.left), right: Math.round(b.right)
        });
      }
    });
    return {
      clientWidth: w,
      scrollWidth: document.documentElement.scrollWidth,
      bodyScroll: document.body.scrollWidth,
      offenders: out.slice(0, 12),
      heroH: Math.round(document.querySelector('.hero').getBoundingClientRect().height),
      navTop: Math.round(document.querySelector('.navbar').getBoundingClientRect().top)
    };
  });
  console.log(JSON.stringify(r, null, 1));

  await p.evaluate(() => window.scrollTo(0, 2000));
  await p.waitForTimeout(400);
  const sticky = await p.evaluate(() => Math.round(document.querySelector('.navbar').getBoundingClientRect().top));
  console.log('navbar.top после прокрутки (должно быть 0):', sticky);
  console.log('ошибки консоли:', errors.length ? errors : 'нет');
  await b.close();
})();
