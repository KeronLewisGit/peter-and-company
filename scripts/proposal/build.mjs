// Builds the client proposal (letter-size PDF) from the captured screenshots, in the site's own
// Acado palette (orange, navy, yellow, grey) and fonts (Montserrat, Figtree). Usage: npm run proposal  (capture first; this step only needs Chrome)
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const SHOTS = path.resolve('dist/proposal/shots');
const chunks = JSON.parse(fs.readFileSync(path.join(SHOTS, 'chunks.json'), 'utf8'));
const OUT_HTML = path.resolve('dist/proposal/proposal.html');
const OUT_PDF = path.resolve('dist/proposal/Peter-and-Company-Website-Proposal.pdf');
const MAX_BYTES = 9_500_000;
const DATE = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
const TITLE = 'Acado St. Lucia (Peter & Company Distribution) – Trade Website Proposal';
// Counted from the code, so the proposal never quotes a stale number.
const countTests = (dir) => fs.readdirSync(dir, { withFileTypes: true }).reduce((n, e) => n + (e.isDirectory()
  ? countTests(path.join(dir, e.name))
  : e.name.endsWith('Test.php') ? (fs.readFileSync(path.join(dir, e.name), 'utf8').match(/public function test_/g) ?? []).length : 0), 0);
const TESTS = process.env.TEST_COUNT ?? String(countTests(path.resolve('tests')));
const BRANDS = execFileSync('php', ['artisan', 'tinker', '--execute', "echo collect(config('peterco.divisions'))->sum(fn ($d) => count($d['brands']));"], { encoding: 'utf8' }).trim();
const img = (f) => 'file://' + f;
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');

// Self-hosted site fonts (built by Vite into public/build/assets).
const ASSETS = path.resolve('public/build/assets');
const font = (prefix) => {
  const f = fs.readdirSync(ASSETS).find((n) => n.startsWith(prefix) && n.endsWith('.woff2'));
  if (!f) throw new Error(`Font ${prefix} not found: run npm run build first`);
  return 'file://' + path.join(ASSETS, f);
};
const FONTS = [
  ['Montserrat', 300, 'montserrat-300-normal'], ['Montserrat', 600, 'montserrat-600-normal'], ['Montserrat', 700, 'montserrat-700-normal'],
  ['Figtree', 400, 'figtree-400-normal'], ['Figtree', 600, 'figtree-600-normal'], ['Figtree', 700, 'figtree-700-normal'],
].map(([fam, w, p]) => `@font-face { font-family: '${fam}'; font-weight: ${w}; font-style: normal; src: url('${font(p)}') format('woff2'); }`).join('\n');

// The company's own logo from acadostlucia.com.
const LOGO = (white) => `<img class="logo" src="${img(path.resolve(`public/img/brand/acado-st-lucia${white ? '-white' : ''}.png`))}" alt="Acado St. Lucia">`;
// Eyebrow section label with the orange bar, as on the site.
const tag = (code, label) => `<p class="rack"><i>${esc(code)}</i><span>${esc(label)}</span></p>`;
// The cover photo as a JPEG (Chrome would embed the WebP uncompressed); falls back to the WebP without sips.
const HERO = (() => {
  const src = path.resolve('public/img/acado-team-1366.webp');
  const out = path.join(SHOTS, 'cover.jpg');
  try { execFileSync('sips', ['-s', 'format', 'jpeg', '-s', 'formatOptions', '72', src, '--out', out], { stdio: 'ignore' }); return img(out); } catch { return img(src); }
})();
// Motion frame strips (scripts/proposal/motion.mjs).
const MOTION_FILE = path.join(SHOTS, 'motion', 'motion.json');
const MOTION = fs.existsSync(MOTION_FILE) ? JSON.parse(fs.readFileSync(MOTION_FILE, 'utf8')) : null;
const JS_GZ = process.env.JS_GZ ?? (() => {
  const f = fs.readdirSync(ASSETS).find((n) => /^app-.*\.js$/.test(n));
  return f ? (Math.round(execFileSync('gzip', ['-c', path.join(ASSETS, f)]).length / 100) / 10) + ' kB' : '';
})();
const strip = (key, labels, cls = '') => `<div class="strip ${cls}" style="--n:${MOTION[key].length}">${MOTION[key].map((f, i) => `<figure><img src="${img(f)}" alt="${esc(labels[i] ?? '')}"><figcaption>${esc(labels[i] ?? '')}</figcaption></figure>`).join('')}</div>`;
const motionBlock = (title, text, body) => `<div class="mblock"><h3>${esc(title)}</h3><p class="mtext">${esc(text)}</p>${body}</div>`;

/** Hand-written captions per slice (key = screen name, index = slice number). */
const CAPTIONS = {
  home: [
    'The familiar Acado look, modernised: the orange top bar, the white header with the uppercase menu, and your own staff photo from the February 2025 rebrand as the hero, with the two trade actions and the facts the company publishes – trading since 1891, four divisions, the brands on its division pages, and the Acado Limited group. Then quick routes for the five people who visit.',
    'Your “About us” band, in your own words, and the orange “Our portfolio” band with the four divisions and your own division images.',
    'Sample catalogue lines, each with the brand’s logo tile from your division pages, a cases box and “Add to order”. No prices anywhere: the trade desk confirms them.',
    'Featured brands: your logo grid, now two rows of logos that drift in opposite directions (shown here as it appears with reduced motion), each linking to its division.',
    'How ordering works in four steps: on a desktop the panel stays in view while the steps scroll past, and a van drives to each stop.',
    'Island-wide delivery: a map of the ten districts with routes drawn out of Cul De Sac, and every district’s delivery days as text. The days are a sample until the trade desk confirms them.',
    'The pharmaceutical division, clearly for licensed pharmacies, hospitals and clinics only, and the company’s history since 1891 on a line that draws itself.',
    'Careers gets its own band, with the live count of open vacancies (or “Send your CV” when there are none), then the panel for brand owners and your navy “Looking for an outstanding distribution partner?” band, which closes every page.',
    'The orange footer with the white logo, tagline, head-office number, address and social links.',
  ],
  brands: [`Every division on one page with its blurb, dated facts from The Voice (Hennessy for 35+ years, Carib Brewery exclusive), and all ${BRANDS} brands from your division pages as logo tiles, each with the brand name as real text underneath, so buyers and search engines can read it.`, 'Wines, Spirits & Tobacco, continued.', 'Brewery, Beverages & Snacks. Food, Household, Frozen & Personal Care and Pharmaceuticals (marked for licensed buyers only) follow on the same page.'],
  division: ['A division page: your division image, the brands and categories, and a route to order from that division.', 'The brand logos in the division, with every name as text.', 'The catalogue lines in that division, ready to add to an order list.'],
  catalogue: ['The trade catalogue: filter by division, brand and category or search, and add cases to the order list from any line. Sample listings, unpriced, using only brands the company lists.', 'Catalogue, continued: every line shows its case size and a “Sample listing” badge in the demo.'],
  order: ['The order list after a buyer added three lines: change quantities or remove a line, add items that aren’t in the catalogue, and tick pharmaceuticals only with an approved licensed account.', 'Who is ordering, the trade account number (optional; typed in any format), district, delivery address and a preferred working day. The buyer gets a reference number on screen and by email.'],
  'trade-account': ['The trade account application, step by step: who is applying, the business, trade licence and VAT, and the extra licence details pharmacies and clinics must give.', 'Cash-on-delivery or credit terms with trade references, then the trade licence, VAT certificate and owner ID uploads. They are stored privately and only credit control can open them.', 'Consent, the declaration and send.'],
  principals: ['For brand owners outside St Lucia: what a principal gets, and an enquiry form with categories and an optional presentation upload.', 'Principal enquiry, continued.'],
  careers: ['Careers is in the main menu and the top bar, not hidden under About us. The page opens with the open-vacancy count and two actions, then the vacancies (samples in the demo) and a general application. HR publishes real vacancies from the back office.', 'Each vacancy links to its own page and the application form; HR keeps general applications on file.'],
  about: ['Who we are, with the rebrand photo, and the company’s history in its own words, in the grey band as on acadostlucia.com.', 'A six-date timeline from 1891 to the 2025 rename, and your published vision over your own photo.', 'Your five core values on the orange band, as on your site.', 'The five retail stores with your own store photos and their numbers.'],
  contact: ['Head-office phone and address with directions, and an enquiry form routed by topic.', 'Faster routes to the right team: orders, trade accounts, principals and jobs.'],
  login: ['Staff sign-in for the back office. Sign-in is rate-limited, and switched-off accounts cannot sign in.'],
  'sales-dashboard': ['The trade desk’s overview leads with the one number to act on: requests past their response target (two hours for an order). Then order trends, the median time to confirm an order, requested deliveries for the next working days, and the call queue. Demo data.', 'Requests per day, a breakdown by type, the oldest new requests and the delivery days customers asked for.'],
  'sales-inbox': ['Order requests with per-type stages (New, Confirmed, Out for delivery, Delivered, Cancelled), filters, search by business, phone or reference, and CSV export. Demo requests.'],
  'sales-order': ['One order request: the buyer, their trade account and terms, the lines with case counts, other items, the delivery address and day. One tap to call, WhatsApp or email, a stage, an assignee, and a timeline of every call and change. Demo request.'],
  'credit-board': ['Credit control’s board: applications by stage, oldest first, with the requested terms and a flag on licensed pharmacies. Demo applications.'],
  'credit-application': ['An application as credit control sees it: licence and VAT numbers, references, the requested terms, and each document with “Open”. Every opening is logged. Demo application with placeholder documents.', 'Approving opens a PC- trade account and emails the customer the number; the timeline records every step.'],
  'sales-application': ['The same application as the trade desk sees it: the business and contact, but the credit details are hidden and each document is locked “Credit control only”. Administrators see a lock on the documents too.'],
  'credit-accounts': ['Trade accounts with status (active, on hold, closed) and terms. Credit control changes them; the trade desk can look an account up but not change it. Demo accounts.'],
  'sales-products': ['The catalogue editor for the trade desk: brand, product, division, case size and code, an optional pack shot, published and featured flags. There are no price or stock fields, on purpose.'],
  'hr-dashboard': ['HR’s overview: job applications past their target, applications in the last 30 days and open vacancies. HR sees only applications and CVs. Demo data.'],
  'hr-vacancies': ['Vacancies with closing dates and application counts. Sample vacancies.'],
  'admin-staff': ['Staff accounts with four roles: administrator, sales / trade desk, credit control and HR.'],
};

const SECTIONS = [
  { id: 'public', code: 'A', title: 'The public website', intro: 'A trade-first website for the shops, bars, hotels, restaurants, pharmacies and clinics that buy from Acado St. Lucia, and for the brand owners and job seekers who want to reach it. Every fact on it comes from the company’s own website or a dated news report; the catalogue lines in the demo are samples.', screens: [
    ['home', 'Home page', ['Says in one screen what the company distributes and how a business starts buying.', 'Built from the company’s own facts: 1891, four divisions, the brands on its division pages, the Acado group.']],
    ['brands', 'Brands by division', ['Your logo grids kept, with every brand name added as searchable text that buyers and search engines can read.']],
    ['division', 'A division', ['Each division has its own page with its brands and catalogue lines.']],
    ['catalogue', 'Trade catalogue', ['Filter, search and add cases to an order list, without signing in. No prices or stock levels are shown.']],
    ['order', 'Order request', ['A buyer sends the list with a delivery day; the trade desk confirms prices, availability and the day by phone or WhatsApp.', 'Pharmaceutical items are accepted only from an approved, licensed pharmacy or clinic account.']],
    ['trade-account', 'Trade account and credit application', ['Replaces paper application forms: the documents arrive with the application, privately.']],
    ['principals', 'Brand owners and principals', ['A proper front door for manufacturers looking for St Lucia distribution.']],
    ['careers', 'Careers', ['Vacancies and CV upload, feeding HR’s inbox in the back office.']],
    ['about', 'About and retail stores', ['History since 1891, the rename, values, and the company’s own stores.']],
    ['contact', 'Contact', ['Head office, directions, and a form that lands with the right team.']],
  ]},
  { id: 'admin', code: 'B', title: 'The back office', intro: 'One private back office with four roles. The trade desk works order requests, enquiries and principal enquiries and keeps the catalogue. Credit control reviews trade account applications, opens accounts and is the only role that can open credit documents. HR runs vacancies and applications. Administrators manage everything else.', screens: [
    ['login', 'Staff sign-in', ['Individual accounts for every member of staff.']],
    ['sales-dashboard', 'Trade desk: overview', ['Leads with requests past their response target, so no order waits.']],
    ['sales-inbox', 'Trade desk: order requests', ['Each request type has its own stages and response target.']],
    ['sales-order', 'Trade desk: an order request', ['Everything needed to confirm the order in one call.']],
    ['credit-board', 'Credit control: the board', ['Every application by stage, at a glance.']],
    ['credit-application', 'Credit control: an application', ['Documents open for credit control only, and each opening is logged.']],
    ['sales-application', 'The same application, as the trade desk sees it', ['Credit details and documents stay locked for every other role.']],
    ['credit-accounts', 'Trade accounts', ['Account numbers buyers quote on their orders.']],
    ['sales-products', 'Catalogue editor', ['The trade desk keeps the catalogue up to date without a developer.']],
    ['hr-dashboard', 'HR: overview', ['HR sees job applications and CVs, nothing else.']],
    ['hr-vacancies', 'HR: vacancies', ['Publish and close vacancies.']],
    ['admin-staff', 'Administrator: staff accounts', ['Add staff, assign roles, switch accounts off.']],
  ]},
];

const FEATURES = [
  ['Public website', ['Phone-first, light and dark themes', 'Your colours, fonts and photos', 'Brand logos with searchable names', 'Motion: delivery map, logo marquee, page transitions', 'Trade catalogue, no prices', 'Order list and order requests', 'Delivery-day choice', 'Trade account and credit application', 'Principal enquiries', 'Careers with CV upload', 'Sitemap and structured data']],
  ['Back office', ['Four roles: admin, trade desk, credit, HR', 'Response targets per request type', 'Order stages and delivery days', 'Credit board and account approval', 'Trade accounts with PC- numbers', 'Catalogue editor', 'Vacancies and applications', 'CSV export']],
  ['Privacy', ['Credit documents: credit control only', 'CVs: HR and administrators only', 'Every document opening logged', 'Files stored outside the web root', 'Bank and card numbers refused']],
  ['Platform', ['Laravel 13 on PHP 8.3', 'Runs on standard shared hosting', 'HTTPS, security headers, spam protection', `${TESTS} automated tests`, 'Packaged installer and written guide']],
];

const PHONES = [
  [['m-home', 'Home'], ['m-catalogue', 'Trade catalogue'], ['m-order', 'Order list']],
  [['m-trade-account', 'Trade account application'], ['m-sales-dashboard', 'Trade desk overview'], ['m-sales-order', 'Order request, one-tap call']],
];

function caption(key, i, c) {
  const text = CAPTIONS[key]?.[i];
  if (!text) return '';
  const n = c.chunks.length > 1 ? `Part ${i + 1} of ${c.chunks.length} · ` : '';
  return `<figcaption class="cap">${esc(n)}${esc(text)}</figcaption>`;
}

// Screen pages are packed by measured height. Each section's slices are split into pages, in order, so
// that a page can hold slices from more than one screen (a slice that starts a new screen mid-page gets a
// small screen heading). A page may shrink its screenshots a little (to no less than 85% width) to take
// one more slice, such as a short tail. The split is chosen to avoid half-empty pages first, then to use
// as few pages as possible, then to shrink as little as possible.
const CONTENT_W = 7.4 * 96; // letter width minus the 0.55in side margins, in CSS px
const PAGE_H = 10 * 96; // .page height
const ITEM_GAP = 16; // .item + .item
const SAFETY = 6;
const MIN_SCALE = 0.85;
const HALF_EMPTY = 0.55; // a page whose content ends above 55% of its height reads as half empty

const headHtml = (sec, title, url, cont) => `<header class="shot-head"><div><p class="eyebrow">${esc(sec.title)}${cont ? ' · continued' : ''}</p><h2>${esc(title)}</h2></div><p class="url">${esc(url)}</p></header>`;
const subHtml = (title, url) => `<header class="sub-head"><h3>${esc(title)}</h3><p class="url">${esc(url)}</p></header>`;
const bulletsHtml = (bullets) => `<ul class="bullets">${bullets.map((b) => `<li>${esc(b)}</li>`).join('')}</ul>`;

function sectionSlices(sec) {
  return sec.screens.flatMap(([key, title, bullets]) => {
    const c = chunks[key];
    if (!c) throw new Error(`Screenshot missing: ${key}`);
    return c.chunks.map((file, i) => ({ key, title, bullets, url: c.url, i, file, cap: caption(key, i, c), report: key === 'sample-report' }));
  });
}

/** Renders every piece once in Chrome at the page's content width and returns their outer heights. */
async function measure(browser, sections) {
  const pieces = {};
  const add = (id, h) => { pieces[id] = h; };
  for (const sec of sections) {
    add(`foot`, foot());
    for (const sl of sectionSlices(sec)) {
      if (sl.i === 0) {
        add(`head:${sl.key}:0`, headHtml(sec, sl.title, sl.url, false));
        add(`sub:${sl.key}`, subHtml(sl.title, sl.url));
        add(`bul:${sl.key}`, bulletsHtml(sl.bullets));
      } else add(`head:${sl.key}:1`, headHtml(sec, sl.title, sl.url, true));
      add(`cap:${sl.key}:${sl.i}`, sl.cap);
    }
  }
  const files = [...new Set(sections.flatMap((sec) => sectionSlices(sec).map((sl) => sl.file)))];
  const file = path.resolve('dist/proposal/measure.html');
  fs.writeFileSync(file, `<!doctype html><html><head><meta charset="utf-8"><style>${STYLE}</style></head><body>${Object.entries(pieces).map(([id, h]) => `<div data-id="${esc(id)}" style="width:${CONTENT_W}px;display:flex;flex-direction:column">${h}</div>`).join('')}</body></html>`);
  const page = await browser.newPage();
  await page.goto('file://' + file, { waitUntil: 'networkidle0' });
  await page.evaluate(() => document.fonts.ready);
  const out = await page.evaluate(async (files) => {
    const h = {};
    for (const el of document.querySelectorAll('[data-id]')) h[el.dataset.id] = el.getBoundingClientRect().height;
    const dims = {};
    for (const f of files) { const im = new Image(); im.src = f; await im.decode(); dims[f] = [im.naturalWidth, im.naturalHeight]; }
    return { h, dims };
  }, files.map(img));
  await page.close();
  fs.rmSync(file);
  return { h: out.h, dims: Object.fromEntries(files.map((f) => [f, out.dims[img(f)]])) };
}

function packSection(sec, { h, dims }) {
  const budget = PAGE_H - h.foot - SAFETY;
  const slices = sectionSlices(sec);
  const imgBox = (sl) => {
    const [nw, nh] = dims[sl.file];
    const w = sl.report ? Math.min(CONTENT_W, 6.9 * 96 * nw / nh) : CONTENT_W;
    return { w, h: w * nh / nw };
  };
  // A page holding slices a..b-1: its items, the scale that fits them, the height used.
  const layout = (a, b) => {
    const first = slices[a];
    const headH = h[`head:${first.key}:${first.i > 0 ? 1 : 0}`];
    const items = slices.slice(a, b).map((sl, k) => {
      const sub = k > 0 && sl.i === 0;
      const fixed = (k ? ITEM_GAP : 0) + (sub ? h[`sub:${sl.key}`] : 0) + (sl.i === 0 ? h[`bul:${sl.key}`] : 0) + h[`cap:${sl.key}:${sl.i}`];
      return { sl, sub, fixed, img: imgBox(sl) };
    });
    const fixed = headH + items.reduce((t, it) => t + it.fixed, 0);
    const imgs = items.reduce((t, it) => t + it.img.h, 0);
    const scale = Math.min(1, (budget - fixed) / imgs);
    return { first, items, scale, used: fixed + imgs * scale };
  };
  // best[a] = cheapest packing of slices a..end; a lone slice always fits (shrunk if it must be).
  const best = Array(slices.length + 1).fill(null);
  best[slices.length] = { cost: 0, pages: [] };
  for (let a = slices.length - 1; a >= 0; a--) {
    for (let b = a + 1; b <= slices.length; b++) {
      const pg = layout(a, b);
      if (b - a > 1 && pg.scale < MIN_SCALE) break;
      const cost = (pg.used < HALF_EMPTY * PAGE_H ? 1000 : 0) + 100 + 200 * (1 - pg.scale) + best[b].cost;
      if (!best[a] || cost < best[a].cost) best[a] = { cost, pages: [pg, ...best[b].pages] };
    }
  }
  const pages = best[0].pages;
  return pages.map((pg) => `
    <section class="page shot">
      ${headHtml(sec, pg.first.title, pg.first.url, pg.first.i > 0)}
      ${pg.items.map(({ sl, sub, img: box }) => `<div class="item">${sub ? subHtml(sl.title, sl.url) : ''}${sl.i === 0 ? bulletsHtml(sl.bullets) : ''}<figure class="${sl.report ? 'report' : ''}"><img src="${img(sl.file)}" alt="${esc(sl.title)}" style="width:${(box.w * pg.scale).toFixed(1)}px;height:${(box.h * pg.scale).toFixed(1)}px">${sl.cap}</figure></div>`).join('')}
      ${foot()}
    </section>`).join('');
}

const foot = () => `<footer class="foot"><span>${esc(TITLE)}</span><span>${esc(DATE)}</span></footer>`;

const STYLE = `
  ${FONTS}
  :root { --bg: #fff; --grey: #f4f4f4; --ink: #262626; --ink-2: #555; --ink-3: #676767; --line: #e4e4e4; --primary: #b94e00; --orange: #e2680e; --orange-2: #f26921; --yellow: #ffbd0b; --navy: #002766; --navy-2: #003a8c; --soft: #fdf0e6; }
  @page { size: Letter; margin: 0.5in 0.55in 0.5in; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; font-family: 'Figtree', 'Helvetica Neue', Arial, sans-serif; color: var(--ink); font-size: 10.4pt; line-height: 1.45; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  h1, h2, h3 { font-family: 'Montserrat', 'Helvetica Neue', Arial, sans-serif; font-weight: 700; color: var(--ink); margin: 0; line-height: 1.1; letter-spacing: -0.01em; }
  h1 { color: var(--orange); } h2 { color: var(--navy); } h3 { font-weight: 600; }
  b, strong { font-weight: 700; }
  .page { page-break-after: always; break-after: page; height: 10in; display: flex; flex-direction: column; overflow: hidden; }
  .page:last-child { page-break-after: auto; break-after: auto; }
  .page > * { flex-shrink: 0; }
  .eyebrow { font-family: 'Montserrat', sans-serif; font-size: 7.6pt; font-weight: 600; letter-spacing: .16em; text-transform: uppercase; color: var(--primary); margin: 0 0 4px; }
  .rack { display: flex; align-items: center; gap: 8px; margin: 0 0 10px; font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 7.8pt; letter-spacing: .16em; text-transform: uppercase; color: var(--primary); }
  .rack::before { content: ''; width: 26px; height: 3px; border-radius: 2px; background: linear-gradient(90deg, var(--orange), var(--yellow)); }
  .rack i { font-style: normal; color: var(--ink-3); font-weight: 600; }
  .logo { height: 46px; width: auto; display: block; }
  .grad { height: 5px; background: linear-gradient(90deg, var(--orange), var(--orange-2) 55%, var(--yellow)); }
  /* cover */
  .cover { justify-content: space-between; }
  .cover .topbar { background: var(--orange); color: #1a1a1a; font-size: 8.4pt; font-weight: 600; padding: 6px 14px; border-radius: 10px 10px 0 0; display: flex; justify-content: space-between; }
  .cover .head { display: flex; justify-content: space-between; align-items: center; padding: 14px 4px 12px; }
  .cover .head .eyebrow { margin: 0; }
  .cover .hero { position: relative; height: 3.15in; border-radius: 14px; overflow: hidden; background: #001433; }
  .cover .hero img { width: 100%; height: 100%; object-fit: cover; object-position: 60% 40%; display: block; }
  .cover .hero::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(0,20,51,.55) 0%, rgba(0,20,51,0) 60%); }
  .cover .hero .grad { position: absolute; left: 0; right: 0; bottom: 0; z-index: 1; }
  .cover h1 { font-size: 30pt; line-height: 1.06; margin: 4px 0 12px; color: var(--navy); } .cover h1 em { font-style: normal; color: var(--orange); }
  .cover p.lead { color: var(--ink-2); font-size: 11.5pt; max-width: 6.6in; margin: 0; }
  .cover .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; background: var(--navy); color: rgba(255,255,255,.86); border-radius: 12px; padding: 14px 18px 16px; font-size: 9.3pt; position: relative; overflow: hidden; }
  .cover .meta::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 4px; background: linear-gradient(90deg, var(--orange), var(--yellow)); }
  .cover .meta b { display: block; font-family: 'Montserrat', sans-serif; font-size: 7.4pt; letter-spacing: .14em; text-transform: uppercase; margin-bottom: 4px; color: var(--yellow); font-weight: 600; }
  .cover .meta .n { color: #fff; font-weight: 700; font-size: 10.5pt; }
  /* part dividers: Acado's navy band */
  .band { background: radial-gradient(90% 70% at 100% 100%, rgba(226,104,14,.28) 0%, rgba(226,104,14,0) 60%), linear-gradient(160deg, var(--navy) 0%, #001a45 100%); color: #fff; border-radius: 14px; padding: 0.6in 0.6in 0.7in; position: relative; overflow: hidden; }
  .band::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 8px; background: linear-gradient(90deg, var(--orange), var(--orange-2) 55%, var(--yellow)); }
  .band .rack { color: var(--yellow); } .band .rack i { color: rgba(255,255,255,.75); }
  .divider { justify-content: center; }
  .divider h1 { font-size: 36pt; margin: 4px 0 16px; color: #fff; font-weight: 300; text-transform: uppercase; letter-spacing: .02em; } .divider p { color: rgba(255,255,255,.88); font-size: 12pt; max-width: 6in; margin: 0; }
  .divider ol { color: rgba(255,255,255,.86); columns: 2; font-size: 10pt; margin: 22px 0 0; padding-left: 18px; } .divider li { break-inside: avoid; margin: 3px 0; } .divider li::marker { color: var(--yellow); font-weight: 700; }
  /* text pages */
  .prose h1 { font-size: 25pt; margin-bottom: 10px; }
  .prose h2 { font-size: 14pt; margin: 14px 0 6px; }
  .prose p { margin: 0 0 8px; }
  .prose ul.list { margin: 0 0 8px; padding-left: 17px; } .prose ul.list li { margin: 2px 0; } .prose li::marker { color: var(--orange); }
  .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .card { border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; background: #fff; break-inside: avoid; box-shadow: inset 0 4px 0 var(--orange), 0 6px 16px -10px rgba(0,20,51,.25); }
  .card h3 { font-size: 11.5pt; margin-bottom: 5px; color: var(--navy); }
  .card ul { margin: 0; padding-left: 15px; font-size: 9.3pt; } .card li { margin: 1.5px 0; }
  .evidence { border-left: 4px solid var(--orange); background: var(--soft); border-radius: 0 10px 10px 0; padding: 9px 12px 9px 28px; margin: 8px 0 10px; }
  .evidence li { margin: 3px 0; }
  .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 9px; margin: 12px 0; } .stat { background: var(--navy); color: #fff; border-radius: 12px; padding: 11px 12px 13px; position: relative; overflow: hidden; } .stat::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, var(--orange), var(--yellow)); } .stat b { display: block; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 23pt; line-height: 1; color: var(--yellow); } .stat span { display: block; margin-top: 5px; font-size: 8.4pt; color: rgba(255,255,255,.86); line-height: 1.3; }
  table.feat { width: 100%; border-collapse: collapse; font-size: 9.4pt; } table.feat td { border-bottom: 1px solid var(--line); padding: 9px 6px; vertical-align: top; } table.feat td:first-child { width: 20%; font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 11pt; color: var(--navy); }
  .pill-row { display: flex; flex-wrap: wrap; gap: 5px; } .pill { border: 1px solid #f3c9a6; background: var(--soft); color: #9a4100; border-radius: 999px; padding: 2px 9px; font-size: 8.6pt; font-weight: 600; }
  table.terms { width: 100%; border-collapse: collapse; font-size: 9.4pt; margin: 4px 0 8px; } table.terms th, table.terms td { text-align: left; vertical-align: top; padding: 4px 8px; border-bottom: 1px solid var(--line); } table.terms th { width: 31%; font-weight: 700; }
  table.terms thead th { font-family: 'Montserrat', sans-serif; font-size: 7.6pt; letter-spacing: .12em; text-transform: uppercase; color: var(--ink-3); border-bottom: 2px solid var(--orange); }
  .price { display: grid; grid-template-columns: 1.3fr 1fr; gap: 12px; align-items: stretch; margin: 6px 0 10px; }
  .price .big { background: var(--navy); color: #fff; border-radius: 12px; padding: 14px 16px; position: relative; overflow: hidden; } .price .big::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 4px; background: linear-gradient(90deg, var(--orange), var(--yellow)); } .price .big b.amt { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 26pt; line-height: 1; display: block; margin-bottom: 6px; color: #fff; } .price .big span { color: rgba(255,255,255,.88); font-size: 9.6pt; }
  .price .year { border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; background: var(--grey); box-shadow: inset 0 -4px 0 var(--orange); } .price .year p { margin: 0 0 4px; } .price .year .k { font-family: 'Montserrat', sans-serif; font-size: 7.4pt; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; color: var(--primary); } .price .year .v { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 14.5pt; line-height: 1.15; color: var(--navy); }
  .two { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .two ul { margin: 0; padding-left: 16px; font-size: 9.4pt; } .two li { margin: 2.5px 0; }
  .weeks { list-style: none; margin: 6px 0 10px; padding: 0; } .weeks li { display: grid; grid-template-columns: 0.9in 1fr; gap: 10px; padding: 6px 0; border-bottom: 1px solid var(--line); } .weeks b { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 12pt; color: var(--orange); }
  .next li { margin: 5px 0; } .next li::marker { color: var(--orange); font-weight: 700; }
  .contact { margin-top: 14px; background: var(--grey); border-radius: 12px; padding: 14px 16px; border-left: 5px solid var(--orange); } .contact p { margin: 2px 0; } .contact a { color: var(--primary); }
  /* screenshot pages */
  .shot-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; padding-bottom: 7px; margin-bottom: 8px; border-bottom: 3px solid; border-image: linear-gradient(90deg, var(--orange), var(--orange-2) 55%, var(--yellow)) 1; }
  .shot-head h2 { font-size: 17pt; } .url { font-family: Menlo, Consolas, monospace; font-size: 8pt; color: var(--ink-3); margin: 0; white-space: nowrap; }
  .bullets { margin: 0 0 7px; padding-left: 16px; font-size: 9.3pt; } .bullets li { margin: 1px 0; } .bullets li::marker { color: var(--orange); }
  figure { margin: 0; display: flex; flex-direction: column; min-height: 0; flex: 1 1 auto; }
  figure img { display: block; max-width: 100%; min-height: 0; flex: 0 1 auto; object-fit: contain; object-position: left top; border: 1px solid var(--line); border-radius: 8px; align-self: flex-start; }
  .item { display: flex; flex-direction: column; flex: none; } .item + .item { margin-top: 16px; } .item figure { flex: none; } .item figure img { max-width: none; max-height: none; flex: none; }
  .sub-head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; border-bottom: 1px solid var(--line); padding-bottom: 4px; margin-bottom: 6px; } .sub-head h3 { font-size: 12.5pt; color: var(--navy); }
  .cap { margin: 6px 0 0; font-size: 8.9pt; line-height: 1.38; color: var(--ink-2); border-left: 3px solid var(--orange); padding-left: 8px; flex: none; }
  .phones { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin: 14px 0.15in 0; } .phones figure { display: block; } .phones img { display: block; width: 100%; height: auto; border-radius: 18px; border: 6px solid var(--navy); } .phones figcaption { text-align: center; font-size: 9pt; color: var(--ink-3); margin-top: 6px; }
  .motion .mblock { margin: 10px 0 4px; } .motion .mblock h3 { font-size: 12.5pt; color: var(--navy); } .motion .mtext { margin: 2px 0 6px; font-size: 9.4pt; color: var(--ink-2); }
  .strip { display: grid; grid-template-columns: repeat(var(--n), 1fr); gap: 8px; } .strip figure { margin: 0; display: block; } .strip img { display: block; width: 100%; height: auto; border-radius: 8px; border: 1px solid var(--line); }
  .strip figcaption { margin-top: 3px; font-size: 7.8pt; color: var(--ink-3); font-family: 'Montserrat', sans-serif; font-weight: 600; letter-spacing: .04em; }
  .strip.tall img { max-height: 3.6in; width: auto; max-width: 100%; margin: 0 auto; } .strip.tall figure { text-align: center; }
  .strip.one { grid-template-columns: 1fr; } .strip.short img { max-height: 2.55in; width: auto; max-width: 100%; }
  footer.foot { margin-top: auto; padding-top: 8px; font-size: 7.8pt; color: var(--ink-3); display: flex; justify-content: space-between; flex: none; }
`;

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--allow-file-access-from-files'] });
const measured = await measure(browser, SECTIONS);
const packed = Object.fromEntries(SECTIONS.map((sec) => [sec.id, packSection(sec, measured)]));
const screenCount = SECTIONS.reduce((t, s) => t + s.screens.length, 0) + PHONES.flat().length;

const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><title>${esc(TITLE)}</title>
<style>${STYLE}</style></head><body>

<section class="page cover">
  <div>
    <div class="topbar"><span>Formerly Peter &amp; Company Distribution · Cul De Sac, Castries</span><span>Website proposal</span></div>
    <div class="head">${LOGO(false)}<p class="eyebrow">${esc(DATE)}</p></div>
    <div class="hero"><img src="${HERO}" alt="Acado St. Lucia staff at the unveiling of the new Acado sign"><div class="grad"></div></div>
  </div>
  <div>
    ${tag('00', 'Website proposal')}
    <h1>A trade website that <em>takes orders</em>, opens accounts and hires, for Acado St. Lucia</h1>
    <p class="lead">A new acadostlucia.com in your own colours, for the shops, bars, hotels, pharmacies and clinics that buy from you: a brand portfolio and trade catalogue, order requests, trade account and credit applications with private document upload, principal enquiries, careers, and a back office for the trade desk, credit control and HR – for US$1,000 a month, with no charge for design and development.</p>
  </div>
  <div class="meta">
    <div><b>Prepared for</b><span class="n">Acado St. Lucia Limited</span><br>formerly Peter &amp; Company Distribution<br>Cul De Sac, P.O. Box 84-85, Castries, Saint Lucia</div>
    <div><b>Prepared by</b><span class="n">Keron Lewis, Code Canvas Consultants Ltd</span><br>Port of Spain · codecanvastt.com<br>+1 868 466-CODE · info@codecanvastt.com</div>
  </div>
</section>

<section class="page prose">
  ${tag('01', 'Executive summary')}
  <h1>Why rebuild now</h1>
  <p>Peter &amp; Company has traded in Castries since 1891, and as Acado St. Lucia it describes itself as one of the island’s largest distributors of consumer products: wines and spirits, Carib and the Brewery range, food, household and personal care, and pharmaceuticals, from Cul De Sac to supermarkets, minimarts, bars, hotels and pharmacies island-wide. The website does not yet do any of that work. When we checked acadostlucia.com on 26 October 2026:</p>
  <ul class="list evidence">
    <li>The brand portfolio is shown as <b>logo grids</b> on four division pages – images with no brand names that buyers can search or search engines can read.</li>
    <li>There is <b>no catalogue, no price list request and no way to send an order</b>. A buyer still has to phone or wait for a rep.</li>
    <li>There is <b>no trade account or credit application</b>. Licences, VAT certificates and IDs travel on paper or by email attachment.</li>
    <li>The contact page has the head-office number and a general contact form, but <b>no email address</b>. The careers page has a short form with <b>no CV upload and no vacancies</b>, and brand owners looking for a distributor have only the general contact form.</li>
  </ul>
  <p>This proposal turns the website into a working trade channel. A buyer finds a line, adds cases to an order list and sends it with a delivery day; the trade desk confirms prices and availability by phone. A new customer applies for cash-on-delivery or credit terms and uploads their documents, which only credit control can open. Brand owners and job seekers each have a proper front door. A complete working demo has been built from the company’s own published information and can be shown live.</p>
  <div class="stat-grid">
    <div class="stat"><b>${screenCount}</b><span>screens in this proposal, all from the working demo</span></div>
    <div class="stat"><b>${BRANDS}</b><span>brands from your own division pages, logos with names as text</span></div>
    <div class="stat"><b>4</b><span>staff roles: admin, trade desk, credit, HR</span></div>
    <div class="stat"><b>${TESTS}</b><span>automated tests, run on every change</span></div>
  </div>
  <h2>What the company gains</h2>
  <div class="cols">
    <div class="card"><h3>Trade customers</h3><ul><li>A catalogue they can browse at any hour, without signing in</li><li>Orders sent in minutes, with a reference number</li><li>Account applications from a phone, documents included</li></ul></div>
    <div class="card"><h3>Trade desk, credit and HR</h3><ul><li>One inbox, with a response target for every request</li><li>Credit documents kept private and every opening logged</li><li>Vacancies and CVs in one place</li></ul></div>
  </div>
  ${foot()}
</section>

<section class="page prose">
  ${tag('02', 'At a glance')}
  <h1>What is included</h1>
  <table class="feat"><tbody>
    ${FEATURES.map(([a, f]) => `<tr><td>${esc(a)}</td><td><div class="pill-row">${f.map((x) => `<span class="pill">${esc(x)}</span>`).join('')}</div></td></tr>`).join('')}
  </tbody></table>
  <h2>How an order reaches the truck</h2>
  <p>A buyer adds cases from the catalogue (or types items that are not listed), quotes their trade account number if they have one, and chooses a working day. The trade desk is alerted at once; the overview shows any order still waiting after two hours. The desk calls or messages the buyer to confirm prices, stock and the day, then moves the request through Confirmed, Out for delivery and Delivered. Nothing is charged online.</p>
  <h2>Pharmaceuticals stay business-to-business</h2>
  <p>The pharmaceutical range is never listed publicly. Pharmaceutical orders are accepted only from an approved pharmacy or clinic account with its licence on file, and there is no sale of medicines to the public anywhere on the site.</p>
  <h2>Content and data in the demo</h2>
  <p>Facts come from acadostlucia.com and The Voice St Lucia (the 2025 rename, the Carib Brewery and El Dorado agencies, Hennessy, the 2020 Diquez award). The design follows acadostlucia.com’s colours and layout; the logo, photos and brand-logo tiles are your own from acadostlucia.com (to be confirmed with you), and the few port and landscape photos are credited Wikimedia Commons images. <b>Catalogue lines, vacancies, accounts and requests in the screenshots are demo records</b>, marked as samples, and no prices, stock levels, staff names or figures are invented.</p>
  ${foot()}
</section>

${SECTIONS.map((sec) => `
<section class="page band divider">
  ${tag(sec.code, 'Part ' + sec.code)}
  <h1>${esc(sec.title)}</h1>
  <p>${esc(sec.intro)}</p>
  <ol>${sec.screens.map((s) => `<li>${esc(s[1])}</li>`).join('')}</ol>
</section>
${packed[sec.id]}
`).join('')}

${PHONES.map((row, i) => `
<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design${i ? ' · continued' : ''}</p><h2>${i ? 'Applications and the back office on a phone' : 'Built for phones first'}</h2></div><p class="url">390 × 844</p></header>
  <p style="margin:0 0 4px">${i ? 'Owners apply from the shop counter with a phone photo of their licence, and reps can check and call back an order from the road.' : 'Buyers can order from the shop floor on a phone. A bar with Call, Catalogue and the order list stays at the bottom of every public page.'}</p>
  <div class="phones">${row.map(([k, c]) => `<figure><img src="${img(chunks[k].chunks[0])}" alt="${esc(c)}"><figcaption>${esc(c)}</figcaption></figure>`).join('')}</div>
  ${foot()}
</section>`).join('')}

${MOTION ? `
<section class="page prose motion">
  ${tag('M', 'Motion and interaction')}
  <h1>A website that feels a level above</h1>
  <p>The new site moves the way a modern brand does: calm, quick and on brand, never in the way. Every effect is built from your own material – the Acado mark, your colours, your photo and your districts – and each one does a job.</p>
  ${motionBlock('Hero entrance', 'The rebrand photo drifts slowly, the headline rises word by word, the four leaves of the Acado mark assemble and turn, and the figures count up to 1891, 4 divisions and ' + BRANDS + ' brands.', strip('hero', ['0.2 s', '0.5 s', '1 s', '3 s'], 'wide'))}
  ${motionBlock('Island-wide delivery map', 'Routes draw out of Cul De Sac to all ten districts as the map scrolls into view, vans run along them, and pointing at a district (or tabbing to it) shows its delivery days. The days are a sample until your trade desk confirms them.', strip('map', ['Arriving', 'Routes drawing', 'All ten districts', 'Soufrière on hover'], 'tall'))}
  ${foot()}
</section>
<section class="page prose motion">
  <header class="shot-head"><div><p class="eyebrow">Motion and interaction · continued</p><h2>Ordering, made tangible</h2></div></header>
  ${motionBlock('From list to loading bay', 'The four ordering steps scroll past a panel that stays in view; the van drives to each stop as its step becomes active.', strip('steps', ['Step 1', 'Step 2', 'Step 4']))}
  ${motionBlock('Add to order', 'The line is added without leaving the page: its logo flies to the order list, the count bumps and a toast confirms, with a link to the list. Without JavaScript the same button posts the form as before.', strip('order', ['Added', 'On its way', 'Confirmed']))}
  ${motionBlock('Light and dark', 'Switching theme reveals the other one in a circle that grows from the button, and moving between pages cross-fades smoothly instead of flashing white.', strip('theme', ['Light', 'Switching', 'Dark']))}
  ${foot()}
</section>
<section class="page prose motion">
  <header class="shot-head"><div><p class="eyebrow">Motion and interaction · continued</p><h2>Your brands, alive on the page</h2></div></header>
  ${motionBlock('Brand-logo marquee', 'Two rows of your brand logos drift in opposite directions, endlessly. Pointing at them, or tabbing into them, pauses them, and a Pause button stops them for good.', strip('marquee', ['Two rows, opposite directions'], 'one'))}
  ${motionBlock('Division cards', 'Your four division cards tilt toward the pointer, with the picture drifting the other way and a soft glare. Also: a scroll-progress bar in Acado orange, a warm spotlight on the navy partner band, a history line that draws itself from 1891 to 2025, and a progress stepper on the trade account application.', strip('tilt', ['Tilt and glare'], 'one short'))}
  <div class="cols" style="margin-top:12px">
    <div class="card"><h3>Built responsibly</h3><ul>
      <li>Visitors who ask their device for reduced motion get a still, complete page: the logos stand in a grid and the map is already drawn.</li>
      <li>Everything works without JavaScript; the page is never hidden waiting for a script.</li>
      <li>Only smooth, GPU-friendly properties are animated, with no layout shift, and loops pause when off screen.</li>
    </ul></div>
    <div class="card"><h3>Still fast and accessible</h3><ul>
      <li>No animation library: the whole script is ${esc(JS_GZ)} gzipped.</li>
      <li>AA contrast in light and dark, 44-pixel touch targets, and every map district and control reachable by keyboard.</li>
      <li>No sound, and nothing that flashes.</li>
    </ul></div>
  </div>
  ${foot()}
</section>` : ''}

<section class="page prose">
  ${tag('03', 'Technology, hosting & security')}
  <h1>Built properly, hosted for you</h1>
  <div class="cols">
    <div class="card"><h3>Platform</h3><ul><li>Laravel 13 on PHP 8.3 with a MySQL database: open-source and widely supported, with no proprietary page builder</li><li>Fonts served from your own site, not a third-party font service</li><li>No background servers needed; emails send directly</li><li>${TESTS} automated tests cover every form, the order list, roles and document privacy</li></ul></div>
    <div class="card"><h3>Security and privacy</h3><ul><li>HTTPS enforced, with security headers on every page</li><li>Trade licences, VAT certificates, IDs and CVs are stored outside the public website</li><li>Credit documents open for credit control only, CVs for HR; each opening is logged</li><li>Forms are rate-limited and spam-trapped; bank and card numbers are refused</li><li>Login throttling, hashed passwords and individual staff accounts</li></ul></div>
    <div class="card"><h3>Speed and search</h3><ul><li>Responsive WebP photography; uploaded pack shots are resized automatically</li><li>Brands and products as real text, with an XML sitemap and structured data</li><li>Works without JavaScript; accessible forms, keyboard navigation and AA contrast in light and dark themes</li></ul></div>
    <div class="card"><h3>Ownership</h3><ul><li>Your content, photos, domain and customer data are always yours</li><li>Staff manage the catalogue, vacancies, accounts and users themselves</li><li>Hosting, backups, monitoring and updates are handled by Code Canvas under the monthly plan</li></ul></div>
  </div>
  <h2>Emails included</h2>
  <p>Every request sends the customer a confirmation with their reference number and what happens next, and alerts the right team’s inbox (trade desk, credit control, HR or management) with a link to the request. Approving an application emails the customer their new trade account number. Credit details, documents and CVs are never emailed; they stay in the back office.</p>
  ${foot()}
</section>

<section class="page prose">
  ${tag('04', 'Investment')}
  <h1>The US$1,000 a month plan</h1>
  <div class="price">
    <div class="big"><b class="amt">US$1,000 <small style="font-size:12pt">per month</small></b><span><b>US$0 for design and development.</b> No setup fee and no deposit. Invoiced in US dollars. Billing starts at the end of the month in which the site launches, prorated for the days it was live; monthly invoices are due within 14 days.</span></div>
    <div class="year"><p class="k">Year one</p><p class="v">US$12,000</p><p class="k" style="margin-top:6px">Or prepaid annually (10% off)</p><p class="v">US$10,800</p><p class="k" style="margin-top:6px">Build cost</p><p class="v">US$0</p></div>
  </div>
  <div class="cols" style="margin-top:8px">
    <div class="card"><h3>Included every month</h3><ul>
      <li>The complete design and build shown in this proposal</li>
      <li>Managed hosting with CDN and SSL</li>
      <li>Daily backups plus a weekly off-site copy</li>
      <li>Security patches and software updates</li>
      <li>24/7 uptime monitoring</li>
      <li><b>Up to 10 hours a month</b> of content changes and small improvements</li>
      <li>Staff training</li>
      <li>Google Analytics, Search Console and a monthly summary</li>
      <li>Support by email, WhatsApp and phone, plus a quarterly review call</li>
      <li>A 30-day post-launch warranty</li>
    </ul></div>
    <div class="card"><h3>Terms</h3><ul>
      <li>No minimum term. 30 days' notice by email from either side.</li>
      <li>Price fixed for the first 24 months.</li>
      <li>Optional annual prepayment at 10% off: US$10,800 a year.</li>
      <li><b>Handover fee US$7,000, only if you leave.</b> It buys the website outright and moves it to hosting in Acado St. Lucia's name. It is waived if Code Canvas stops trading.</li>
      <li>Your content, domain and data are always yours.</li>
    </ul>
    <h3 style="margin-top:8px">Quoted separately, only with your approval</h3><ul>
      <li>Work beyond 10 hours in a month: US$50 an hour, or a fixed quote</li>
      <li>Customer log-ins with account-specific price lists, statements or online payment</li>
      <li>Integration with your ERP, stock or accounting system (live stock, order export)</li>
      <li>A rep and merchandiser app; SMS or WhatsApp Business API messaging</li>
      <li>Paid advertising and SEO campaigns; professional photography</li>
    </ul></div>
  </div>
  <h2>Service levels</h2>
  <table class="terms"><thead><tr><th>Issue</th><th>Response</th><th>Fix target</th></tr></thead><tbody>
    <tr><td>Site down or forms not sending</td><td>1 hour, any day</td><td>4 hours</td></tr>
    <tr><td>Something on a page is broken</td><td>4 business hours</td><td>1 business day</td></tr>
    <tr><td>Content changes (text, photos, catalogue, holidays)</td><td>Acknowledged in 1 business day</td><td>Done in 2 business days</td></tr>
  </tbody></table>
  ${foot()}
</section>

<section class="page prose">
  ${tag('05', 'Timeline')}
  <h1>Six weeks to launch</h1>
  <ol class="weeks">
    <li><b>Week 1</b><span>Kick-off, brand assets and content: logo files (and any Acado group guidelines), photos, and confirmation of the facts and brands on the site.</span></li>
    <li><b>Week 2</b><span>Revision round 1. Forms, emails and staff roles configured with your real inboxes; the trade account rules and documents agreed with credit control.</span></li>
    <li><b>Week 3</b><span>Data and content load: the product list with case sizes and codes, existing trade accounts, delivery days by district, public holidays and vacancies.</span></li>
    <li><b>Week 4</b><span>Revision round 2.</span></li>
    <li><b>Week 5</b><span>Formal testing with the trade desk, credit control and HR on every form, email, role and document.</span></li>
    <li><b>Week 6</b><span>Launch on HTTPS at acadostlucia.com, redirects from the old pages, Search Console, and staff training.</span></li>
  </ol>
  <h2>What we need from Acado St. Lucia</h2>
  <ul class="list">
    <li>The logo as a vector file, and any Acado Limited brand guidelines the site should follow.</li>
    <li>Photos of the warehouse, fleet and team, or time for a short photo session at Cul De Sac, and the full-size originals of the photos on acadostlucia.com.</li>
    <li>Confirmation that the brand logos from your division pages may be shown on the new site.</li>
    <li>The product list (brand, product, case size, code) you are happy to publish, without prices, and confirmation of the brands you carry.</li>
    <li>Credit control’s account rules and document list, and the pharmacy licensing checks you apply.</li>
    <li>The staff who need accounts and their roles, and the inboxes for orders, credit, HR and brand enquiries.</li>
    <li>Access to the acadostlucia.com domain settings, and to your Google Business Profile and Analytics if they exist.</li>
  </ul>
  <h2>When billing starts</h2>
  <p>Not on signing. The build, both revision rounds, testing and the preview site cost nothing. Billing starts at the end of the month in which the site launches, prorated for the days it was live. Nothing goes live until you approve it in writing.</p>
  ${foot()}
</section>

<section class="page prose">
  ${tag('06', 'Next steps')}
  <h1>From proposal to launch</h1>
  <ol class="next" style="padding-left:18px">
    <li><b>See the demo live.</b> We walk through the website and the back office together, on a laptop and a phone.</li>
    <li><b>Tell us what to change.</b> Pages, wording, roles or the request flows; revision round 1 opens straight away.</li>
    <li><b>Sign a one-page agreement</b> with the terms in this proposal: US$1,000 a month, month to month on 30 days' notice, US$0 build cost, and a US$7,000 handover fee only if you leave.</li>
    <li><b>Kick-off the same week</b>, with launch six weeks later.</li>
  </ol>
  <div class="contact" style="max-width:4.8in">
    <p class="eyebrow">Contact</p>
    <p style="font-family:'Montserrat',sans-serif;font-weight:800;font-size:16pt">Keron Lewis</p>
    <p>Director, Code Canvas Consultants Ltd · Port of Spain, Trinidad and Tobago</p>
    <p>codecanvastt.com · +1 868 466-CODE · <a href="mailto:info@codecanvastt.com">info@codecanvastt.com</a><br>Book a call: <a href="https://calendly.com/keronlewis/30min">calendly.com/keronlewis/30min</a></p>
  </div>
  <div style="margin-top:auto; margin-bottom:12px">${LOGO(false)}</div>
  ${foot()}
</section>
</body></html>`;

fs.writeFileSync(OUT_HTML, html);
const page = await browser.newPage();
await page.goto('file://' + OUT_HTML, { waitUntil: 'networkidle0' });
await page.evaluate(() => document.fonts.ready);
await page.pdf({ path: OUT_PDF, format: 'Letter', printBackground: true, preferCSSPageSize: true, displayHeaderFooter: false });
await browser.close();
const size = fs.statSync(OUT_PDF).size;
console.log('PDF written:', OUT_PDF, size + ' bytes');
if (size > MAX_BYTES) {
  console.error(`The PDF is ${size} bytes, over ${MAX_BYTES}: lower the JPEG quality in capture.mjs.`);
  process.exit(1);
}
