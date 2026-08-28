const { chromium } = require('C:/Users/hnguy/AppData/Local/npm-cache/_npx/e41f203b7505f1fb/node_modules/playwright');

const base = (process.argv[2] || 'http://memomind-vn.local').replace(/\/$/, '');
const routes = [
  '/', '/collections/all/', '/products/memomind-one-standard/',
  '/products/memomind-one-custom/', '/pages/memomind-one/',
  '/blogs/tech-hub/how-do-ai-glasses-work/', '/contact/',
  '/policies/privacy-policy/', '/support/',
  '/url-khong-ton-tai-audit/',
];
const viewports = [{ width: 360, height: 800, name: 'mobile' }, { width: 1440, height: 900, name: 'desktop' }];

(async () => {
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const failures = [];
  for (const viewport of viewports) {
    const page = await browser.newPage({ viewport });
    await page.route('**/*', async (route) => {
      const url = new URL(route.request().url());
      if (url.hostname === 'memomind-vn.local' || url.protocol === 'data:') return route.continue();
      return route.abort('blockedbyclient');
    });
    for (const route of routes) {
      const consoleErrors = [];
      const pageErrors = [];
      const failedRequests = [];
      const onConsole = (message) => {
        if (message.type() === 'error' && !/Failed to load resource|Error loading script/.test(message.text())) consoleErrors.push(message.text());
      };
      const onPageError = (error) => pageErrors.push(error.stack || error.message);
      const onFailed = (request) => failedRequests.push(`${request.url()} ${request.failure()?.errorText || ''}`);
      page.on('console', onConsole); page.on('pageerror', onPageError); page.on('requestfailed', onFailed);
      const response = await page.goto(base + route, { waitUntil: 'commit', timeout: 15000 });
      await page.waitForFunction(() => document.querySelector('main') || document.readyState !== 'loading', null, { timeout: 7000 }).catch(() => {});
      await page.waitForTimeout(500);
      const state = await page.evaluate(() => ({
        width: document.documentElement.scrollWidth,
        clientWidth: document.documentElement.clientWidth,
        title: document.title,
        h1: document.querySelectorAll('h1').length,
        main: Boolean(document.querySelector('main')),
        visibleLinks: [...document.querySelectorAll('a[href]')].filter((a) => {
          const rect = a.getBoundingClientRect(); return rect.width > 0 && rect.height > 0;
        }).length,
      }));
      const expectedStatus = route.includes('khong-ton-tai') ? 404 : 200;
      if (response?.status() !== expectedStatus) failures.push([viewport.name, route, 'HTTP', response?.status(), expectedStatus]);
      if (state.width > state.clientWidth + 2) failures.push([viewport.name, route, 'OVERFLOW', state.width, state.clientWidth]);
      if (!state.title || !state.main || (expectedStatus === 200 && state.h1 !== 1)) failures.push([viewport.name, route, 'DOM', state]);
      if (!state.visibleLinks) failures.push([viewport.name, route, 'NO_VISIBLE_LINKS']);
      const localFailed = failedRequests.filter((item) => item.startsWith(base));
      if (localFailed.length) failures.push([viewport.name, route, 'FAILED_LOCAL_REQUESTS', localFailed.slice(0, 10)]);
      if (pageErrors.length) failures.push([viewport.name, route, 'PAGEERROR', pageErrors.slice(0, 10)]);
      if (consoleErrors.length) failures.push([viewport.name, route, 'CONSOLE', consoleErrors.slice(0, 10)]);
      page.off('console', onConsole); page.off('pageerror', onPageError); page.off('requestfailed', onFailed);
    }
    await page.close();
  }
  await browser.close();
  console.log(JSON.stringify({ tested: routes.length * viewports.length, failures }, null, 2));
  if (failures.length) process.exitCode = 1;
})();
