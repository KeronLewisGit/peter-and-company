// Captures frame strips of the motion and interaction layer for the proposal (after capture.mjs).
// Output: dist/proposal/shots/motion/{effect}-{n}.jpg and motion.json. Needs the dev server on 8108.
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';

const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8108';
const OUT = path.resolve('dist/proposal/shots/motion');
fs.rmSync(OUT, { recursive: true, force: true });
fs.mkdirSync(OUT, { recursive: true });
const QUALITY = 70;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--hide-scrollbars', '--no-sandbox'] });
const strips = {};

async function fresh(width = 1440, height = 900) {
    const ctx = await browser.createBrowserContext();
    const page = await ctx.newPage();
    await page.setViewport({ width, height, deviceScaleFactor: 0.55 }); // small frames: they print as strips
    await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }, { name: 'prefers-color-scheme', value: 'light' }]);
    return { ctx, page };
}
const rectOf = (page, sel, pad = 0) => page.$eval(sel, (el, pad) => {
    const r = el.getBoundingClientRect();
    // Screenshot clips are in page coordinates.
    return { x: Math.max(0, r.left - pad) + scrollX, y: Math.max(0, r.top - pad) + scrollY, width: Math.min(innerWidth, r.width + pad * 2), height: Math.min(innerHeight - Math.max(0, r.top - pad), r.height + pad * 2) };
}, pad);
const scrollTo = (page, sel, offset = 110) => page.$eval(sel, (el, offset) => window.scrollTo({ top: el.getBoundingClientRect().top + scrollY - offset, behavior: 'instant' }), offset);
async function shot(page, name, i, clip) {
    const file = path.join(OUT, `${name}-${i}.jpg`);
    await page.screenshot({ path: file, type: 'jpeg', quality: QUALITY, ...(clip ? { clip } : {}) });
    (strips[name] ??= []).push(file);
}

// 1. Hero entrance: Ken Burns, word-by-word headline, the mark assembling, count-up.
{
    const { ctx, page } = await fresh(1440, 820);
    await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
    const t0 = Date.now();
    for (const [i, t] of [180, 520, 950, 3200].entries()) {
        await sleep(Math.max(0, t - (Date.now() - t0)));
        await shot(page, 'hero', i, { x: 0, y: 40, width: 1440, height: 780 });
    }
    await ctx.close();
}

// 2. Delivery map: routes draw out of Cul De Sac, then a district on hover.
{
    const { ctx, page } = await fresh();
    await page.goto(BASE + '/', { waitUntil: 'networkidle0' });
    await scrollTo(page, '[data-dmap] .dmap-panel', 120);
    const t0 = Date.now();
    for (const [i, t] of [350, 1000, 3300].entries()) {
        await sleep(Math.max(0, t - (Date.now() - t0)));
        await shot(page, 'map', i, await rectOf(page, '[data-dmap] .dmap-panel', 8));
    }
    const box = await page.$eval('.dmap-district[data-district="soufriere"]', (el) => { const r = el.getBoundingClientRect(); return { x: r.left + r.width * 0.45, y: r.top + r.height * 0.5 }; });
    await page.mouse.move(box.x, box.y);
    await sleep(500);
    await shot(page, 'map', 3, await rectOf(page, '[data-dmap] .dmap-panel', 8));
    await ctx.close();
}

// 3. Four steps: the van moves to each stop as the steps scroll past.
{
    const { ctx, page } = await fresh();
    await page.goto(BASE + '/', { waitUntil: 'networkidle0' });
    for (const [i, n] of [1, 2, 4].entries()) {
        await page.$eval(`[data-step-n="${n}"]`, (el) => window.scrollTo({ top: el.getBoundingClientRect().top + scrollY - innerHeight * 0.45, behavior: 'instant' }));
        await sleep(1300);
        await shot(page, 'steps', i, await rectOf(page, '[data-steps]', 0));
    }
    await ctx.close();
}

// 4. Add to order: the tile flies to the order list, the count bumps, a toast confirms.
{
    const { ctx, page } = await fresh();
    await page.goto(BASE + '/catalogue', { waitUntil: 'networkidle0' });
    await page.evaluate(() => window.scrollTo(0, 260));
    await sleep(500);
    const clip = null; // the viewport
    await page.click('article.case form button');
    const t0 = Date.now();
    for (const [i, t] of [260, 560, 1400].entries()) {
        await sleep(Math.max(0, t - (Date.now() - t0)));
        await shot(page, 'order', i, clip);
    }
    await ctx.close();
}

// 5. Theme switch: the dark theme spreads in a circle from the button (shown over the white sections).
{
    const { ctx, page } = await fresh(1440, 820);
    await page.goto(BASE + '/', { waitUntil: 'networkidle0' });
    await scrollTo(page, '#todo-title', 150);
    await sleep(1500);
    await shot(page, 'theme', 0, null);
    await page.click('header button[aria-label="Switch light or dark theme"]');
    const t0 = Date.now();
    for (const [i, t] of [300, 1100].entries()) {
        await sleep(Math.max(0, t - (Date.now() - t0)));
        await shot(page, 'theme', i + 1, null);
    }
    await ctx.close();
}

// 6. Brand marquee, and the division cards' tilt.
{
    const { ctx, page } = await fresh();
    await page.goto(BASE + '/', { waitUntil: 'networkidle0' });
    await scrollTo(page, '#brands-title', 140);
    await sleep(1800);
    await shot(page, 'marquee', 0, await rectOf(page, '[data-marquee]', 12));
    await scrollTo(page, '#div-title', 140);
    await sleep(1200);
    const card = await page.$eval('[data-tilt]', (el) => { const r = el.getBoundingClientRect(); return { x: r.left + r.width * 0.82, y: r.top + r.height * 0.22 }; });
    await page.mouse.move(card.x - 40, card.y + 40);
    await page.mouse.move(card.x, card.y, { steps: 8 });
    await sleep(500);
    await shot(page, 'tilt', 0, await rectOf(page, '.band-orange ul', 24));
    await ctx.close();
}

fs.writeFileSync(path.join(OUT, 'motion.json'), JSON.stringify(strips, null, 1));
console.log('✓ motion frames:', Object.entries(strips).map(([k, v]) => `${k} ${v.length}`).join(', '));
await browser.close();
