// En-tête (fond au défilement, menu mobile), apparition des blocs et compteurs animés.

export function initHeader() {
    const header = document.getElementById('header');
    const burger = document.getElementById('burger');
    const menu = document.getElementById('menu');
    if (!header) return;

    const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 40);
    addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    burger?.addEventListener('click', () => {
        const open = menu.classList.toggle('open');
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    menu?.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => menu.classList.remove('open')));
}

export function initReveal() {
    const els = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) {
        els.forEach((el) => el.classList.add('in'));
        return;
    }
    const io = new IntersectionObserver((entries) => entries.forEach((e) => {
        if (e.isIntersecting) {
            e.target.classList.add('in');
            io.unobserve(e.target);
        }
    }), { threshold: 0.12 });
    els.forEach((el) => io.observe(el));
    // Filet de sécurité : rien ne doit rester invisible.
    setTimeout(() => els.forEach((el) => el.classList.add('in')), 2500);
}

function fillCount(el, instant) {
    if (el.dataset.done) return;
    el.dataset.done = '1';
    const target = parseFloat(el.dataset.count) || 0;
    const unit = el.dataset.unit ? ` <small>${el.dataset.unit}</small>` : '';
    const fmt = (c) => Math.round(c).toLocaleString('fr-FR') + unit;
    if (instant) {
        el.innerHTML = fmt(target);
        return;
    }
    let c = 0;
    const step = target / 55;
    const run = () => {
        c = Math.min(c + step, target);
        el.innerHTML = fmt(c);
        if (c < target) requestAnimationFrame(run);
    };
    run();
}

export function initCounters() {
    const els = document.querySelectorAll('[data-count]');
    if (!els.length) return;
    const io = new IntersectionObserver((entries) => entries.forEach((e) => {
        if (!e.isIntersecting) return;
        fillCount(e.target, false);
        io.unobserve(e.target);
    }), { threshold: 0.35 });
    els.forEach((el) => io.observe(el));
    setTimeout(() => els.forEach((el) => fillCount(el, true)), 2500);
}
