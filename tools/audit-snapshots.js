const fs = require('fs');
const path = require('path');

const viewDir = path.join(__dirname, '..', 'views');
const files = fs.readdirSync(viewDir).filter((file) => /\.html?$/.test(file));
const report = {
  files: files.length,
  noTitle: [], noDescription: [], noCanonical: [], externalCanonical: [],
  noH1: [], multipleH1: [], noRobots: [], noOgTitle: [], noOgDescription: [],
  noOgUrl: [], noOgImage: [], invalidJsonLd: [], firstImageLazy: [],
  images: 0, imagesWithoutAlt: 0, imagesWithoutDimensions: 0,
};

const has = (html, pattern) => pattern.test(html);

for (const file of files) {
  const html = fs.readFileSync(path.join(viewDir, file), 'utf8');
  const h1Count = (html.match(/<h1\b/gi) || []).length;
  const images = html.match(/<img\b[^>]*>/gi) || [];

  if (!has(html, /<title\b[^>]*>[\s\S]*?\S[\s\S]*?<\/title>/i)) report.noTitle.push(file);
  if (!has(html, /<meta\b(?=[^>]*\bname=["']description["'])[^>]*>/i)) report.noDescription.push(file);
  if (!has(html, /<link\b(?=[^>]*\brel=["']canonical["'])[^>]*>/i)) report.noCanonical.push(file);
  if (has(html, /<link\b(?=[^>]*\brel=["']canonical["'])(?=[^>]*memo-mind\.com)[^>]*>/i)) report.externalCanonical.push(file);
  if (!h1Count) report.noH1.push(file);
  if (h1Count > 1) report.multipleH1.push([file, h1Count]);
  if (!has(html, /<meta\b(?=[^>]*\bname=["']robots["'])[^>]*>/i)) report.noRobots.push(file);
  if (!has(html, /<meta\b(?=[^>]*\bproperty=["']og:title["'])[^>]*>/i)) report.noOgTitle.push(file);
  if (!has(html, /<meta\b(?=[^>]*\bproperty=["']og:description["'])[^>]*>/i)) report.noOgDescription.push(file);
  if (!has(html, /<meta\b(?=[^>]*\bproperty=["']og:url["'])[^>]*>/i)) report.noOgUrl.push(file);
  if (!has(html, /<meta\b(?=[^>]*\bproperty=["']og:image["'])[^>]*>/i)) report.noOgImage.push(file);

  for (const match of html.matchAll(/<script\b[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)) {
    try { JSON.parse(match[1]); } catch (error) { report.invalidJsonLd.push([file, error.message]); }
  }

  report.images += images.length;
  report.imagesWithoutAlt += images.filter((image) => !/\balt=["'][^"']*["']/i.test(image)).length;
  report.imagesWithoutDimensions += images.filter((image) => !(/\bwidth=["']?\d+/i.test(image) && /\bheight=["']?\d+/i.test(image))).length;
  if (images[0] && /\bloading=["']lazy["']/i.test(images[0])) report.firstImageLazy.push(file);
}

console.log(JSON.stringify(report, null, 2));
