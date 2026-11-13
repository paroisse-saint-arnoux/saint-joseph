// « Le clocher des bâtisseurs » : le clocher, la barre et le mur montent avec le montant collecté.
// 1 pierre = 10 €, 1 mur = 100 pierres. Les valeurs viennent des attributs data-* de #collecte.

const GY = 590; // sol du clocher dans le SVG
const TY = 60; // sommet
const SPAN = GY - TY;

const fr = (n) => Math.round(n).toLocaleString('fr-FR');

function countUp(el, to, fmt) {
    if (!el) return;
    let c = 0;
    const step = to / 55;
    const run = () => {
        c = Math.min(c + step, to);
        el.innerHTML = fmt(c);
        if (c < to) requestAnimationFrame(run);
    };
    run();
}

function buildWall(wall) {
    const ROWS = 11;
    const COLS = 10;
    const order = [];
    for (let r = 0; r < ROWS; r++) {
        const row = document.createElement('div');
        row.className = 'row' + (r % 2 ? ' odd' : '');
        const add = (cls) => {
            const b = document.createElement('i');
            b.className = cls;
            row.appendChild(b);
            order.push(b);
        };
        if (r % 2) {
            add('brick half');
            for (let c = 0; c < COLS - 1; c++) add('brick');
            add('brick half');
        } else {
            for (let c = 0; c < COLS; c++) add('brick');
        }
        wall.appendChild(row); // column-reverse : la première rangée est en bas
    }
    return order;
}

export function initClocher() {
    const section = document.getElementById('collecte');
    if (!section) return;

    const $ = (id) => document.getElementById(id);
    const raised = parseFloat(section.dataset.raised) || 0;
    const goal = parseFloat(section.dataset.goal) || 1;
    const goalStones = goal / 10;
    const base = Math.round(raised / 10); // pierres déjà posées
    const baseY = GY - Math.min(raised / goal, 1) * SPAN;

    const wall = $('wall');
    const bricks = wall ? buildWall(wall) : [];
    const baseBricks = Math.round(Math.min(raised / goal, 1) * bricks.length);

    let added = 0; // pierres ajoutées en cliquant sur les niveaux (« aujourd'hui »)
    let currentY = GY;

    const setLevel = (yy) => {
        currentY = yy;
        $('riseRect').setAttribute('y', yy);
        $('riseRect').setAttribute('height', GY + 8 - yy);
        $('riseLine').setAttribute('y', yy - 1.5);
        $('curLine').setAttribute('y1', yy);
        $('curLine').setAttribute('y2', yy);
        $('curCm').setAttribute('y', yy - 7);
        const band = $('todayBand');
        if (band) {
            const h = Math.max(baseY - yy, 0);
            band.setAttribute('y', yy);
            band.setAttribute('height', h);
            band.style.opacity = h > 0 ? 1 : 0;
        }
    };
    const animateTo = (target, duration) => {
        const from = currentY;
        const start = performance.now();
        const step = (now) => {
            const t = Math.min((now - start) / duration, 1);
            setLevel(from + (1 - Math.pow(1 - t, 3)) * (target - from));
            if (t < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };

    // Hauteur du clocher avec les pierres ajoutées : un don de quelques pierres est invisible
    // à l'échelle de l'objectif, on le montre donc au moins de quelques pixels.
    const targetY = () => {
        if (!added) return baseY;
        const delta = Math.max((added / goalStones) * SPAN, 6 + Math.log10(added) * 6);
        return Math.max(baseY - delta, TY);
    };

    // Chiffres et mur, avec les pierres ajoutées
    const update = () => {
        const total = base + added;
        const pct = Math.min(total / goalStones, 1);
        $('curCm').textContent = `${fr(total)} pierres`;
        $('barFill').style.width = `${pct * 100}%`;
        $('raisedTxt').innerHTML = `${fr(base)} <small>pierres</small>`
            + (added ? ` <em>+ ${fr(added)} aujourd'hui&nbsp;!</em>` : '');
        $('pctTxt').textContent = `${Math.round(pct * 100)} %`;
        ['pierres', 'wallPierres'].forEach((id) => { if ($(id)) $(id).textContent = fr(total); });
        if ($('mursDone')) $('mursDone').textContent = fr(Math.floor(total / 100));
        if ($('wallPct')) $('wallPct').textContent = `${Math.round(pct * 100)} %`;

        const today = added ? Math.max(1, Math.round((added / goalStones) * bricks.length)) : 0;
        bricks.forEach((b, i) => b.classList.toggle('today', i >= baseBricks && i < baseBricks + today));

        const give = $('clocherGive');
        if (give) {
            give.hidden = !added;
            $('clocherGiveAmount').textContent = added ? `(${fr(added * 10)} €)` : '';
        }
    };

    let played = false;
    const play = () => {
        if (played) return;
        played = true;
        animateTo(baseY, 1600);
        $('curCm').textContent = `${fr(base)} pierres`;
        $('barFill').style.width = `${Math.min(raised / goal, 1) * 100}%`;
        bricks.forEach((b, i) => setTimeout(() => b.classList.toggle('set', i < baseBricks), i * 8));

        countUp($('raisedTxt'), base, (v) => `${fr(v)} <small>pierres</small>`);
        countUp($('pctTxt'), Math.min(raised / goal, 1) * 100, (v) => `${Math.round(v)} %`);
        countUp($('pierres'), base, fr);
        countUp($('wallPierres'), base, fr);
        countUp($('mursDone'), Math.floor(base / 100), fr);
        const wp = $('wallPct');
        if (wp) wp.textContent = `${Math.round(Math.min(raised / goal, 1) * 100)} %`;
    };

    const io = new IntersectionObserver((entries) => {
        if (entries.some((e) => e.isIntersecting)) {
            play();
            io.disconnect();
        }
    }, { threshold: 0.2 });
    io.observe(section);

    // Niveaux « 1 pierre », « 10 pierres »… : chaque clic ajoute ce don au clocher
    section.querySelectorAll('.lvl[data-stones]').forEach((btn) => {
        btn.addEventListener('click', () => {
            play();
            added += parseInt(btn.dataset.stones, 10) || 0;
            update();
            animateTo(targetY(), 700);
            btn.classList.remove('bump');
            void btn.offsetWidth; // relance l'animation
            btn.classList.add('bump');
        });
    });
    $('clocherReset')?.addEventListener('click', () => {
        added = 0;
        update();
        animateTo(baseY, 700);
    });

    initMecenat(section);
}

// « Le clocher » : formulaire de contact pour un soutien important, envoyé sans recharger la page.
function initMecenat(section) {
    const toggle = section.querySelector('[data-mecenat]');
    const zone = document.getElementById('mecenat');
    const form = zone?.querySelector('form');
    if (!toggle || !form) return;

    toggle.addEventListener('click', () => {
        zone.hidden = !zone.hidden;
        toggle.setAttribute('aria-expanded', String(!zone.hidden));
        toggle.classList.toggle('is-open', !zone.hidden);
        if (zone.hidden) return;
        // Cloudflare Turnstile chargé seulement à l'ouverture du formulaire
        if (form.hasAttribute('data-turnstile') && !document.getElementById('turnstile-js')) {
            const sc = document.createElement('script');
            sc.id = 'turnstile-js';
            sc.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
            sc.async = true;
            document.head.appendChild(sc);
        }
        zone.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => form.querySelector('input:not([type=hidden])')?.focus({ preventScroll: true }), 400);
    });

    const feedback = form.querySelector('.form-feedback');
    const button = form.querySelector('button[type=submit]');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        button.disabled = true;
        feedback.textContent = '';
        feedback.className = 'form-feedback';
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new FormData(form),
            });
            const data = await res.json();
            const ok = res.ok && data.ok;
            const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
            feedback.textContent = firstError || data.message || "Votre message n'a pas pu être envoyé. Merci de réessayer.";
            feedback.classList.add(ok ? 'ok' : 'error');
            if (ok) form.querySelectorAll('input[type=text], input[type=email], textarea').forEach((f) => { f.value = ''; });
        } catch {
            feedback.textContent = "Votre message n'a pas pu être envoyé. Merci de réessayer.";
            feedback.classList.add('error');
        } finally {
            button.disabled = false;
            window.turnstile?.reset();
        }
    });
}
