const http = require('http');
const fs = require('fs');
const path = require('path');

const base = (process.argv[2] || 'http://memomind-vn.local').replace(/\/$/, '');
const routeMap = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'routes.json'), 'utf8'));
const routes = [...new Set(Object.keys(routeMap).filter((route) => !route.startsWith('/fr/')))];
routes.push('/support/', '/support/chinh-sach-bao-hanh/', '/support/van-chuyen-giao-hang/', '/support/phuong-thuc-thanh-toan/');

function request(route, redirects = 0) {
  return new Promise((resolve, reject) => {
    const req = http.get(base + route, { headers: { 'User-Agent': 'MemoMind-Handover-Audit/1.0' } }, (res) => {
      let body = '';
      res.setEncoding('utf8');
      res.on('data', (chunk) => { body += chunk; });
      res.on('end', async () => {
        if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location && redirects < 5) {
          const target = new URL(res.headers.location, base).pathname;
          return resolve({ ...(await request(target, redirects + 1)), initialStatus: res.statusCode, redirectTarget: target });
        }
        resolve({ status: res.statusCode, headers: res.headers, body, finalRoute: route });
      });
    });
    req.setTimeout(20000, () => req.destroy(new Error('timeout')));
    req.on('error', reject);
  });
}

function attr(tag, name) {
  const match = tag.match(new RegExp(`\\b${name}=["']([^"']*)["']`, 'i'));
  return match ? match[1] : '';
}

(async () => {
  const failures = [];
  const summary = { tested: routes.length, statusErrors: 0, metadataErrors: 0, schemaErrors: 0, semanticErrors: 0, oldDomainLeaks: 0 };
  for (const route of routes) {
    let result;
    try { result = await request(route); } catch (error) { failures.push([route, 'REQUEST', error.message]); summary.statusErrors++; continue; }
    const html = result.body;
    if (result.status !== 200) { failures.push([route, 'HTTP', result.status]); summary.statusErrors++; continue; }
    const checks = [
      ['title', /<title\b[^>]*>[\s\S]*?\S[\s\S]*?<\/title>/i],
      ['description', /<meta\b(?=[^>]*name=["']description["'])[^>]*>/i],
      ['robots', /<meta\b(?=[^>]*name=["']robots["'])[^>]*>/i],
      ['canonical', /<link\b(?=[^>]*rel=["']canonical["'])[^>]*>/i],
      ['og:title', /<meta\b(?=[^>]*property=["']og:title["'])[^>]*>/i],
      ['og:description', /<meta\b(?=[^>]*property=["']og:description["'])[^>]*>/i],
      ['og:url', /<meta\b(?=[^>]*property=["']og:url["'])[^>]*>/i],
      ['og:image', /<meta\b(?=[^>]*property=["']og:image["'])[^>]*>/i],
      ['favicon', /<link\b(?=[^>]*rel=["']icon["'])[^>]*>/i],
      ['apple-touch-icon', /<link\b(?=[^>]*rel=["']apple-touch-icon["'])[^>]*>/i],
      ['manifest', /<link\b(?=[^>]*rel=["']manifest["'])[^>]*>/i],
    ];
    for (const [name, pattern] of checks) if (!pattern.test(html)) { failures.push([route, 'META', name]); summary.metadataErrors++; }
    const canonicalTag = html.match(/<link\b(?=[^>]*rel=["']canonical["'])[^>]*>/i);
    const expected = base + result.finalRoute;
    if (canonicalTag && attr(canonicalTag[0], 'href') !== expected) { failures.push([route, 'CANONICAL', attr(canonicalTag[0], 'href'), expected]); summary.metadataErrors++; }
    const h1 = (html.match(/<h1\b/gi) || []).length;
    if (h1 !== 1) { failures.push([route, 'H1', h1]); summary.semanticErrors++; }
    for (const match of html.matchAll(/<script\b[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)) {
      try { JSON.parse(match[1]); } catch (error) { failures.push([route, 'JSON-LD', error.message]); summary.schemaErrors++; }
    }
    if (/https?:\\?\/\\?\/(?:www\.)?memo-mind\.com/i.test(html)) { failures.push([route, 'OLD_DOMAIN']); summary.oldDomainLeaks++; }
  }
  console.log(JSON.stringify({ summary, failures }, null, 2));
  if (failures.length) process.exitCode = 1;
})();
