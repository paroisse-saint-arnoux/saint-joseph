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
    const pct = Math.min(raised / goal, 1);
    const pierres = Math.round(raised / 10);
    const murs = Math.floor(pierres / 100);
    const y = GY - pct * SPAN;

    const wall = $('wall');
    const bricks = wall ? buildWall(wall) : [];

    let played = false;
    const play = () => {
        if (played) return;
        played = true;
        const setLevel = (yy) => {
            $('riseRect').setAttribute('y', yy);
            $('riseRect').setAttribute('height', GY + 8 - yy);
            $('riseLine').setAttribute('y', yy - 1.5);
            $('curLine').setAttribute('y1', yy);
            $('curLine').setAttribute('y2', yy);
            $('curCm').setAttribute('y', yy - 7);
        };
        const start = performance.now();
        const rise = (now) => {
            const t = Math.min((now - start) / 1600, 1);
            const eased = 1 - Math.pow(1 - t, 3);
            setLevel(GY - eased * (GY - y));
            if (t < 1) requestAnimationFrame(rise);
        };
        requestAnimationFrame(rise);
        $('curCm').textContent = `${fr(pierres)} pierres`;
        $('barFill').style.width = `${pct * 100}%`;
        const set = Math.round(pct * bricks.length);
        bricks.forEach((b, i) => setTimeout(() => b.classList.toggle('set', i < set), i * 8));

        countUp($('raisedTxt'), pierres, (v) => `${fr(v)} <small>pierres</small>`);
        countUp($('pctTxt'), pct * 100, (v) => `${Math.round(v)} %`);
        countUp($('pierres'), pierres, fr);
        countUp($('wallPierres'), pierres, fr);
        countUp($('mursDone'), murs, fr);
        const wp = $('wallPct');
        if (wp) wp.textContent = `${Math.round(pct * 100)} %`;
    };

    const io = new IntersectionObserver((entries) => {
        if (entries.some((e) => e.isIntersecting)) {
            play();
            io.disconnect();
        }
    }, { threshold: 0.2 });
    io.observe(section);
}
