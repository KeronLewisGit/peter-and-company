// Local QA screenshots: node scripts/screens.mjs <outdir> [path ...]. Needs the dev server on 8108.
import puppeteer from 'puppeteer-core';
const out = process.argv[2];
const paths = process.argv.slice(3);
const BASE = process.env.BASE ?? 'http://127.0.0.1:8108';
const browser = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: true });
const login = process.env.LOGIN;
for (const [vp, w, h] of [['phone', 390, 844], ['desk', 1440, 900]]) {
    for (const theme of ['light', 'dark']) {
        const ctx = await browser.createBrowserContext();
        const page = await ctx.newPage();
        await page.setViewport({ width: w, height: h, deviceScaleFactor: 1 });
        await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: theme }, { name: 'prefers-reduced-motion', value: 'reduce' }]);
        if (login) {
            await page.goto(BASE + '/login');
            await page.type('#email', login);
            await page.type('#password', 'password');
            await Promise.all([page.waitForNavigation(), page.click('button.a-btn-primary')]);
        }
        for (const p of paths) {
            await page.goto(BASE + p, { waitUntil: 'load', timeout: 30000 });
            await page.evaluate(async () => {
                document.querySelectorAll('img[loading="lazy"]').forEach((i) => { i.loading = 'eager'; });
                for (let y = 0; y < document.body.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 60)); }
                window.scrollTo(0, 0);
                await Promise.all([...document.images].map((i) => (i.complete ? null : new Promise((r) => { i.onload = i.onerror = r; setTimeout(r, 4000); }))));
            });
            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
            if (overflow > 0) console.log(`OVERFLOW ${overflow}px ${vp} ${p}`);
            const name = (p.replace(/[^a-z0-9]+/gi, '_') || 'home').replace(/^_|_$/g, '') || 'home';
            await page.screenshot({ path: `${out}/${name}-${vp}-${theme}.jpg`, fullPage: true, quality: 60, type: 'jpeg' });
        }
        await ctx.close();
    }
}
await browser.close();
