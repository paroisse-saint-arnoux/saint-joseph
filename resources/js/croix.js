// Page /croix (concours S.O.S Calvaires) : polices auto-hébergées.
import '@fontsource/cormorant-garamond/latin-500.css';
import '@fontsource/cormorant-garamond/latin-600.css';
import '@fontsource-variable/hanken-grotesk/index.css';

// Compte à rebours à côté des dates (ouverture puis clôture du vote). À zéro, la page se recharge pour passer à l'état suivant.
const countdown = document.querySelector('.countdown[data-until]');
if (countdown) {
    const until = new Date(countdown.dataset.until).getTime();
    const out = countdown.querySelector('b');
    const pad = (n) => String(n).padStart(2, '0');
    const tick = () => {
        const left = Math.max(0, Math.floor((until - Date.now()) / 1000));
        const d = Math.floor(left / 86400), h = Math.floor(left % 86400 / 3600), m = Math.floor(left % 3600 / 60), s = left % 60;
        out.textContent = `${d}\u00a0j ${pad(h)}\u00a0h ${pad(m)}\u00a0min ${pad(s)}\u00a0s`;
        if (left === 0) {
            clearInterval(timer);
            setTimeout(() => location.reload(), 1500);
        }
    };
    const timer = setInterval(tick, 1000);
    tick();
}

// Page /croix/liens : générateur de liens avec balises UTM (utm_campaign toujours « croix »).
const generator = document.getElementById('generateur');
if (generator) {
    const $ = (id) => document.getElementById(id);
    const slug = (s) => s.trim().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    const update = () => {
        const option = $('l-case').selectedOptions[0];
        const custom = option.value === '';
        generator.querySelector('.l-custom').hidden = !custom;
        const url = new URL(generator.querySelector('input[name=l-target]:checked').value);
        const source = custom ? slug($('l-source').value) : option.value;
        const medium = custom ? slug($('l-medium').value) : option.dataset.medium;
        const content = slug($('l-content').value);
        if (source) url.searchParams.set('utm_source', source);
        if (medium) url.searchParams.set('utm_medium', medium);
        url.searchParams.set('utm_campaign', 'croix');
        if (content) url.searchParams.set('utm_content', content);
        $('l-url').value = url.toString();
    };
    const copy = async (text, button) => {
        try {
            await navigator.clipboard.writeText(text);
        } catch {
            const field = Object.assign(document.createElement('textarea'), { value: text });
            document.body.append(field);
            field.select();
            document.execCommand('copy');
            field.remove();
        }
        const label = button.textContent;
        button.textContent = 'Copié ✓';
        setTimeout(() => { button.textContent = label; }, 1500);
    };
    generator.addEventListener('input', update);
    generator.addEventListener('change', update);
    $('l-copy').addEventListener('click', (e) => copy($('l-url').value, e.currentTarget));
    $('l-url').addEventListener('focus', (e) => e.target.select());
    document.querySelectorAll('.l-copy-row').forEach((b) => b.addEventListener('click', () => copy(b.dataset.url, b)));
    update();
}
