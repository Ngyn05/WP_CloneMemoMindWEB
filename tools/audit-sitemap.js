const http = require('http');

const base = (process.argv[2] || 'http://memomind-vn.local').replace(/\/$/, '');
const sitemap = process.argv[3] || `${base}/sitemap_index.xml`;

function get(url, redirects = 0) {
  return new Promise((resolve, reject) => {
    const req = http.get(url, { headers: { 'User-Agent': 'MemoMind-Sitemap-Audit/1.0' } }, (res) => {
      let body = '';
      res.setEncoding('utf8');
      res.on('data', (chunk) => { body += chunk; });
      res.on('end', () => {
        if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location && redirects < 5) {
          return resolve(get(new URL(res.headers.location, url).href, redirects + 1));
        }
        resolve({ status: res.statusCode, headers: res.headers, body, url });
      });
    });
    req.setTimeout(20000, () => req.destroy(new Error('timeout')));
    req.on('error', reject);
  });
}

const locations = (xml) => [...xml.matchAll(/<loc>(.*?)<\/loc>/gi)].map((match) => match[1].trim().replaceAll('&amp;', '&'));
const attr = (tag, name) => tag.match(new RegExp(`\\b${name}=["']([^"']*)["']`, 'i'))?.[1] || '';

(async () => {
  const failures = [];
  const index = await get(sitemap);
  if (index.status !== 200) throw new Error(`Sitemap index HTTP ${index.status}`);
  const children = locations(index.body);
  const urls = [];
  for (const child of children) {
    const response = await get(child);
    if (response.status !== 200) { failures.push([child, 'SITEMAP_HTTP', response.status]); continue; }
    urls.push(...locations(response.body));
  }
  for (const url of [...new Set(urls)]) {
    const response = await get(url);
    if (response.status !== 200) { failures.push([url, 'URL_HTTP', response.status]); continue; }
    const robots = response.body.match(/<meta\b(?=[^>]*name=["']robots["'])[^>]*>/i)?.[0] || '';
    if (/noindex/i.test(attr(robots, 'content'))) failures.push([url, 'NOINDEX']);
    const canonical = response.body.match(/<link\b(?=[^>]*rel=["']canonical["'])[^>]*>/i)?.[0] || '';
    if (!canonical) failures.push([url, 'NO_CANONICAL']);
    else if (attr(canonical, 'href').replace(/\/$/, '') !== url.replace(/\/$/, '')) failures.push([url, 'CANONICAL_MISMATCH', attr(canonical, 'href')]);
  }
  console.log(JSON.stringify({ sitemap, childSitemaps: children.length, urls: [...new Set(urls)].length, failures }, null, 2));
  if (failures.length) process.exitCode = 1;
})().catch((error) => { console.error(error); process.exitCode = 1; });
