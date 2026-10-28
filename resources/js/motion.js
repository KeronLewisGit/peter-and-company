/*
 * Motion and interaction. Everything here is an enhancement: the server-rendered page is complete
 * without it, and with `prefers-reduced-motion: reduce` only the non-moving parts run (the map's
 * district highlight, the stepper's state, "Add to order" without the flying tile).
 */
const reduce = matchMedia('(prefers-reduced-motion: reduce)');
const finePointer = matchMedia('(hover: hover) and (pointer: fine)');
const moving = () => !reduce.matches;
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
const frame = (fn) => { let id = 0; return (...a) => { if (!id) id = requestAnimationFrame(() => { id = 0; fn(...a); }); }; };

/* Long-running loops (Ken Burns, orbit, marquee, map vans) pause while off screen. */
function inView() {
    if (!('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver((es) => es.forEach((e) => e.target.classList.toggle('is-inview', e.isIntersecting)), { rootMargin: '120px 0px' });
    $$('[data-inview], [data-marquee], [data-dmap], [data-steps]').forEach((el) => io.observe(el));
}

/* Stat tiles count up once, when they come into view. The final number is in the HTML. */
function countUp() {
    if (!moving() || !('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver((es) => es.forEach((e) => {
        if (!e.isIntersecting) return;
        io.unobserve(e.target);
        const el = e.target;
        const end = Number(el.dataset.count);
        const t0 = performance.now();
        const dur = end > 999 ? 1800 : 1200;
        const tick = (t) => {
            const p = Math.min(1, (t - t0) / dur);
            el.textContent = String(Math.round(end * (1 - (1 - p) ** 4)));
            if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    }), { threshold: 0.6 });
    $$('[data-count]').forEach((el) => io.observe(el));
}

/* Marquee: a real Pause button (hover and focus already pause it in CSS). */
function marquees() {
    if (!moving()) return;
    $$('[data-marquee]').forEach((m) => {
        const b = m.querySelector('[data-marquee-toggle]');
        if (!b) return;
        b.hidden = false;
        b.addEventListener('click', () => b.setAttribute('aria-pressed', String(m.classList.toggle('is-paused'))));
    });
}

/* Delivery map: pointing at or focusing a district (on the map or the list) highlights both and shows its days. */
function maps() {
    $$('[data-dmap]').forEach((root) => {
        const card = root.querySelector('[data-dmap-card]');
        const items = Object.fromEntries($$('li[data-district]', root).map((li) => [li.dataset.district, li]));
        let current = null;
        const show = (key) => {
            if (key === current) return;
            current = key;
            $$('.is-active', root).forEach((x) => x.classList.remove('is-active'));
            if (!key || !items[key]) { card.classList.remove('is-shown'); return; }
            $$(`[data-district="${key}"]`, root).forEach((x) => x.classList.add('is-active'));
            const li = items[key];
            card.querySelector('.dmap-card-name').textContent = li.firstElementChild.textContent;
            card.querySelector('.dmap-card-days').textContent = li.dataset.days;
            card.style.left = li.dataset.left + '%';
            card.style.top = li.dataset.top + '%';
            card.classList.add('is-shown');
        };
        const keyOf = (e) => e.target.closest?.('[data-district]')?.dataset.district ?? null;
        root.addEventListener('pointerover', (e) => show(keyOf(e)));
        root.addEventListener('pointerleave', () => show(null));
        root.addEventListener('focusin', (e) => show(keyOf(e)));
        root.addEventListener('focusout', () => show(null));
    });
}

/* "Four steps": the step nearest the reading line becomes active and the van drives to its stop. */
function steps() {
    $$('[data-steps]').forEach((root) => {
        const cards = $$('[data-step-n]', root);
        const stops = $$('.route-stop', root);
        const update = frame(() => {
            const line = innerHeight * 0.62;
            let n = 1;
            cards.forEach((c, i) => { if (c.getBoundingClientRect().top < line) n = i + 1; });
            root.style.setProperty('--step', String(n));
            cards.forEach((c, i) => c.classList.toggle('is-active', i === n - 1));
            stops.forEach((s, i) => s.classList.toggle('is-done', i < n));
        });
        update();
        new IntersectionObserver(([e]) => {
            if (e.isIntersecting) { addEventListener('scroll', update, { passive: true }); update(); } else removeEventListener('scroll', update);
        }).observe(root);
    });
}

/* Division cards: tilt toward the pointer, the picture drifts the other way, a glare follows. */
function tilts() {
    if (!moving() || !finePointer.matches) return;
    $$('[data-tilt]').forEach((el) => {
        const move = frame((x, y) => {
            el.style.setProperty('--ry', (x * 9).toFixed(2) + 'deg');
            el.style.setProperty('--rx', (-y * 7).toFixed(2) + 'deg');
            el.style.setProperty('--px', (-x * 12).toFixed(1) + 'px');
            el.style.setProperty('--py', (-y * 10).toFixed(1) + 'px');
            el.style.setProperty('--gx', (x * 70).toFixed(1) + '%');
            el.style.setProperty('--gy', (y * 70).toFixed(1) + '%');
        });
        el.addEventListener('pointermove', (e) => {
            const r = el.getBoundingClientRect();
            el.classList.add('is-tilting');
            move((e.clientX - r.left) / r.width - 0.5, (e.clientY - r.top) / r.height - 0.5);
        });
        el.addEventListener('pointerleave', () => { el.classList.remove('is-tilting'); move(0, 0); });
    });
}

/* Navy band: a soft spotlight follows the pointer. */
function spotlights() {
    if (!moving() || !finePointer.matches) return;
    $$('[data-spotlight]').forEach((el) => {
        const move = frame((x, y) => { el.style.setProperty('--sx', x + 'px'); el.style.setProperty('--sy', y + 'px'); });
        el.addEventListener('pointermove', (e) => {
            const r = el.getBoundingClientRect();
            el.classList.add('is-lit');
            move(Math.round(e.clientX - r.left), Math.round(e.clientY - r.top));
        });
        el.addEventListener('pointerleave', () => el.classList.remove('is-lit'));
    });
}

/* Trade account stepper: follows the part of the form you are in and ticks the finished parts. */
function stepper() {
    const nav = document.querySelector('[data-stepper]');
    if (!nav) return;
    const parts = $$('[data-form-step]');
    const links = parts.map((p) => nav.querySelector(`[data-stepper-link="${p.id}"]`));
    const bar = nav.querySelector('.stepper-bar span');
    let current = 0;
    const visited = new Set([0]);
    const done = (fs, i) => {
        const req = $$('input, select, textarea', fs).filter((f) => f.required && !f.disabled && f.type !== 'hidden' && f.offsetParent !== null);
        return req.length ? req.every((f) => f.checkValidity()) : visited.has(i) && i < current;
    };
    const update = () => {
        let count = 0;
        parts.forEach((fs, i) => {
            const a = links[i];
            if (!a) return;
            const ok = done(fs, i);
            count += ok ? 1 : 0;
            a.classList.toggle('is-done', ok);
            a.classList.toggle('is-current', i === current);
            if (i === current) a.setAttribute('aria-current', 'step'); else a.removeAttribute('aria-current');
            const st = a.querySelector('[data-stepper-state]');
            if (st) st.textContent = ok ? ' (done)' : '';
        });
        if (bar) bar.style.setProperty('--p', String(count / parts.length));
    };
    const setCurrent = (i) => { if (i < 0 || i === current) return; current = i; visited.add(i); update(); };
    const io = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) setCurrent(parts.indexOf(e.target)); }), { rootMargin: '-35% 0px -55% 0px' });
    parts.forEach((p) => io.observe(p));
    const form = parts[0]?.closest('form');
    form?.addEventListener('focusin', (e) => setCurrent(parts.findIndex((p) => p.contains(e.target))));
    form?.addEventListener('input', update);
    form?.addEventListener('change', update);
    update();
}

/* "Add to order": with JavaScript the line is added in place. The tile flies to the order list, the count
 * bumps and a toast confirms. Without JavaScript, or if anything unexpected comes back, the form posts as usual. */
function toast(message) {
    const host = document.querySelector('[data-toasts]');
    if (!host) return;
    const t = document.createElement('div');
    t.className = 'toast';
    t.innerHTML = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg><span></span><a>View list</a>';
    t.querySelector('span').textContent = message;
    t.querySelector('a').href = document.querySelector('[data-order-target]')?.href ?? '/order';
    host.replaceChildren(t);
    setTimeout(() => { t.classList.add('is-leaving'); setTimeout(() => t.remove(), moving() ? 320 : 0); }, 5000);
}

function bump(el) {
    if (!el || !moving()) return;
    el.classList.remove('bump');
    void el.offsetWidth;
    el.classList.add('bump');
}

function fly(card, target) {
    if (!moving() || !card || !target) return Promise.resolve();
    const art = card.querySelector('.case-art') ?? card;
    const a = art.getBoundingClientRect();
    const b = target.getBoundingClientRect();
    const tile = document.createElement('div');
    tile.className = 'fly-tile';
    const img = art.querySelector('img');
    if (img) tile.append(img.cloneNode());
    else { const s = document.createElement('span'); s.textContent = art.textContent.trim(); tile.append(s); }
    document.body.append(tile);
    const size = tile.offsetWidth;
    const sx = a.left + a.width / 2 - size / 2; const sy = a.top + a.height / 2 - size / 2;
    const ex = b.left + b.width / 2 - size / 2; const ey = b.top + b.height / 2 - size / 2;
    const mx = sx + (ex - sx) * 0.35; const my = Math.max(16, Math.min(sy, ey) - 70);
    const anim = tile.animate([
        { transform: `translate(${sx}px, ${sy}px) scale(1.5)`, opacity: 0 },
        { transform: `translate(${sx}px, ${sy - 24}px) scale(1.25)`, opacity: 1, offset: 0.15 },
        { transform: `translate(${mx}px, ${my}px) scale(0.85)`, opacity: 1, offset: 0.55 },
        { transform: `translate(${ex}px, ${ey}px) scale(0.3)`, opacity: 0.4 },
    ], { duration: 900, easing: 'cubic-bezier(0.45, 0, 0.25, 1)' });
    return anim.finished.then(() => tile.remove(), () => tile.remove());
}

function orderForms() {
    if (!window.fetch || !window.DOMParser) return;
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post' || !/\/order\/list$/.test(new URL(form.action).pathname)) return;
        e.preventDefault();
        const button = e.submitter ?? form.querySelector('button');
        const hadFocus = document.activeElement === button;
        if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
        try {
            const res = await fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'text/html' } });
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            const count = doc.querySelector('[data-order-count]')?.textContent.trim();
            const flash = doc.querySelector('[data-flash]')?.textContent.trim();
            if (!res.ok || count === undefined || !flash) throw new Error('unexpected response');
            const card = form.closest('article[id]');
            const target = $$('[data-order-target]').find((t) => t.offsetParent !== null && t.getBoundingClientRect().width > 0);
            await fly(card, target);
            $$('[data-order-count]').forEach((c) => { c.textContent = count; bump(c); });
            bump(target);
            const fresh = card && doc.getElementById(card.id);
            if (fresh) {
                card.replaceWith(fresh);
                if (hadFocus) fresh.querySelector('form button')?.focus();
            } else if (button) { button.disabled = false; button.removeAttribute('aria-busy'); }
            toast(flash);
        } catch {
            if (button) button.disabled = false;
            form.submit();
        }
    });
}

export function startMotion() {
    inView();
    countUp();
    marquees();
    maps();
    steps();
    tilts();
    spotlights();
    stepper();
    orderForms();
}

/* Theme switch: with View Transitions, the new theme spreads in a circle from the button. */
export function themeTransition(apply, event) {
    const root = document.documentElement;
    if (!document.startViewTransition || !moving()) { apply(); return; }
    const r = event?.currentTarget?.getBoundingClientRect?.();
    const x = r ? r.left + r.width / 2 : innerWidth - 48;
    const y = r ? r.top + r.height / 2 : 48;
    const end = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
    root.classList.add('vt-theme');
    const t = document.startViewTransition(apply);
    t.ready.then(() => root.animate(
        { clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${end}px at ${x}px ${y}px)`] },
        { duration: 700, easing: 'cubic-bezier(0.6, 0, 0.2, 1)', pseudoElement: '::view-transition-new(root)' },
    )).catch(() => {});
    t.finished.finally(() => root.classList.remove('vt-theme'));
}
