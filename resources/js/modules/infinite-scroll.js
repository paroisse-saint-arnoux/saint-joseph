// Défilement infini de la liste des articles : la page suivante est chargée
// quand le lien « Voir plus d'articles » approche de l'écran (il reste cliquable sans JS).

export function initInfiniteScroll() {
    const grid = document.getElementById('articles-grid');
    const more = document.getElementById('load-more');
    if (!grid || !more) return;

    const link = more.querySelector('a');
    const loader = document.getElementById('loader');
    let next = link?.href;
    let loading = false;

    const load = async () => {
        if (loading || !next) return;
        loading = true;
        loader?.classList.add('on');
        try {
            const res = await fetch(next, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
            const data = await res.json();
            grid.insertAdjacentHTML('beforeend', data.html);
            next = data.next;
            if (!next) {
                io.disconnect();
                more.remove();
            }
        } catch {
            // En cas d'erreur, le lien reste disponible pour un chargement classique.
        } finally {
            loading = false;
            loader?.classList.remove('on');
        }
    };

    link?.addEventListener('click', (e) => {
        e.preventDefault();
        load();
    });

    const io = new IntersectionObserver((entries) => {
        if (entries.some((e) => e.isIntersecting)) load();
    }, { rootMargin: '400px' });
    io.observe(more);
}
