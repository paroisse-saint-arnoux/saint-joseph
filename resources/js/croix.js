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
