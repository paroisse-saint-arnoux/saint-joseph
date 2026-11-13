// Derniers donateurs (campagne RGIVE) sous « Quel bâtisseur voulez-vous être ? » :
// une seule ligne visible, qui passe à la suivante toutes les 3 secondes.
const DELAY = 3000;

export function initDonors() {
    document.querySelectorAll('[data-donors-ticker]').forEach((ticker) => {
        const items = [...ticker.querySelectorAll('li')];
        if (items.length < 2) return;

        let i = 0;
        let timer = null;
        const next = () => {
            items[i].classList.remove('is-on');
            i = (i + 1) % items.length;
            items[i].classList.add('is-on');
        };
        const start = () => { if (!timer) timer = setInterval(next, DELAY); };
        const stop = () => { clearInterval(timer); timer = null; };

        // Pause au survol, et quand l'onglet est caché
        ticker.addEventListener('mouseenter', stop);
        ticker.addEventListener('mouseleave', start);
        document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
        start();
    });
}
