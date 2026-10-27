// Captures the screens for the client proposal: full-page desktop shots sliced on section
// boundaries, and phone shots. Each demo role signs in in its own browser context.
// Usage: npm run proposal   (env: BASE_URL, CHROME_PATH, ORDER_ID, ACCOUNT_ID)
// Needs the dev server running (php artisan serve --port=8108) on the DemoSeeder data.
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8108';
const OUT = path.resolve('dist/proposal/shots');
const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PASSWORD = process.env.DEMO_PASSWORD ?? 'password';
const ACCOUNTS = { admin: 'admin@peterco.test', sales: 'sales@peterco.test', credit: 'credit@peterco.test', hr: 'hr@peterco.test' };

// Pick demo records from the database: a recent order request with delivery day, and an open
// pharmacy account application (with its placeholder documents).
const ids = execFileSync('php', ['artisan', 'tinker', '--execute', `
  $o = App\\Models\\Lead::where('type', 'order')->where('status', 'new')->whereNotNull('delivery_date')->latest()->first() ?? App\\Models\\Lead::where('type', 'order')->latest()->first();
  $a = App\\Models\\Lead::where('type', 'account')->whereIn('status', ['new', 'reviewing'])->where('details->trade_type', 'pharmacy')->first() ?? App\\Models\\Lead::where('type', 'account')->whereIn('status', ['new', 'reviewing'])->first();
  echo $o->id.' '.$a->id;`], { encoding: 'utf8' }).trim().split(/\s+/);
const ORDER_ID = process.env.ORDER_ID ?? ids[0];
const ACCOUNT_ID = process.env.ACCOUNT_ID ?? ids[1];

fs.rmSync(OUT, { recursive: true, force: true });
fs.mkdirSync(OUT, { recursive: true });

// [name, url, account]
const SCREENS = [
  ['home', '/'], ['brands', '/brands'], ['division', '/brands/wines-spirits'], ['catalogue', '/catalogue'], ['order', '/order', 'guest-list'],
  ['trade-account', '/trade-account'], ['principals', '/principals'], ['careers', '/careers'], ['about', '/about'], ['contact', '/contact'],
  ['login', '/login'],
  ['sales-dashboard', '/admin', 'sales'],
  ['sales-inbox', '/admin/leads?type=order', 'sales'],
  ['sales-order', `/admin/leads/${ORDER_ID}`, 'sales'],
  ['credit-board', '/admin/credit', 'credit'],
  ['credit-application', `/admin/leads/${ACCOUNT_ID}`, 'credit'],
  ['sales-application', `/admin/leads/${ACCOUNT_ID}`, 'sales'],
  ['credit-accounts', '/admin/accounts', 'credit'],
  ['sales-products', '/admin/products', 'sales'],
  ['hr-dashboard', '/admin', 'hr'],
  ['hr-vacancies', '/admin/vacancies', 'hr'],
  ['admin-staff', '/admin/users', 'admin'],
];
// Long lists are cut after this many slices: the first screens show the idea.
const MAX_SLICES = { catalogue: 2, 'sales-inbox': 1, 'credit-accounts': 1, 'sales-products': 1 };
const MOBILE = [
  ['m-home', '/'], ['m-catalogue', '/catalogue', 'guest-list'], ['m-order', '/order', 'guest-list'],
  ['m-trade-account', '/trade-account'], ['m-sales-dashboard', '/admin', 'sales'], ['m-sales-order', `/admin/leads/${ORDER_ID}`, 'sales'],
];

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', protocolTimeout: 90000, args: ['--hide-scrollbars', '--no-sandbox'] });

/** One isolated browser context per account, signed in once. "guest-list" is a visitor with three lines on the order list. */
const pages = {};
async function pageFor(account) {
  const key = account ?? 'guest';
  if (pages[key]) return pages[key];
  const ctx = await browser.createBrowserContext();
  const page = await ctx.newPage();
  await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }, { name: 'prefers-color-scheme', value: 'light' }]);
  await page.setViewport({ width: 1440, height: 900 });
  if (key === 'guest-list') {
    for (const [i, cases] of [[0, 10], [2, 4], [5, 6]]) {
      await page.goto(BASE + '/catalogue', { waitUntil: 'networkidle0' });
      await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.evaluate((i, cases) => {
        const f = document.querySelectorAll('form[action$="/order/list"]')[i];
        f.querySelector('input[name=cases]').value = cases;
        f.requestSubmit();
      }, i, cases)]);
    }
  } else if (account) {
    await page.goto(BASE + '/login', { waitUntil: 'networkidle0' });
    await page.type('input[name=email]', ACCOUNTS[account]);
    await page.type('input[name=password]', PASSWORD);
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.$eval('input[name=password]', (i) => i.form.requestSubmit())]);
    if (new URL(page.url()).pathname === '/login') throw new Error(`Sign-in failed for ${account}`);
  }
  return (pages[key] = page);
}

async function settle(page) {
  await page.evaluate(async () => {
    document.querySelectorAll('[data-reveal]').forEach((e) => e.classList.add('is-visible'));
    document.querySelectorAll('img[loading="lazy"]').forEach((i) => { i.loading = 'eager'; });
    for (let y = 0; y < document.body.scrollHeight; y += 700) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 40)); }
    window.scrollTo(0, 0);
    await Promise.race([
      Promise.all([...document.images].filter((i) => !i.complete).map((i) => new Promise((r) => { i.onload = i.onerror = r; }))),
      new Promise((r) => setTimeout(r, 3000)),
    ]);
  });
  await new Promise((r) => setTimeout(r, 400));
}

/**
 * Measure natural content blocks (header, each section, footer). Blocks taller than the
 * page budget are replaced by their children, recursively, so slices follow real section
 * boundaries instead of cutting wherever the pixel budget runs out.
 */
async function measureBlocks(page, maxH) {
  return page.evaluate((maxH) => {
    const rect = (el) => { const r = el.getBoundingClientRect(); return { top: Math.round(r.top + window.scrollY), bottom: Math.round(r.bottom + window.scrollY) }; };
    const visible = (el) => { const cs = getComputedStyle(el); const r = el.getBoundingClientRect(); return cs.display !== 'none' && cs.visibility !== 'hidden' && r.height > 24 && cs.position !== 'fixed'; };
    const skip = (el) => el.matches('script, style, template, [x-cloak], [aria-hidden="true"]:not(section)');
    const label = (el) => {
      const hs = [...el.querySelectorAll('h1, h2, h3')].filter((h) => visible(h) && h.closest('nav, footer, [aria-hidden="true"]') === null);
      const seen = new Set();
      return hs.map((h) => h.textContent.replace(/\s+/g, ' ').trim()).filter((t) => t && !seen.has(t.toLowerCase()) && seen.add(t.toLowerCase())).slice(0, 4);
    };
    const explode = (el) => {
      const r = { ...rect(el), labels: label(el) };
      if (r.bottom - r.top <= maxH) return [r];
      const kids = [...el.children].filter((k) => !skip(k) && visible(k));
      if (kids.length === 0) return [{ top: r.top, bottom: r.bottom, labels: r.labels }];
      if (kids.length === 1) return explode(kids[0]);
      return kids.flatMap(explode);
    };
    // Back office: the content column is <main id="content"> beside a fixed sidebar.
    const adminMain = document.querySelector('body.admin main#content');
    const roots = adminMain
      ? [...adminMain.children].map((el) => ({ el, trailing: false }))
      : [...document.body.children].filter((el) => ['HEADER', 'MAIN', 'FOOTER', 'DIV', 'SECTION'].includes(el.tagName) && !skip(el) && visible(el))
          .flatMap((el) => el.tagName === 'MAIN' ? [...el.children].map((k) => ({ el: k, trailing: false })) : [{ el, trailing: el.tagName === 'FOOTER' }]);
    const blocks = roots.filter((r) => visible(r.el)).flatMap((r) => explode(r.el).map((b) => ({ ...b, trailing: r.trailing }))).filter((b) => b.bottom > b.top).sort((a, b) => a.top - b.top);
    const merged = [];
    for (const b of blocks) {
      const last = merged[merged.length - 1];
      if (last && b.top < last.bottom - 2) { last.bottom = Math.max(last.bottom, b.bottom); last.trailing = last.trailing && b.trailing; last.labels = [...new Set([...last.labels, ...b.labels])]; } else merged.push({ ...b, labels: [...b.labels] });
    }
    const docH = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight);
    if (merged.length) { merged[0].top = 0; merged[merged.length - 1].bottom = Math.max(merged[merged.length - 1].bottom, docH); for (let i = 1; i < merged.length; i++) merged[i].top = merged[i - 1].bottom; }
    return { blocks: merged, docH };
  }, maxH);
}

/** Greedily pack consecutive blocks into page-sized groups; a lone oversize block is hard-cut as a last resort. */
function packBlocks(blocks, maxH, docH) {
  const groups = [];
  let cur = null;
  for (const b of blocks) {
    const h = b.bottom - b.top;
    if (cur && b.bottom - cur.top <= maxH) { cur.bottom = b.bottom; cur.labels = [...new Set([...cur.labels, ...(b.labels ?? [])])]; continue; }
    if (cur) groups.push(cur);
    if (h > maxH) {
      for (let y = b.top; y < b.bottom; y += maxH) groups.push({ top: y, bottom: Math.min(y + maxH, b.bottom), labels: b.labels ?? [] });
      cur = null;
    } else cur = { top: b.top, bottom: b.bottom, labels: [...(b.labels ?? [])] };
  }
  if (cur) groups.push(cur);
  if (groups.length > 1) {
    const last = groups[groups.length - 1], prev = groups[groups.length - 2];
    if (last.bottom - last.top < maxH * 0.15 && last.bottom - prev.top <= maxH * 1.3) { prev.bottom = last.bottom; prev.labels = [...new Set([...prev.labels, ...last.labels])]; groups.pop(); }
  }
  groups[groups.length - 1].bottom = Math.min(groups[groups.length - 1].bottom, docH);
  return groups;
}

const RATIO = 1.08; // slice height ÷ width that fits a letter page beneath the heading and bullets

async function shoot(name, url, account, { width = 1440, height = 900, mobile = false } = {}) {
  const page = await pageFor(account);
  await page.setViewport({ width, height, deviceScaleFactor: mobile ? 2 : 1.25, isMobile: mobile, hasTouch: mobile });
  await page.goto(BASE + url, { waitUntil: 'networkidle0' });
  await settle(page);
  if (mobile) {
    const file = path.join(OUT, `${name}.jpg`);
    await page.screenshot({ path: file, type: 'jpeg', quality: 76 });
    console.log(`✓ ${name} (phone)`);
    return { name, url, chunks: [file], labels: [[]], mobile };
  }
  const maxH = Math.round(width * RATIO);
  let { blocks, docH } = await measureBlocks(page, maxH);
  if (name !== 'home') { blocks = blocks.filter((b) => !b.trailing); docH = blocks[blocks.length - 1].bottom; } // the footer is shown once (home)
  const groups = packBlocks(blocks, maxH, docH).slice(0, MAX_SLICES[name] ?? 99);
  // The back-office sidebar is position: fixed, so it only covers one viewport: stretch the viewport to the page.
  if (await page.$('body.admin')) {
    await page.setViewport({ width, height: Math.max(height, Math.ceil(docH)), deviceScaleFactor: 1.25 });
    await new Promise((r) => setTimeout(r, 300));
  }
  const files = []; const labels = [];
  for (const [i, g] of groups.entries()) {
    const file = path.join(OUT, groups.length > 1 ? `${name}-${i}.jpg` : `${name}.jpg`);
    await page.screenshot({ path: file, type: 'jpeg', quality: 74, captureBeyondViewport: true, clip: { x: 0, y: g.top, width, height: g.bottom - g.top } });
    files.push(file); labels.push(g.labels ?? []);
  }
  console.log(`✓ ${name} → ${groups.length} slice(s): ${groups.map((g) => (g.labels ?? []).join(' | ') || '(no heading)').join('  ·  ')}`);
  return { name, url, chunks: files, labels };
}

const shots = [];
const DESK = { width: 1280, height: 1000 }; // staff screens: a laptop size, so their short pages fill a letter page
for (const [n, u, a] of SCREENS) shots.push(await shoot(n, u, a, a && a !== 'guest-list' ? DESK : {}));
for (const [n, u, a] of MOBILE) shots.push(await shoot(n, u, a, { width: 390, height: 844, mobile: true }));
fs.writeFileSync(path.join(OUT, 'chunks.json'), JSON.stringify(Object.fromEntries(shots.map((s) => [s.name, s])), null, 1));
await browser.close();
console.log('done');
