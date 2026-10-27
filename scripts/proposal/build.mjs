// Builds the client proposal (letter-size PDF) from the captured screenshots, in Hadeed Motors' own
// palette and fonts. Usage: npm run proposal (or node scripts/proposal/build.mjs after a capture).
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';

const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ROOT = path.resolve('.');
const SHOTS = path.resolve('dist/proposal/shots');
const chunks = JSON.parse(fs.readFileSync(path.join(SHOTS, 'chunks.json'), 'utf8'));
const OUT_HTML = path.resolve('dist/proposal/proposal.html');
const OUT_PDF = path.resolve('dist/proposal/Hadeed-Motors-Website-Proposal.pdf');
const DATE = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
const TESTS = process.env.TEST_COUNT ?? '65';
const img = (f) => 'file://' + f;
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;');

// The site's self-hosted fonts (built by the Vite fonts plugin into public/build/assets).
const ASSETS = path.join(ROOT, 'public/build/assets');
const font = (prefix) => {
  const f = fs.readdirSync(ASSETS).find((n) => n.startsWith(prefix) && n.endsWith('.woff2'));
  if (!f) throw new Error(`Font ${prefix} not found in public/build/assets; run npm run build first.`);
  return 'file://' + path.join(ASSETS, f);
};
const FONTS = [
  ['Unbounded', 500, 'unbounded-500-normal'], ['Unbounded', 700, 'unbounded-700-normal'],
  ['Hanken Grotesk', 400, 'hanken-grotesk-400-normal'], ['Hanken Grotesk', 500, 'hanken-grotesk-500-normal'],
  ['Hanken Grotesk', 600, 'hanken-grotesk-600-normal'], ['Hanken Grotesk', 700, 'hanken-grotesk-700-normal'],
].map(([fam, w, p]) => `@font-face { font-family: "${fam}"; font-weight: ${w}; font-style: normal; src: url("${font(p)}") format("woff2"); }`).join('\n');

// Hadeed Motors' own HM mark (the file hadeedmotors.com uses, kept in public/img/brand), on the
// same white plate the site puts it on.
const LOGO = 'data:image/png;base64,' + fs.readFileSync(path.join(ROOT, 'public/img/brand/hadeed-hm.png')).toString('base64');
const MARK = (h = 34, ring = 'rgba(255,255,255,.35)') => `<span class="mark" style="height:${h}px; padding:0 ${Math.round(h * 0.2)}px; box-shadow: inset 0 0 0 1px ${ring}"><img src="${LOGO}" alt="" style="height:${Math.round(h * 0.6)}px"></span>`;
const WORDMARK = (dark = true) => `<div class="brand">${MARK(dark ? 40 : 30, dark ? 'rgba(255,255,255,.35)' : '#8a939d')}<div><span class="n ${dark ? '' : 'ink'}">Hadeed Motors</span><span class="s ${dark ? '' : 'ink'}">St John's · Since 1972</span></div></div>`;
const plate = (code, label, dark = false) => `<p class="plate ${dark ? 'dark' : ''}"><span class="c">${esc(code)}</span><span class="l">${esc(label)}</span></p>`;

/** Hand-written captions per slice (key = screen name, index = slice number), from what each slice shows. */
const CAPTIONS = {
  home: [
    'Hero on Hadeed Motors\' own showroom photograph, the three main actions, the odometer-style facts strip (opened 1972, two franchises, the size of the line-up, pre-owned cars on the lot) and the "I\'d like to…" shortcuts to every request form.',
    'The new line-up: six featured Nissan and Suzuki models with brand filters, each linking to its model page.',
    '"On the lot this week": featured pre-owned stock pulled live from the inventory. These are sample listings; no prices are invented, so they read "Call for price".',
    'Finance is upstairs: Finance & Development Co. and the three steps from pre-qualifying to driving home.',
    'Service and parts, with the workshop hours read from the same settings as the booking calendar.',
    'Heritage timeline from 1972 and the visit block with opening hours and directions.',
    'Footer: phone, email, links for buying and owning, the showroom address and hours, and the staff sign-in.',
  ],
  lineup: [
    'New vehicles: brand filter in the page header, then the Nissan line-up.',
    'The Suzuki line-up: every model sold new, each with body type and a one-line summary.',
    'Closing call to action: book a test drive to compare two models.',
  ],
  model: [
    'A model page (Suzuki Jimny): breadcrumb, test-drive and pre-qualify buttons, overview, gallery and an "at a glance" spec sheet with a call button (a WhatsApp button appears once you give us a WhatsApp number).',
    'Trims with their features from Hadeed Motors\' own model pages, then "on the lot now", which lists new units entered in the inventory (a sample unit here).',
    'More models from the same brand.',
  ],
  inventory: ['Pre-owned stock with filters for make, body type, price, year and transmission, and sorting. It works with or without JavaScript. All six cars are sample listings.'],
  vehicle: [
    'Vehicle page header: stock number, breadcrumb, availability and the sample-listing badge.',
    'Photo gallery with arrows and thumbnails, odometer readout, full spec sheet, test-drive and call buttons, and a finance panel.',
    'Similar vehicles by body type or make.',
  ],
  finance: [
    'Finance with FDC: four steps to your keys. The monthly estimator stays hidden until FDC publishes an indicative rate in Settings, so the page asks customers to pre-qualify instead.',
    'Documents FDC asks for, taken from FDC\'s published requirements, then FDC\'s hours, contacts and common questions.',
  ],
  service: [
    'Service and parts: the hero photograph and the three things the workshop does.',
    'Online service booking, part 1: vehicle details and the type of work, beside the workshop hours and a parts shortcut.',
    'Part 2: drop-off days with the places left on each, fully booked days greyed out (from sample bookings), the drop-off time and contact details.',
  ],
  about: [
    'About: the company story from hadeedmotors.com and the milestones from 1972.',
    'Links to the group companies, the line-up and the showroom.',
  ],
  contact: [
    'Contact page header on a St John\'s harbour photograph.',
    'Department directory, opening hours, Google Maps link and the message form.',
  ],
  'test-drive': [
    'Test-drive booking header.',
    'The car is pre-selected when the customer comes from a vehicle page. Only times with space left are offered, and closing time and public holidays are respected.',
  ],
  'finance-apply': [
    'Finance pre-qualification, part 1: contact details, residency and employment.',
    'Part 2: income band, deposit, preferred term and car, optional ID and pay-slip upload, and explicit consent to share with FDC. Bank or card numbers are never asked for and are refused if sent.',
  ],
  'trade-in': [
    'Trade-in valuation: the customer\'s car, its condition, money still owed, up to six photos and the car they would like next.',
    'Contact details and submit. Every form ends on a thank-you page with a reference number, also sent by email.',
  ],
  parts: [
    'Parts request header.',
    'VIN (checked as 17 characters, never I, O or Q), the part, quantity and an optional photo, with tips on where to find the VIN.',
  ],
  'admin-login': ['Staff sign-in. Accounts that have been switched off are refused and signed out.'],
  'admin-dashboard': [
    'Overview: the number of leads waiting more than two hours for a call, headline tiles, leads per day for 30 days, leads by type and the sales pipeline. Figures are from the demo data. The setup reminder at the top stays until the notification inbox is configured.',
    'The call queue (oldest new lead first) and the next three days of appointments.',
  ],
  'admin-inbox': [
    'Unified leads inbox: status tabs with counts, filters by type and assignee, "waiting over 2h", search by name, phone, email or reference, and CSV export.',
    'Every request from all six forms in one list, with type, vehicle, appointment, status and assignee.',
  ],
  'admin-lead': ['A test-drive lead: one-tap call, WhatsApp and email, the car requested with its stock details, status, assignment, appointment and the activity timeline.'],
  'admin-lead-finance': ['The same screen for the finance role: employment, income band and deposit, the consent time, and the ID and pay slips. Only the finance role can open the documents, and every opening is logged on the timeline.'],
  'admin-service': ['Workshop calendar: each day\'s bookings against the daily capacity, and the selected day\'s job list.'],
  'admin-finance': ['FDC pipeline: applications in columns by decision stage (received, reviewing, needs documents, pre-qualified, declined, withdrawn). Changing a card\'s stage moves it.'],
  'admin-vehicles': ['Inventory: every unit with photo, stock number, odometer, price, photo count and a quick status change (available, reserved, sold).'],
  'admin-vehicle-edit': ['Vehicle editor: details, description, features, multi-photo upload with cover and delete, price, status and website visibility. Photos are resized automatically.'],
  'admin-users': ['Staff accounts with four roles. Each role sees only the lead types it works.'],
  'admin-settings': ['Settings: the indicative finance rate (blank keeps the estimator hidden) and the daily workshop capacity.'],
};

/** Screen definitions: section, key, title, bullets. */
const SECTIONS = [
  { id: 'public', eyebrow: 'Part 1', code: '01', title: 'The public website', intro: 'A new hadeedmotors.com on valid HTTPS, written from the facts on the current site, hadeedmotors.com\'s own model pages and FDC\'s published requirements. Every page works on a phone, and every inner page opens with the same graphite header.', screens: [
    ['home', 'Home page', ['Hadeed Motors\' own showroom photograph, the dealership\'s history since 1972, and the line-up, pre-owned stock, finance and service in one scroll.', 'Six shortcuts ("I\'d like to…") lead straight to the request forms that the current site\'s menu points to.', 'On phones, a bar with Call and Test drive follows visitors on every page (WhatsApp joins it once you have a WhatsApp number).']],
    ['lineup', 'New vehicles', ['Every Nissan and Suzuki model sold new, filterable by brand.', 'Old links such as index.html?page=vehicle&model=jimny redirect to the matching new page, so bookmarks and search results keep working.']],
    ['model', 'Model page', ['Trims and features, spec sheet and gallery for each model.', 'New stock entered in the back office appears automatically under "on the lot now".', 'No prices are shown for new models: "Call for today\'s price", as the line-up changes by shipment.']],
    ['inventory', 'Pre-owned stock', ['Filters and sorting for make, body type, price, year and transmission, all searchable by Google.', 'Reserved cars are badged, and sold cars stay listed as Sold for 7 days, then drop off.']],
    ['vehicle', 'Vehicle page', ['Multi-photo gallery, odometer, full spec sheet and clear ways to act: book a test drive or call.', 'A monthly estimate appears automatically once FDC publishes an indicative rate.']],
    ['finance', 'Finance with FDC', ['Explains the FDC process and the documents to bring.', 'An estimator that gives the same figure with or without JavaScript, hidden until a rate is published.']],
    ['service', 'Service and online booking', ['Customers see which days have space and claim a drop-off place. The daily capacity is set by the workshop.', 'Weekends and public holidays are never offered.']],
    ['about', 'About Hadeed Motors', ['The 1972 story, the founding philosophy and the milestones, from the current site.']],
    ['contact', 'Contact and hours', ['Directory, opening hours, directions and a message form that lands in the inbox with a reference number.']],
  ]},
  { id: 'requests', eyebrow: 'Part 2', code: '02', title: 'Online requests', intro: 'Five request forms replace the template filler on today\'s home page: test drive, service booking (shown in Part 1), parts, trade-in valuation and finance pre-qualification, plus the contact form. Each one gives the customer a reference number on screen and by email, and puts the request in the sales desk inbox.', screens: [
    ['test-drive', 'Book a test drive', ['Any new model or any car in stock, on a weekday, at a time that still has space (two drives per slot by default).', 'The driving-licence confirmation is recorded on the lead.']],
    ['finance-apply', 'Finance pre-qualification', ['Income band, deposit, term and car: enough for FDC to call with options. It is not a credit application.', 'ID and pay slips are stored privately, outside the public website, and only FDC\'s finance staff can open them.']],
    ['trade-in', 'Trade-in valuation', ['Up to six phone photos (iPhone HEIC accepted) and the details a consultant needs for a first figure.']],
    ['parts', 'Parts request', ['A checked VIN, the part and a photo, so the parts counter can quote on the first call.']],
  ]},
  { id: 'admin', eyebrow: 'Part 3', code: '03', title: 'The sales desk', intro: 'A private, role-based back office for sales, service, finance and management. Every web lead arrives with a reference number, an owner and a timeline, and anything not called within two hours is flagged as overdue.', screens: [
    ['admin-login', 'Staff sign-in', ['Four roles: administrator, sales, service and parts, and finance.', 'Accounts are switched off rather than deleted, so lead history keeps its names.']],
    ['admin-dashboard', 'Overview', ['Leads with an overdue counter first, because the dealer who calls back first is the one customers visit.', 'Median time to first call, test drives this week, cars available, today\'s service bookings and finance applications to review.']],
    ['admin-inbox', 'Leads inbox', ['Each role sees only the lead types it works: sales sees test drives, trade-ins, finance and enquiries; service sees bookings, parts and enquiries.', 'CSV export for management reporting.']],
    ['admin-lead', 'Lead detail', ['Status, assignment, appointment changes and notes in one place, with a full timeline.', 'Logging a call moves a new lead to Contacted and records the response time.']],
    ['admin-lead-finance', 'Finance application (finance role)', ['Financial details are visible to finance and administrators; ID and pay slips open for the finance role only, not even administrators.']],
    ['admin-service', 'Service calendar', ['Month view against daily capacity, so the workshop sees full days at a glance.']],
    ['admin-finance', 'FDC pipeline', ['A board for the finance desk, separate from the sales status.']],
    ['admin-vehicles', 'Inventory', ['Add, edit, reserve or mark sold; changes show on the website immediately.']],
    ['admin-vehicle-edit', 'Vehicle editor', ['Up to 24 photos per car; phone photos are resized automatically to fast WebP copies.']],
    ['admin-users', 'Staff and roles', ['Add staff, change roles and switch accounts off.']],
    ['admin-settings', 'Settings', ['Settings the dealership controls without a developer.']],
  ]},
];
const SCREEN_COUNT = SECTIONS.reduce((n, s) => n + s.screens.length, 0);

const FEATURES = [
  ['Public website', ['New-vehicle line-up and model pages', 'Pre-owned inventory with filters and galleries', 'Finance page with an FDC estimator', 'Service page with live day availability', 'About, group companies and contact', 'Redirects from the old site\'s links', 'Sitemap and Google structured data', 'Phone action bar on every page']],
  ['Online requests', ['Test drive with slot capacity', 'Service booking with daily capacity', 'Parts request with VIN check and photo', 'Trade-in with up to six photos', 'Finance pre-qualification with private ID and pay-slip upload', 'Contact form', 'Reference number on screen and by email', 'Office alert email for every request']],
  ['Sales desk', ['Dashboard with overdue-lead counter', 'Unified inbox, filters and CSV export', 'Assignment, status, notes and timeline', 'Workshop calendar against capacity', 'FDC finance pipeline', 'Inventory with multi-photo upload', 'Four staff roles', 'Settings for the rate and workshop capacity']],
  ['Platform', ['Laravel 13 on PHP 8.3', 'Valid HTTPS throughout', 'Customer documents stored privately', 'Finance documents for the finance role only', 'Rate-limited, spam-protected forms', 'Light and dark themes', `${TESTS} automated tests`, 'Packaged for one-zip installation']],
];

/**
 * Pack a screen's slices onto pages. A slice's height ÷ width is its share of a page. Every slice keeps
 * its own caption; slices never break across pages and keep their order. The layout is chosen by cost:
 * one point per page, one more for a page left about a third empty, half a point for shrinking slices
 * below 68% of a 1440px screen to share a page (never below 55%), and a quarter point for moving the
 * bullets off the first page. So a short slice joins its neighbour instead of sitting alone on a
 * half-blank page; ties go to the layout whose emptiest page is fullest.
 */
function pages(c, hasBullets) {
  const sizes = c.sizes ?? c.chunks.map(() => [1440, 1440]);
  // Page capacity as image height ÷ text width: about 1.22 below the heading, 0.98 when bullets sit on top.
  // The estimate leaves out caption height, so a page counts as gappy at 25% estimated (about 35% printed).
  const CAP = 0.08, GOOD_SCALE = 0.68, MIN_SCALE = 0.55, GAP = 0.25;
  const ratios = sizes.map(([w, h]) => h / w);
  const page = (from, to, bullets) => {
    const budget = bullets ? 0.98 : 1.22;
    const sum = ratios.slice(from, to).reduce((a, r) => a + r, 0);
    const n = to - from;
    const scale = Math.min(1, (budget - CAP * n) / sum);
    const readable = scale * 1440 / sizes[from][0]; // narrower captures carry larger type
    if (n > 1 && readable < MIN_SCALE) return null;
    const empty = 1 - (sum * scale + CAP * n) / budget;
    return { items: [...Array(n).keys()].map((k) => from + k), bullets, budget, scale, empty, cost: 1 + (empty > GAP ? 1 : 0) + (readable < GOOD_SCALE ? 0.5 : 0) + (bullets && from > 0 ? 0.25 : 0) };
  };
  // best(i, owed) = cheapest packing of slices i..end, where owed means the bullets still need a page.
  const n = ratios.length;
  const memo = new Map();
  const best = (i, owed) => {
    if (i === n) return owed ? null : { list: [], cost: 0, worst: 0 };
    const key = i + ':' + owed;
    if (memo.has(key)) return memo.get(key);
    let pick = null;
    for (const here of owed ? [true, false] : [false]) {
      for (let j = i + 1; j <= n; j++) {
        const pg = page(i, j, here);
        if (!pg) break;
        const rest = best(j, owed && !here);
        if (!rest) continue;
        const cand = { list: [pg, ...rest.list], cost: pg.cost + rest.cost, worst: Math.max(pg.empty, rest.worst) };
        if (!pick || cand.cost < pick.cost - 1e-9 || (Math.abs(cand.cost - pick.cost) < 1e-9 && cand.worst < pick.worst - 1e-9)) pick = cand;
      }
    }
    memo.set(key, pick);
    return pick;
  };
  return best(0, hasBullets).list;
}

function caption(key, i, c) {
  const text = CAPTIONS[key]?.[i];
  if (!text) return '';
  const n = c.chunks.length > 1 ? `${i + 1}/${c.chunks.length} · ` : '';
  return `<figcaption class="cap"><span class="n">${esc(n)}</span>${esc(text)}</figcaption>`;
}

function screenPages(sec, [key, title, bullets]) {
  const c = chunks[key];
  if (!c) throw new Error(`Screenshot missing: ${key}`);
  return pages(c, bullets.length > 0).map((p, pi) => `
    <section class="page shot">
      <header class="shot-head">
        <div><p class="eyebrow">${esc(sec.title)}${pi ? ' · continued' : ''}</p><h2>${esc(title)}</h2></div>
        <p class="url">${esc(c.url)}</p>
      </header>
      ${p.bullets ? `<ul class="bullets">${bullets.map((b) => `<li>${esc(b)}</li>`).join('')}</ul>` : ''}
      ${p.items.map((i) => `<figure><img style="width:${(p.scale * 100).toFixed(1)}%" src="${img(c.chunks[i])}" alt="${esc(title)}">${caption(key, i, c)}</figure>`).join('')}
      ${foot()}
    </section>`).join('');
}

const foot = () => `<footer class="foot"><span class="fl">${MARK(16, '#d5d9de')}Hadeed Motors · Website proposal</span><span>Code Canvas Consultants Ltd · ${esc(DATE)}</span></footer>`;
const notes = (list) => `<div class="phone-notes">${list.map(([t, d]) => `<div><b>${esc(t)}</b>${esc(d)}</div>`).join('')}</div>`;
const phones = (list) => `<div class="phones">${list.map(([k, c]) => `<figure><img src="${img(chunks[k].chunks[0])}" alt="${esc(c)}"><figcaption>${esc(c)}</figcaption></figure>`).join('')}</div>`;

const html = `<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Hadeed Motors – Website Proposal</title>
<style>
  ${FONTS}
  @page { size: Letter; margin: 0.5in 0.6in 0.55in; }
  :root { --graphite: #16191d; --graphite-950: #0e1013; --gm: #2a3038; --bg: #f6f6f3; --surface-2: #eceeef; --ink: #16191d; --ink-2: #3d444c; --ink-3: #5b6470; --line: #dcdfe2; --coral: #f0643c; --coral-text: #b13d17; --coral-soft: #fde9e0; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; font-family: "Hanken Grotesk", system-ui, sans-serif; color: var(--ink); font-size: 10.5pt; line-height: 1.45; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  h1, h2, h3, .display { font-family: "Unbounded", system-ui, sans-serif; font-weight: 700; letter-spacing: -0.03em; color: var(--ink); margin: 0; line-height: 1.05; }
  h3 { font-family: "Hanken Grotesk", system-ui, sans-serif; letter-spacing: 0; font-size: 12pt; }
  .page { page-break-after: always; break-after: page; height: 9.95in; display: flex; flex-direction: column; overflow: hidden; }
  .page:last-child { page-break-after: auto; }
  .eyebrow { font-size: 8pt; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--coral-text); margin: 0 0 5px; }
  .muted { color: var(--ink-3); }
  /* number-plate label, as on the site */
  .plate { align-self: flex-start; display: inline-flex; border: 1.5px solid currentColor; border-radius: 6px; padding: 2px; font-size: 7.5pt; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; line-height: 1; color: var(--ink-2); margin: 0 0 12px; }
  .plate span { display: inline-flex; align-items: center; padding: 5px 7px 4px; border-radius: 3px; }
  .plate .c { background: var(--ink); color: var(--bg); font-family: "Unbounded"; font-weight: 500; letter-spacing: .04em; }
  .plate .l { box-shadow: inset 0 0 0 1px rgba(0,0,0,.18); margin-left: 2px; }
  .plate.dark { color: rgba(255,255,255,.85); } .plate.dark .c { background: var(--coral); color: var(--graphite); } .plate.dark .l { box-shadow: inset 0 0 0 1px rgba(255,255,255,.28); }
  .ticks { height: 12px; opacity: .35; background: repeating-linear-gradient(90deg, currentColor 0 2px, transparent 2px 0.42in) left bottom / 100% 12px no-repeat, repeating-linear-gradient(90deg, currentColor 0 1px, transparent 1px 0.084in) left bottom / 100% 6px no-repeat; }
  /* cover and dividers: the site's graphite hero band */
  .dark-page { background: var(--graphite); color: #fff; border-radius: 16px; padding: 0.6in 0.6in 0.5in; position: relative; overflow: hidden; }
  .dark-page h1 { color: #fff; }
  .cover { justify-content: space-between; background-image: linear-gradient(180deg, rgba(14,16,19,.55), rgba(14,16,19,.95) 62%), url("${img(path.join(ROOT, 'public/img/showroom-1920.webp'))}"); background-size: cover; background-position: 22% 0; }
  .cover h1 { font-size: 40pt; margin: 14px 0 16px; max-width: 6.2in; }
  .cover .accent { color: var(--coral); }
  .cover .lead { color: rgba(255,255,255,.85); font-size: 13pt; max-width: 5.8in; margin: 0; }
  .cover .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; border-top: 1px solid rgba(255,255,255,.18); padding-top: 16px; font-size: 9.5pt; color: rgba(255,255,255,.8); }
  .cover .meta b { display: block; color: #fff; font-size: 9pt; letter-spacing: .14em; text-transform: uppercase; margin-bottom: 4px; }
  .brand { display: flex; align-items: center; gap: 10px; } .brand .n { display: block; font-family: "Unbounded"; font-weight: 700; font-size: 17pt; color: #fff; line-height: 1; letter-spacing: -0.02em; } .brand .s { display: block; margin-top: 4px; font-size: 7pt; font-weight: 600; letter-spacing: .16em; text-transform: uppercase; color: rgba(255,255,255,.7); }
  .mark { display: inline-flex; align-items: center; flex: none; background: #fff; border-radius: 5px; } .mark img { display: block; width: auto; }
  .brand .ink { color: var(--ink); } .brand .s.ink { color: var(--ink-3); }
  .divider { justify-content: center; }
  .divider h1 { font-size: 38pt; margin: 6px 0 18px; } .divider p.intro { color: rgba(255,255,255,.85); font-size: 12.5pt; max-width: 6in; margin: 0; }
  .divider ol { color: rgba(255,255,255,.85); columns: 2; font-size: 10pt; margin: 26px 0 0; padding-left: 18px; } .divider li { break-inside: avoid; margin: 3px 0; }
  .divider .note { margin-top: 22px; font-size: 9pt; color: rgba(255,255,255,.65); max-width: 6in; }
  .divider .ticks, .cover .ticks { position: absolute; left: 0; right: 0; bottom: 0; color: #fff; }
  /* text pages */
  .prose h1 { font-size: 24pt; margin-bottom: 12px; }
  .prose h2 { font-size: 13pt; margin: 16px 0 6px; letter-spacing: -0.01em; }
  .prose p { margin: 0 0 8px; }
  .prose ul.plain { margin: 0 0 8px; padding-left: 16px; } .prose ul.plain li { margin: 2px 0; }
  .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .card { border: 1px solid var(--line); border-radius: 12px; padding: 11px 13px; background: #fff; break-inside: avoid; }
  .card h3 { margin-bottom: 5px; }
  .card ul { margin: 0; padding-left: 15px; } .card li { margin: 2px 0; font-size: 9.6pt; }
  .evidence { border-left: 4px solid var(--coral); background: var(--bg); border-radius: 0 10px 10px 0; padding: 10px 14px 10px 30px; margin: 8px 0 10px; }
  .evidence li { margin: 3px 0; }
  .src { font-size: 8pt; color: var(--ink-3); }
  .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 12px 0 6px; }
  .stat { background: var(--graphite); color: #fff; border-radius: 12px; padding: 12px 12px 11px; }
  .stat b { display: inline-flex; gap: 3px; font-family: "Unbounded"; font-weight: 500; font-size: 20pt; line-height: 1; }
  .stat b i { font-style: normal; display: inline-block; min-width: .95em; text-align: center; padding: 3px 2px 2px; border-radius: 3px; background: linear-gradient(180deg,#0b0d10 0%,#2a3038 22%,#2f3640 50%,#2a3038 78%,#0b0d10 100%); }
  .stat b i.hl { background: linear-gradient(180deg,#8a2a0c 0%,#f0643c 26%,#ff7a4d 50%,#f0643c 74%,#8a2a0c 100%); color: var(--graphite); }
  .stat span { display: block; margin-top: 8px; font-size: 8.5pt; color: rgba(255,255,255,.78); line-height: 1.3; }
  table.feat { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
  table.feat th, table.feat td { border-bottom: 1px solid var(--line); padding: 8px 8px; text-align: left; vertical-align: top; }
  table.feat th { background: var(--surface-2); font-size: 8pt; text-transform: uppercase; letter-spacing: .1em; color: var(--ink-3); }
  .pill-row { display: flex; flex-wrap: wrap; gap: 5px; } .pill { border: 1px solid #f6c3ae; background: var(--coral-soft); color: var(--coral-text); border-radius: 999px; padding: 2px 9px; font-size: 8.5pt; font-weight: 600; }
  /* screenshot pages */
  .shot-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; border-bottom: 2px solid var(--ink); padding-bottom: 6px; margin-bottom: 7px; }
  .shot-head h2 { font-size: 16pt; } .url { font-family: Menlo, Consolas, monospace; font-size: 8pt; color: var(--ink-3); margin: 0; white-space: nowrap; }
  .bullets { margin: 0 0 7px; padding-left: 16px; font-size: 9.3pt; } .bullets li { margin: 1px 0; } .bullets li::marker { color: var(--coral); }
  figure { margin: 0 0 8px; display: block; text-align: center; }
  figure img { display: block; margin: 0 auto; border: 1px solid var(--line); border-radius: 6px; }
  .cap { text-align: left; margin: 5px 0 0; font-size: 8.6pt; line-height: 1.35; color: var(--ink-2); border-left: 3px solid var(--coral); padding-left: 8px; }
  .cap .n { font-weight: 700; color: var(--ink-3); }
  .phones { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 12px; }
  .phones figure { margin: 0; }
  .phones img { width: 100%; border-radius: 22px; border: 7px solid var(--graphite); }
  .phone-notes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 14px; }
  .phone-notes div { background: var(--bg); border-radius: 10px; padding: 9px 12px; font-size: 9pt; color: var(--ink-2); }
  .phone-notes b { display: block; color: var(--ink); font-size: 9.5pt; margin-bottom: 2px; }
  .phones figcaption { text-align: center; font-size: 9pt; color: var(--ink-2); margin-top: 6px; font-weight: 600; }
  /* investment */
  .price { display: grid; grid-template-columns: 1.45fr 1fr; gap: 14px; align-items: stretch; margin: 6px 0 4px; }
  .price .big { background: var(--graphite); color: #fff; border-radius: 14px; padding: 16px 18px; }
  .price .big .amt { white-space: nowrap; font-family: "Unbounded"; font-weight: 700; font-size: 26pt; line-height: 1; color: #fff; } .price .big .amt small { font-size: 11pt; font-weight: 500; color: rgba(255,255,255,.75); }
  .price .big p { color: rgba(255,255,255,.82); margin: 8px 0 0; font-size: 9.6pt; }
  .price .year { border: 2px solid var(--coral); border-radius: 14px; padding: 14px 16px; background: var(--coral-soft); }
  .price .year div { display: flex; justify-content: space-between; gap: 8px; padding: 4px 0; border-bottom: 1px dotted #e3a58c; font-size: 10pt; } .price .year div:last-child { border: 0; }
  .price .year b { font-family: "Unbounded"; font-weight: 500; }
  .terms li { margin: 3px 0; font-size: 9.6pt; }
  .sla { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 4px; } .sla td, .sla th { border-bottom: 1px solid var(--line); padding: 4px 6px; text-align: left; } .sla th { font-size: 7.8pt; letter-spacing: .1em; text-transform: uppercase; color: var(--ink-3); background: var(--surface-2); }
  .weeks { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 6px 0 10px; }
  .week { border: 1px solid var(--line); border-radius: 12px; padding: 10px 12px; background: #fff; }
  .week b { display: inline-block; font-family: "Unbounded"; font-weight: 500; font-size: 8.5pt; background: var(--ink); color: var(--bg); border-radius: 4px; padding: 3px 6px; margin-bottom: 5px; }
  .week p { margin: 0; font-size: 9.5pt; }
  footer.foot { margin-top: auto; padding-top: 6px; font-size: 7.8pt; color: #8a939d; display: flex; justify-content: space-between; border-top: 1px solid var(--line); align-items: center; } footer.foot .fl { display: inline-flex; align-items: center; gap: 6px; }
</style></head><body>

<section class="page dark-page cover">
  ${WORDMARK()}
  <div>
    ${plate('HM', 'Website proposal · ' + DATE, true)}
    <h1>A website that sells cars, <span class="accent">books the workshop</span> and follows up every lead.</h1>
    <p class="lead">A complete rebuild of hadeedmotors.com on valid HTTPS, with online test-drive, service, parts, trade-in and finance requests feeding one sales desk for sales, service and FDC.</p>
  </div>
  <div class="meta">
    <div><b>Prepared for</b>Hadeed Motors Ltd<br>Hadeed Motors Complex, Old Parham Road<br>St John's, Antigua</div>
    <div><b>Prepared by</b>Keron Lewis, Code Canvas Consultants Ltd<br>Port of Spain · codecanvastt.com<br>+1 868 466-CODE · info@codecanvastt.com</div>
  </div>
  <div class="ticks"></div>
</section>

<section class="page prose">
  ${plate('01', 'Executive summary')}
  <h1>Why rebuild now</h1>
  <p>Hadeed Motors has sold cars on Old Parham Road since 1972: Nissan since the Datsun days, Suzuki since 1995, with Finance &amp; Development Co. lending from the floor above since 1984. In 2016 Business View Caribbean described it as Antigua and Barbuda's largest auto dealer. Its website does not reflect that standing today. When we checked hadeedmotors.com on 23 October 2026:</p>
  <ul class="evidence">
    <li><b>The secure address does not work.</b> https://hadeedmotors.com fails because its certificate is issued to the hosting server (swmp-550-116.slc.westdc.net), not to hadeedmotors.com. The site only loads over plain HTTP, which browsers label "Not secure", including on its forms.</li>
    <li><b>Template filler is still live on the home page:</b> menu cards reading "Car 1 description 1" through "Car 5", panes titled "Slide 2" and "Slide 3", and the word "adssadf" in the header.</li>
    <li><b>Visible typos and a stray link:</b> "Finance &amp; Develpment Co." and "Caribbean Premoum Motors", and a "Vehicles" menu link that points to bbc.com.</li>
  </ul>
  <p class="src">Sources: the hadeedmotors.com home page and its HTTPS certificate, checked on 23 October 2026; Business View Caribbean, April 2016, p. 63.</p>
  <p>This proposal replaces the site with one that matches the dealership: a fast, mobile-first showroom for new and pre-owned stock; five online request flows that each give the customer a reference number; and a sales desk where every lead has an owner, a status and a two-hour call-back target. A working demo is already built and can be shown live, on a laptop or a phone, at your convenience.</p>
  <div class="stat-grid">
    <div class="stat"><b>${String(SCREEN_COUNT).split('').map((d) => `<i>${d}</i>`).join('')}</b><span>screens designed, built and shown in this proposal</span></div>
    <div class="stat"><b><i>0</i><i class="hl">6</i></b><span>online forms, each issuing a reference number</span></div>
    <div class="stat"><b><i>0</i><i>4</i></b><span>staff roles, each seeing only its own leads</span></div>
    <div class="stat"><b>${TESTS.split('').map((d) => `<i>${d}</i>`).join('')}</b><span>automated tests run before every release</span></div>
  </div>
  <h2>What Hadeed Motors gains</h2>
  <div class="cols">
    <div class="card"><h3>Customers</h3><ul><li>Book a test drive or a service in two minutes, from a phone</li><li>See which workshop days still have space</li><li>Pre-qualify with FDC without sharing bank details</li></ul></div>
    <div class="card"><h3>Sales, service and FDC</h3><ul><li>One inbox for every web request, split by role</li><li>Overdue leads flagged after two hours</li><li>Stock and photos updated by your own staff</li></ul></div>
  </div>
  ${foot()}
</section>

<section class="page prose">
  ${plate('02', 'At a glance')}
  <h1>What is included</h1>
  <table class="feat"><thead><tr><th style="width:20%">Area</th><th>Features</th></tr></thead><tbody>
    ${FEATURES.map(([a, f]) => `<tr><td><b>${esc(a)}</b></td><td><div class="pill-row">${f.map((x) => `<span class="pill">${esc(x)}</span>`).join('')}</div></td></tr>`).join('')}
  </tbody></table>
  <h2>How a lead flows</h2>
  <p>A customer books a test drive on a Jimny from the model page. The booking checks that the time is free, issues a reference such as <b>TD-26-00087</b>, emails the customer and alerts the sales inbox. In the sales desk the lead shows as New, with a call-back clock. A consultant assigns it, calls with one tap, logs the call and moves it through Appointment and Negotiating to Sold or Lost. A lost booking frees its slot for someone else. Service bookings follow the same path to the workshop calendar, and finance pre-qualifications go to the FDC pipeline, where only the finance team can open the ID and pay slips.</p>
  <h2>Content and imagery</h2>
  <p>The company facts, history, hours, model trims and FDC requirements are taken from hadeedmotors.com and fdcantigua.com. Nothing is invented: no prices, rates, stock, awards or testimonials. The hero is Hadeed Motors' own showroom photograph. The vehicle and Antigua photographs are licensed from Wikimedia Commons and credited on the site, ready to be replaced with your own. The demo stock and leads shown in these screenshots are clearly marked samples with fictitious names.</p>
  ${foot()}
</section>

${SECTIONS.map((sec) => `
<section class="page dark-page divider">
  ${plate(sec.code, sec.eyebrow, true)}
  <h1>${esc(sec.title)}</h1>
  <p class="intro">${esc(sec.intro)}</p>
  <ol>${sec.screens.map((s) => `<li>${esc(s[1])}</li>`).join('')}</ol>
  <p class="note">Screens are from the working demo at 1440 pixels wide. Stock, leads, staff names and figures are sample data.</p>
  <div class="ticks"></div>
</section>
${sec.screens.map((s) => screenPages(sec, s)).join('')}
`).join('')}

<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design</p><h2>Built for phones first</h2></div><p class="url">390 × 844</p></header>
  <p style="margin:0 0 4px">Most customers will find Hadeed Motors on a phone. Every page adapts to a small screen, and a bar with Call and Test drive stays within thumb reach (WhatsApp is added once you have a WhatsApp number).</p>
  ${phones([['m-home', 'Home'], ['m-vehicle', 'Pre-owned vehicle'], ['m-service', 'Service booking']])}
  ${notes([['Thumb-sized buttons', 'Every button is at least 48 pixels tall, and a Call button stays at the foot of the screen while browsing.'], ['The right keyboard', 'Phone, mileage and year fields open the number pad; contact fields fill in from the phone\'s saved details.'], ['Light on data', 'Photos are served as WebP in three sizes, so a phone downloads the small one.']])}
  ${foot()}
</section>
<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design · continued</p><h2>Requests and the sales desk on a phone</h2></div><p class="url">390 × 844</p></header>
  <p style="margin:0 0 4px">Consultants can check the call queue and call a customer back from the showroom floor, not only from a desk.</p>
  ${phones([['m-test-drive', 'Test-drive booking'], ['m-admin-dashboard', 'Sales desk overview'], ['m-admin-lead', 'Lead, one-tap call']])}
  ${notes([['Live slots', 'Only test-drive times and workshop days with space left can be picked.'], ['Overdue first', 'The overview opens on the leads that have waited more than two hours for a call.'], ['One tap to reply', 'Call, WhatsApp or email the customer straight from the lead.']])}
  ${foot()}
</section>

<section class="page prose">
  ${plate('04', 'Technology, hosting & security')}
  <h1>Built properly, hosted for you</h1>
  <div class="cols">
    <div class="card"><h3>Platform</h3><ul><li>Laravel 13 on PHP 8.3 with a MySQL database: open-source and widely supported, with no proprietary page builder</li><li>Tailwind CSS and Alpine.js; fonts served from your own site, not a third-party font service</li><li>No background servers needed; emails send directly</li><li>${TESTS} automated tests cover the forms, booking capacity, roles, document privacy and the estimator</li></ul></div>
    <div class="card"><h3>Security and privacy</h3><ul><li>HTTPS enforced, with security headers on every page</li><li>ID, pay slips and customer photos are stored outside the public website and served only to signed-in staff with the right role</li><li>Each opening of a finance document is logged</li><li>Forms are rate-limited and spam-trapped; bank and card numbers are refused</li><li>Login throttling, hashed passwords and individual staff accounts</li></ul></div>
    <div class="card"><h3>Speed and search</h3><ul><li>Responsive WebP photography; uploaded stock photos are resized automatically</li><li>XML sitemap, and schema.org data for the dealership, every car and every model</li><li>Old index.html?page= addresses redirect permanently to the new pages</li><li>Accessible: keyboard navigation, labelled forms, and status chips that pair an icon with text</li></ul></div>
    <div class="card"><h3>Ownership</h3><ul><li>Your content, photos, domain and customer data are always yours</li><li>Staff manage stock, leads, users and settings themselves</li><li>Hosting, backups, monitoring and updates are handled by Code Canvas under the monthly plan</li></ul></div>
  </div>
  <h2>Emails included</h2>
  <p>Every request sends the customer a confirmation with their reference number and what happens next, and alerts your chosen inboxes with a link to the lead. Finance details and documents are never emailed; they stay in the sales desk.</p>
  ${foot()}
</section>

<section class="page prose">
  ${plate('05', 'Investment')}
  <h1>The US$1,000 a month plan</h1>
  <div class="price">
    <div class="big"><div class="amt">US$1,000 <small>per month</small></div><p><b>US$0 for design and development.</b> No setup fee and no deposit. Invoiced in US dollars. Billing starts at the end of the month in which the site launches, prorated for the days it was live; monthly invoices are due within 14 days.</p></div>
    <div class="year"><div><span>Year one</span><b>US$12,000</b></div><div><span>Or prepaid annually (10% off)</span><b>US$10,800</b></div><div><span>Build cost</span><b>US$0</b></div></div>
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
    <div class="card"><h3>Terms</h3><ul class="terms">
      <li>No minimum term. 30 days' notice by email from either side.</li>
      <li>Price fixed for the first 24 months.</li>
      <li>Optional annual prepayment at 10% off: US$10,800 a year.</li>
      <li><b>Handover fee US$7,000, only if you leave.</b> It buys the website outright and moves it to hosting in Hadeed Motors' name. It is waived if Code Canvas stops trading.</li>
      <li>Your content, domain and data are always yours.</li>
    </ul>
    <h3 style="margin-top:8px">Quoted separately, only with your approval</h3><ul>
      <li>Work beyond 10 hours in a month: US$50 an hour, or a fixed quote</li>
      <li>Online card payments, such as reservation deposits</li>
      <li>SMS reminders for test drives and service</li>
      <li>Integrations with a dealer management or stock system, or with FDC's loan system</li>
      <li>A customer account portal; paid advertising and SEO campaigns; professional photography</li>
    </ul></div>
  </div>
  <h2>Service levels</h2>
  <table class="sla"><thead><tr><th>Issue</th><th>Response</th><th>Fix target</th></tr></thead><tbody>
    <tr><td>Site down or forms not sending</td><td>1 hour, any day</td><td>4 hours</td></tr>
    <tr><td>Something on a page is broken</td><td>4 business hours</td><td>1 business day</td></tr>
    <tr><td>Content changes (text, photos, hours, holidays)</td><td>Acknowledged in 1 business day</td><td>Done in 2 business days</td></tr>
  </tbody></table>
  ${foot()}
</section>

<section class="page prose">
  ${plate('06', 'Timeline')}
  <h1>Six weeks to launch</h1>
  <div class="weeks">
    <div class="week"><b>Week 1</b><p>Kick-off, brand assets and content: your logo files, photos and confirmation of the facts on the site.</p></div>
    <div class="week"><b>Week 2</b><p>Revision round 1. Forms, emails and staff roles configured with your real addresses.</p></div>
    <div class="week"><b>Week 3</b><p>Data and content load: real stock and photos, opening hours, public holidays and workshop capacity.</p></div>
    <div class="week"><b>Week 4</b><p>Revision round 2.</p></div>
    <div class="week"><b>Week 5</b><p>Formal testing with your sales, service and FDC staff on every form and screen.</p></div>
    <div class="week"><b>Week 6</b><p>Launch on HTTPS, redirects from the old addresses, Search Console, and staff training.</p></div>
  </div>
  <h2>What we need from Hadeed Motors</h2>
  <ul class="plain">
    <li>Your logo as a vector file (the demo uses the HM mark from hadeedmotors.com, which is only 123 pixels wide) and any brand guidelines.</li>
    <li>Photos of the showroom, team and current stock, or time for a short photo session.</li>
    <li>The stock list with prices you are happy to publish, and confirmation of opening hours, public holidays and daily workshop capacity.</li>
    <li>FDC's confirmation of the documents list and, if they wish, an indicative rate for the estimator.</li>
    <li>The staff who need accounts and their roles, and the inboxes that should receive new-lead alerts.</li>
    <li>Access to the hadeedmotors.com domain settings, and to your Google Business Profile and Analytics if they exist.</li>
  </ul>
  <h2>When billing starts</h2>
  <p>Not on signing. The build, both revision rounds, testing and the preview site cost nothing. Billing starts at the end of the month in which the site launches, prorated for the days it was live. Nothing goes live until you approve it in writing.</p>
  ${foot()}
</section>

<section class="page prose">
  ${plate('07', 'Next steps')}
  <h1>From proposal to launch</h1>
  <ol class="plain" style="padding-left:18px">
    <li style="margin:5px 0"><b>See the demo live.</b> We walk through the website and the sales desk together, on a laptop and a phone.</li>
    <li style="margin:5px 0"><b>Tell us what to change.</b> Pages, wording, roles or the request flows; revision round 1 opens straight away.</li>
    <li style="margin:5px 0"><b>Sign a one-page agreement</b> with the terms in this proposal: US$1,000 a month, month to month on 30 days' notice, US$0 build cost, and a US$7,000 handover fee only if you leave.</li>
    <li style="margin:5px 0"><b>Kick-off the same week</b>, with launch six weeks later.</li>
  </ol>
  <div class="card" style="margin-top:18px; max-width:4.6in">
    <h3>Contact</h3>
    <p style="margin:0">Keron Lewis, Director<br>Code Canvas Consultants Ltd, Port of Spain<br>codecanvastt.com · +1 868 466-CODE<br><a href="mailto:info@codecanvastt.com">info@codecanvastt.com<br>Book a call: calendly.com/keronlewis/30min</a></p>
  </div>
  <div style="margin-top:auto; margin-bottom:12px">${WORDMARK(false)}</div>
  ${foot()}
</section>
</body></html>`;

fs.writeFileSync(OUT_HTML, html);
const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--allow-file-access-from-files'] });
const page = await browser.newPage();
await page.goto('file://' + OUT_HTML, { waitUntil: 'networkidle0' });
await page.evaluate(() => document.fonts.ready);
await page.pdf({ path: OUT_PDF, format: 'Letter', printBackground: true, preferCSSPageSize: true, displayHeaderFooter: false });
await browser.close();
console.log('PDF written:', OUT_PDF, Math.round(fs.statSync(OUT_PDF).size / 1024 / 1024 * 10) / 10 + ' MB');
