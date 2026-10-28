// Records a ~40 s walkthrough of the home page in motion (needs the dev server on 8108 and ffmpeg).
// Usage: node scripts/proposal/video.mjs [out.mp4]  → dist/proposal/Acado-St-Lucia-Motion-Preview.mp4
import puppeteer from 'puppeteer-core';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8108';
const OUT = path.resolve(process.argv[2] ?? 'dist/proposal/Acado-St-Lucia-Motion-Preview.mp4');
const RAW = OUT.replace(/\.mp4$/, '.raw.webm');
const FFMPEG = process.env.FFMPEG ?? (fs.existsSync('/opt/homebrew/bin/ffmpeg') ? '/opt/homebrew/bin/ffmpeg' : 'ffmpeg');
const W = 1280, H = 800;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
fs.mkdirSync(path.dirname(OUT), { recursive: true });

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--hide-scrollbars', `--window-size=${W},${H}`] });
const page = await browser.newPage();
await page.setViewport({ width: W, height: H, deviceScaleFactor: 1 });
await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }, { name: 'prefers-color-scheme', value: 'light' }]);
// Headless Chrome draws no pointer, so show a soft one that follows the mouse.
await page.evaluateOnNewDocument(() => {
    addEventListener('DOMContentLoaded', () => {
        const c = document.createElement('div');
        c.style.cssText = 'position:fixed;left:0;top:0;width:22px;height:22px;margin:-11px 0 0 -11px;border-radius:50%;background:rgb(255 189 11/.55);border:2px solid #002766;z-index:2147483647;pointer-events:none;transform:translate(-60px,-60px);transition:transform .06s linear, scale .15s';
        document.documentElement.appendChild(c);
        addEventListener('mousemove', (e) => { c.style.transform = `translate(${e.clientX}px,${e.clientY}px)`; }, { passive: true });
        addEventListener('mousedown', () => { c.style.scale = '.7'; }); addEventListener('mouseup', () => { c.style.scale = '1'; });
    });
});
// Eased scroll to an element (top offset in px), over ms.
const glide = (sel, offset = 90, ms = 1100) => page.evaluate((sel, offset, ms) => new Promise((done) => {
    const el = sel ? document.querySelector(sel) : null;
    const to = el ? Math.min(document.documentElement.scrollHeight - innerHeight, el.getBoundingClientRect().top + scrollY - offset) : 0;
    const from = scrollY, t0 = performance.now();
    const ease = (t) => (t < 0.5 ? 4 * t * t * t : 1 - (-2 * t + 2) ** 3 / 2);
    const step = (now) => { const t = Math.min(1, (now - t0) / ms); scrollTo(0, from + (to - from) * ease(t)); t < 1 ? requestAnimationFrame(step) : done(); };
    requestAnimationFrame(step);
}), sel, offset, ms);
const centre = (sel, fx = 0.5, fy = 0.5) => page.$eval(sel, (el, fx, fy) => { const r = el.getBoundingClientRect(); return { x: r.left + r.width * fx, y: r.top + r.height * fy }; }, fx, fy);
const moveTo = async (sel, fx, fy, steps = 22) => { const p = await centre(sel, fx, fy); await page.mouse.move(p.x, p.y, { steps }); return p; };

await page.goto('data:text/html,<body style="margin:0;background:%23fff">', { waitUntil: 'load' });
const recorder = await page.screencast({ path: RAW });
await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
await page.mouse.move(W * 0.7, H * 0.75);
await sleep(4200); // hero entrance: Ken Burns, words, the Acado mark, count-up

await glide('#div-title', 110, 1300); await sleep(300); // division cards: tilt and glare
await moveTo('[data-tilt]', 0.2, 0.3); await moveTo('[data-tilt]', 0.85, 0.7, 30);
await moveTo('.tilt-wrap:nth-child(2) [data-tilt]', 0.3, 0.25, 26); await moveTo('.tilt-wrap:nth-child(2) [data-tilt]', 0.75, 0.75, 26);
await sleep(300);

await glide('#cat-title', 110, 1200); await sleep(400); // add to order: tile flies to the order list
await moveTo('section:has(#cat-title) form[action$="/order/list"] button', 0.5, 0.5, 26);
await sleep(250); await page.mouse.down(); await page.mouse.up(); // a real click on the button
await sleep(2300);

await page.mouse.move(W - 40, H - 40, { steps: 10 });
await glide('#brands-title', 120, 1200); await sleep(2600); // two-row logo marquee

for (const n of [1, 2, 3, 4]) { // four steps: the sticky panel's van drives to each stop
    await page.evaluate((n) => { const el = document.querySelector(`[data-step-n="${n}"]`); scrollTo({ top: el.getBoundingClientRect().top + scrollY - innerHeight * 0.45, behavior: 'smooth' }); }, n);
    await sleep(1150);
}

await glide('[data-dmap] .dmap-panel', 110, 1300); await sleep(3000); // map: routes draw out of Cul De Sac
for (const d of ['soufriere', 'gros_islet', 'vieux_fort']) { await moveTo(`.dmap-district[data-district="${d}"]`, 0.5, 0.5, 24); await sleep(900); }

await page.mouse.move(W - 40, 120, { steps: 10 });
await glide('#hist-title', 120, 1300); await sleep(2400); // the 1891 → 2025 line draws itself

await glide('[data-spotlight]', 260, 1200); await sleep(200); // spotlight on the navy band
await moveTo('[data-spotlight]', 0.15, 0.5, 30); await moveTo('[data-spotlight]', 0.8, 0.4, 40); await sleep(300);

await glide(null, 0, 1600); await sleep(500); // back to the top, then the circular theme reveal
await moveTo('header button[aria-label="Switch light or dark theme"]', 0.5, 0.5, 26);
await page.mouse.down(); await page.mouse.up();
await sleep(1400);
await page.mouse.move(W * 0.6, H * 0.7, { steps: 20 });
await glide('#todo-title', 140, 1400); await sleep(1600); // the dark theme on the white sections

await recorder.stop();
await browser.close();
// Constant 30 fps H.264 for any player; small enough to email.
execFileSync(FFMPEG, ['-y', '-loglevel', 'error', '-i', RAW, '-vf', 'fps=30,format=yuv420p', '-c:v', 'libx264', '-preset', 'slow', '-crf', '24', '-movflags', '+faststart', '-an', OUT]);
fs.rmSync(RAW, { force: true });
const dur = execFileSync(FFMPEG.replace(/ffmpeg$/, 'ffprobe'), ['-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', OUT]).toString().trim();
console.log(`✓ ${OUT} ${fs.statSync(OUT).size} bytes, ${Number(dur).toFixed(1)} s`);
