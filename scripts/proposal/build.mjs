// Builds the client proposal (letter-size PDF) from the captured screenshots, in the site's own
// kraft, ink and cerulean palette and fonts. Usage: npm run proposal  (capture first; this step only needs Chrome)
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';

const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const SHOTS = path.resolve('dist/proposal/shots');
const chunks = JSON.parse(fs.readFileSync(path.join(SHOTS, 'chunks.json'), 'utf8'));
const OUT_HTML = path.resolve('dist/proposal/proposal.html');
const OUT_PDF = path.resolve('dist/proposal/Peter-and-Company-Website-Proposal.pdf');
const MAX_BYTES = 9.5 * 1024 * 1024;
const DATE = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
const TITLE = 'Acado St. Lucia (Peter & Company Distribution) – Trade Website Proposal';
const TESTS = process.env.TEST_COUNT ?? '49';
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
  ['Schibsted', 700, 'schibsted-grotesk-700-normal'], ['Schibsted', 800, 'schibsted-grotesk-800-normal'],
  ['Figtree', 400, 'figtree-400-normal'], ['Figtree', 600, 'figtree-600-normal'], ['Figtree', 700, 'figtree-700-normal'],
].map(([fam, w, p]) => `@font-face { font-family: '${fam}'; font-weight: ${w}; font-style: normal; src: url('${font(p)}') format('woff2'); }`).join('\n');

// The company's own logo from acadostlucia.com.
const LOGO = (white) => `<img class="logo" src="${img(path.resolve(`public/img/brand/acado-st-lucia${white ? '-white' : ''}.png`))}" alt="Acado St. Lucia">`;
// Rack-tag section label, as on the site.
const tag = (code, label) => `<p class="rack"><i>${esc(code)}</i><span>${esc(label)}</span></p>`;

/** Hand-written captions per slice (key = screen name, index = slice number). */
const CAPTIONS = {
  home: [
    'The hero: what the company distributes and to whom, the two trade actions (browse the catalogue, open a trade account), and the facts it publishes – trading since 1891, four divisions, the brands on its division pages, and the Acado Limited group.',
    'Quick routes for the five people who visit: a buyer browsing, a buyer ordering, a new trade customer, an overseas brand owner and a job seeker. Then the four divisions with their brand lists.',
    'Sample catalogue lines drawn as carton ends (shipping marks, the brand stamped on kraft) with a cases box and “Add to order” on every line. No prices anywhere: the trade desk confirms them.',
    'How ordering works in four steps, and the pharmaceutical division, which is clearly for licensed pharmacies, hospitals and clinics only.',
    'History since 1891, then the panels for brand owners and job seekers.',
    'The call to action for new trade customers, and the footer with the head-office number, address and social links.',
  ],
  brands: ['Every division on one page with its blurb, dated facts from The Voice (Hennessy for 35+ years, Carib Brewery exclusive), and the full brand list as text – 152 brands read from the company’s own division pages. No brand-owner logos are used.', 'The remaining divisions, including Pharmaceuticals, marked for licensed buyers only.'],
  division: ['A division page: the brands, the categories, and the catalogue lines in that division. Brand names are text only.', 'Division page, continued.'],
  catalogue: ['The trade catalogue: filter by division, brand and category or search, and add cases to the order list from any line. Sample listings, unpriced, using only brands the company lists.', 'Catalogue, continued: every line shows its case size and a “Sample listing” badge in the demo.'],
  order: ['The order list after a buyer added three lines: change quantities or remove a line, add items that aren’t in the catalogue, and tick pharmaceuticals only with an approved licensed account.', 'Who is ordering, the trade account number (optional; typed in any format), district, delivery address and a preferred working day. The buyer gets a reference number on screen and by email.'],
  'trade-account': ['The trade account application, step by step: who is applying, the business, trade licence and VAT, and the extra licence details pharmacies and clinics must give.', 'Cash-on-delivery or credit terms with trade references, then the trade licence, VAT certificate and owner ID uploads. They are stored privately and only credit control can open them.', 'Consent, the declaration and send.'],
  principals: ['For brand owners outside St Lucia: what a principal gets, and an enquiry form with categories and an optional presentation upload.', 'Principal enquiry, continued.'],
  careers: ['Vacancies (samples in the demo) and a general application. HR publishes real vacancies from the back office.', 'Each vacancy links to its own page and the application form; HR keeps general applications on file.'],
  about: ['The company’s history in its own words and the rename to Acado St. Lucia, its published vision and values, a six-date timeline, and its retail stores with their numbers.', 'About, continued.', 'About, continued.'],
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
    ['brands', 'Brands by division', ['The brand portfolio as searchable text, replacing logo grids that search engines and buyers cannot read.']],
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
  ['Public website', ['Phone-first, light and dark themes', 'Brands by division, as text', 'Trade catalogue, no prices', 'Order list and order requests', 'Delivery-day choice', 'Trade account and credit application', 'Principal enquiries', 'Careers with CV upload', 'Sitemap and structured data']],
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
  :root { --bg: #f6f4ef; --kraft: #e9e0cf; --kraft-2: #d9cbb1; --ink: #191a1c; --ink-2: #3f4246; --ink-3: #62666b; --line: #ddd6c8; --primary: #0b6e9f; --sky: #5cc6f2; --sky-soft: #e3f2fa; --stripe: #f26921; --crate: #1d1a16; }
  @page { size: Letter; margin: 0.5in 0.55in 0.5in; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; font-family: 'Figtree', 'Helvetica Neue', Arial, sans-serif; color: var(--ink); font-size: 10.4pt; line-height: 1.45; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  h1, h2, h3 { font-family: 'Schibsted', 'Helvetica Neue', Arial, sans-serif; font-weight: 800; color: var(--ink); margin: 0; line-height: 1.05; letter-spacing: -0.02em; }
  h3 { font-weight: 700; }
  b, strong { font-weight: 700; }
  .page { page-break-after: always; break-after: page; height: 10in; display: flex; flex-direction: column; overflow: hidden; }
  .page:last-child { page-break-after: auto; break-after: auto; }
  .page > * { flex-shrink: 0; }
  .eyebrow { font-size: 8pt; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--primary); margin: 0 0 4px; }
  .rack { display: inline-flex; margin: 0 0 10px; font-family: 'Schibsted', sans-serif; font-weight: 700; font-size: 7.6pt; letter-spacing: .14em; text-transform: uppercase; border-radius: 3px; overflow: hidden; box-shadow: inset 0 -3px 0 var(--stripe), 0 0 0 1.5px var(--ink); background: #fff; }
  .rack i { font-style: normal; background: var(--ink); color: #fff; padding: 5px 7px 7px; font-weight: 800; letter-spacing: .06em; } .rack span { padding: 5px 8px 7px; }
  .logo { height: 46px; width: auto; display: block; }
  /* kraft carton bands: cover and part dividers */
  .band { background: var(--kraft); background-image: repeating-linear-gradient(0deg, transparent 0 7px, rgba(160,135,95,.14) 7px 8px); border-radius: 6px; padding: 0.6in 0.6in 0.7in; position: relative; border: 2px solid var(--ink); }
  .band::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 8px; background: repeating-linear-gradient(90deg, var(--ink) 0 14px, transparent 14px 22px); opacity: .9; }
  .cover { justify-content: space-between; }
  .cover h1 { font-size: 38pt; line-height: 1.02; margin: 14px 0 14px; } .cover h1 em { font-style: normal; color: var(--primary); }
  .cover p.lead { color: var(--ink-2); font-size: 12.5pt; max-width: 5.8in; margin: 0; }
  .label { background: #fff; border: 2px solid var(--ink); border-radius: 4px; font-family: 'Schibsted', sans-serif; width: 3.2in; box-shadow: 0 8px 20px rgba(0,0,0,.12); }
  .label .row { display: flex; justify-content: space-between; padding: 5px 9px; border-bottom: 1.5px solid var(--ink); font-size: 7pt; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
  .label .big { padding: 6px 9px 2px; font-size: 22pt; font-weight: 800; line-height: 1; }
  .label .bars { height: 30px; margin: 6px 9px 9px; background: repeating-linear-gradient(90deg, var(--ink) 0 2px, transparent 2px 4px, var(--ink) 4px 5px, transparent 5px 8px, var(--ink) 8px 11px, transparent 11px 12px); }
  .cover .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; border-top: 1.5px solid var(--ink); padding-top: 14px; font-size: 9.3pt; color: var(--ink-2); margin-bottom: 18px; }
  .cover .meta b { display: block; font-size: 7.6pt; letter-spacing: .14em; text-transform: uppercase; margin-bottom: 4px; color: var(--primary); }
  .cover .meta .n { color: var(--ink); font-weight: 700; font-size: 10.5pt; }
  .divider { justify-content: center; }
  .divider h1 { font-size: 38pt; margin: 4px 0 16px; } .divider p { color: var(--ink-2); font-size: 12pt; max-width: 6in; margin: 0; }
  .divider ol { color: var(--ink-2); columns: 2; font-size: 10pt; margin: 22px 0 0; padding-left: 18px; } .divider li { break-inside: avoid; margin: 3px 0; }
  /* text pages */
  .prose h1 { font-size: 26pt; margin-bottom: 10px; }
  .prose h2 { font-size: 14.5pt; margin: 14px 0 6px; }
  .prose p { margin: 0 0 8px; }
  .prose ul.list { margin: 0 0 8px; padding-left: 17px; } .prose ul.list li { margin: 2px 0; }
  .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .card { border: 1.5px solid var(--ink); border-radius: 5px; padding: 11px 13px; background: #fff; break-inside: avoid; box-shadow: inset 0 3px 0 var(--kraft-2); }
  .card h3 { font-size: 12pt; margin-bottom: 5px; }
  .card ul { margin: 0; padding-left: 15px; font-size: 9.3pt; } .card li { margin: 1.5px 0; }
  .evidence { border-left: 4px solid var(--stripe); background: #fbf3ea; border-radius: 0 6px 6px 0; padding: 9px 12px 9px 28px; margin: 8px 0 10px; }
  .evidence li { margin: 3px 0; }
  .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 9px; margin: 12px 0; } .stat { background: var(--crate); color: #fff; border-radius: 5px; padding: 11px 12px; } .stat b { display: block; font-family: 'Schibsted', sans-serif; font-weight: 800; font-size: 24pt; line-height: 1; color: #fff; } .stat span { display: block; margin-top: 4px; font-size: 8.4pt; color: rgba(255,255,255,.82); line-height: 1.3; }
  table.feat { width: 100%; border-collapse: collapse; font-size: 9.4pt; } table.feat td { border-bottom: 1px solid var(--line); padding: 9px 6px; vertical-align: top; } table.feat td:first-child { width: 20%; font-family: 'Schibsted', sans-serif; font-weight: 700; font-size: 12pt; }
  .pill-row { display: flex; flex-wrap: wrap; gap: 5px; } .pill { border: 1px solid #b9dcee; background: var(--sky-soft); color: #0a4f72; border-radius: 3px; padding: 2px 8px; font-size: 8.6pt; font-weight: 600; }
  table.terms { width: 100%; border-collapse: collapse; font-size: 9.4pt; margin: 4px 0 8px; } table.terms th, table.terms td { text-align: left; vertical-align: top; padding: 4px 8px; border-bottom: 1px solid var(--line); } table.terms th { width: 31%; font-weight: 700; }
  table.terms thead th { font-size: 8pt; letter-spacing: .1em; text-transform: uppercase; color: var(--ink-3); border-bottom: 1.5px solid var(--ink); }
  .price { display: grid; grid-template-columns: 1.3fr 1fr; gap: 12px; align-items: stretch; margin: 6px 0 10px; }
  .price .big { background: var(--crate); color: #fff; border-radius: 6px; padding: 14px 16px; } .price .big b.amt { font-family: 'Schibsted', sans-serif; font-weight: 800; font-size: 27pt; line-height: 1; display: block; margin-bottom: 6px; } .price .big span { color: rgba(255,255,255,.86); font-size: 9.6pt; }
  .price .year { border: 2px solid var(--ink); border-radius: 6px; padding: 12px 14px; background: #fff; box-shadow: inset 0 -4px 0 var(--stripe); } .price .year p { margin: 0 0 4px; } .price .year .k { font-size: 7.8pt; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--primary); } .price .year .v { font-family: 'Schibsted', sans-serif; font-weight: 800; font-size: 15pt; line-height: 1.15; }
  .two { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .two ul { margin: 0; padding-left: 16px; font-size: 9.4pt; } .two li { margin: 2.5px 0; }
  .weeks { list-style: none; margin: 6px 0 10px; padding: 0; } .weeks li { display: grid; grid-template-columns: 0.9in 1fr; gap: 10px; padding: 6px 0; border-bottom: 1px solid var(--line); } .weeks b { font-family: 'Schibsted', sans-serif; font-weight: 800; font-size: 12.5pt; }
  .next li { margin: 5px 0; }
  .contact { margin-top: 14px; background: var(--kraft); border: 2px solid var(--ink); border-radius: 6px; padding: 14px 16px; box-shadow: inset 0 -4px 0 var(--stripe); } .contact p { margin: 2px 0; } .contact a { color: var(--primary); }
  /* screenshot pages */
  .shot-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; border-bottom: 2px solid var(--ink); padding-bottom: 6px; margin-bottom: 7px; }
  .shot-head h2 { font-size: 18pt; } .url { font-family: Menlo, Consolas, monospace; font-size: 8pt; color: var(--ink-3); margin: 0; white-space: nowrap; }
  .bullets { margin: 0 0 7px; padding-left: 16px; font-size: 9.3pt; } .bullets li { margin: 1px 0; }
  figure { margin: 0; display: flex; flex-direction: column; min-height: 0; flex: 1 1 auto; }
  figure img { display: block; max-width: 100%; min-height: 0; flex: 0 1 auto; object-fit: contain; object-position: left top; border: 1px solid var(--line); border-radius: 4px; align-self: flex-start; }
  .item { display: flex; flex-direction: column; flex: none; } .item + .item { margin-top: 16px; } .item figure { flex: none; } .item figure img { max-width: none; max-height: none; flex: none; }
  .sub-head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; border-bottom: 1px solid var(--line); padding-bottom: 4px; margin-bottom: 6px; } .sub-head h3 { font-size: 13.5pt; }
  .cap { margin: 6px 0 0; font-size: 8.9pt; line-height: 1.38; color: var(--ink-2); border-left: 3px solid var(--stripe); padding-left: 8px; flex: none; }
  .phones { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin: 14px 0.15in 0; } .phones figure { display: block; } .phones img { display: block; width: 100%; height: auto; border-radius: 16px; border: 6px solid var(--crate); } .phones figcaption { text-align: center; font-size: 9pt; color: var(--ink-3); margin-top: 6px; }
  footer.foot { margin-top: auto; padding-top: 8px; font-size: 7.8pt; color: #8a8d91; display: flex; justify-content: space-between; flex: none; }
`;

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--allow-file-access-from-files'] });
const measured = await measure(browser, SECTIONS);
const packed = Object.fromEntries(SECTIONS.map((sec) => [sec.id, packSection(sec, measured)]));
const screenCount = SECTIONS.reduce((t, s) => t + s.screens.length, 0) + PHONES.flat().length;

const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><title>${esc(TITLE)}</title>
<style>${STYLE}</style></head><body>

<section class="page band cover">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px">
    ${LOGO(false)}
    <div class="label"><div class="row"><span>Ship from</span><span>Port of Spain · TT</span></div><div class="big">Proposal</div><div class="row" style="border-bottom:0"><span>To</span><span>Cul De Sac · LC</span></div><div class="bars"></div></div>
  </div>
  <div>
    ${tag('00', `Website proposal · ${DATE}`)}
    <h1>A trade website that <em>takes orders</em>, opens accounts and hires, for Acado St. Lucia</h1>
    <p class="lead">A new acadostlucia.com for the shops, bars, hotels, pharmacies and clinics that buy from you: a brand portfolio and trade catalogue, order requests, trade account and credit applications with private document upload, principal enquiries, careers, and a back office for the trade desk, credit control and HR – for US$1,000 a month, with no charge for design and development.</p>
  </div>
  <div class="meta">
    <div><b>Prepared for</b><span class="n">Acado St. Lucia Limited</span><br>formerly Peter &amp; Company Distribution<br>Cul De Sac, P.O. Box 84-85, Castries, Saint Lucia</div>
    <div><b>Prepared by</b><span class="n">Keron Lewis, Code Canvas Consultants Ltd</span><br>Port of Spain · codecanvastt.com<br>+1 868 466-CODE · info@codecanvastt.com</div>
  </div>
</section>

<section class="page prose">
  ${tag('01', 'Executive summary')}
  <h1>Why rebuild now</h1>
  <p>Peter &amp; Company has traded in Castries since 1891, and as Acado St. Lucia it is one of the island’s largest distributors of fast-moving consumer goods: wines and spirits, Carib and the Brewery range, food, household and personal care, and pharmaceuticals, from Cul De Sac to supermarkets, minimarts, bars, hotels and pharmacies island-wide. The website does not yet do any of that work. When we checked acadostlucia.com on 26 October 2026:</p>
  <ul class="list evidence">
    <li>The brand portfolio is shown as <b>logo grids</b> on four division pages – images that buyers cannot search and search engines cannot read.</li>
    <li>There is <b>no catalogue, no price list request and no way to send an order</b>. A buyer still has to phone or wait for a rep.</li>
    <li>There is <b>no trade account or credit application</b>. Licences, VAT certificates and IDs travel on paper or by email attachment.</li>
    <li>The contact page has the head-office number and a general contact form, but <b>no email address</b>; we found no online route for brand owners looking for a distributor, or for a job application with a CV.</li>
  </ul>
  <p>This proposal turns the website into a working trade channel. A buyer finds a line, adds cases to an order list and sends it with a delivery day; the trade desk confirms prices and availability by phone. A new customer applies for cash-on-delivery or credit terms and uploads their documents, which only credit control can open. Brand owners and job seekers each have a proper front door. A complete working demo has been built from the company’s own published information and can be shown live.</p>
  <div class="stat-grid">
    <div class="stat"><b>${screenCount}</b><span>screens in this proposal, all from the working demo</span></div>
    <div class="stat"><b>152</b><span>brands, read from your own division pages, as text</span></div>
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
  <p>A buyer adds cases from the catalogue (or types items that are not listed), quotes their trade account number if they have one, and chooses a working day. The trade desk is alerted at once; the overview shows any order still waiting after two hours. The desk calls or messages the buyer to confirm prices, stock and the day, then moves the request through Confirmed, Out for delivery and Delivered. Nothing is charged online and nothing ships until the buyer confirms.</p>
  <h2>Pharmaceuticals stay business-to-business</h2>
  <p>The pharmaceutical range is never listed publicly. Pharmaceutical orders are accepted only from an approved pharmacy or clinic account with its licence on file, and there is no sale of medicines to the public anywhere on the site.</p>
  <h2>Content and data in the demo</h2>
  <p>Company facts come from acadostlucia.com (home, who we are, the four division pages, contact) and from The Voice St Lucia (the February 2025 rename, the Carib Brewery and El Dorado agencies, the Hennessy relationship, the 2020 Diquez award). The logo is the company’s own; the photographs are credited Wikimedia Commons images to be replaced with your warehouse, fleet and team. <b>Catalogue lines, vacancies, accounts and requests in the screenshots are demo records</b>, marked as samples, and no prices, stock levels, staff names or figures are invented.</p>
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
  <p style="margin:0 0 4px">${i ? 'Owners apply from the shop counter with a phone photo of their licence, and reps can check and call back an order from the road.' : 'Most buyers order from the shop floor on a phone. A bar with Call, Catalogue and the order list stays at the bottom of every public page.'}</p>
  <div class="phones">${row.map(([k, c]) => `<figure><img src="${img(chunks[k].chunks[0])}" alt="${esc(c)}"><figcaption>${esc(c)}</figcaption></figure>`).join('')}</div>
  ${foot()}
</section>`).join('')}

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
    <li>Photos of the warehouse, fleet and team, or time for a short photo session at Cul De Sac.</li>
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
    <p style="font-family:'Schibsted',sans-serif;font-weight:800;font-size:16pt">Keron Lewis</p>
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
console.log('PDF written:', OUT_PDF, Math.round(size / 1024 / 1024 * 10) / 10 + ' MB');
if (size > MAX_BYTES) {
  console.error(`The PDF is over ${MAX_BYTES / 1024 / 1024} MB: lower the JPEG quality in capture.mjs.`);
  process.exit(1);
}
