// Galerie d'un article (carrousel Bootstrap #gallery) :
// - défilement automatique toutes les 3 s, arrêté définitivement dès que le
//   visiteur navigue lui-même (flèches, points, glissement du doigt, clavier) ;
// - un clic sur une photo (ou sur « Agrandir ») ouvre la galerie en plein écran,
//   sur la même photo ; en la refermant, le carrousel de la page s'y place aussi.

const INTERVAL = 3000;

export function initGallery(Carousel, Modal) {
    const el = document.getElementById('gallery');
    if (!el) return;

    const items = [...el.querySelectorAll('.carousel-item')];
    const carousel = Carousel.getOrCreateInstance(el, { interval: false, ride: false, pause: false, touch: true });
    const current = () => Math.max(0, items.findIndex((item) => item.classList.contains('active')));

    // Défilement automatique
    let timer = null;
    const stop = () => { clearInterval(timer); timer = null; };
    if (items.length > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        timer = setInterval(() => {
            if (!document.hidden) carousel.next();
        }, INTERVAL);
        el.querySelectorAll('[data-bs-slide], [data-bs-slide-to]').forEach((b) => b.addEventListener('click', stop));
        el.addEventListener('keydown', stop);
        // Glissement au doigt : Bootstrap ne dit pas si un « slide » vient de l'utilisateur,
        // on arrête donc au premier toucher.
        el.addEventListener('touchstart', stop, { passive: true });
    }

    // Plein écran
    const lightbox = buildLightbox(el, items);
    const big = Carousel.getOrCreateInstance(lightbox.querySelector('.carousel'), { interval: false, ride: false, touch: true });
    const modal = new Modal(lightbox);

    const open = () => {
        stop();
        big.to(current());
        modal.show();
    };
    lightbox.addEventListener('hide.bs.modal', () => {
        const i = [...lightbox.querySelectorAll('.carousel-item')].findIndex((item) => item.classList.contains('active'));
        if (i >= 0 && i !== current()) carousel.to(i);
    });
    // Flèches du clavier dans la fenêtre plein écran
    lightbox.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') big.prev();
        if (e.key === 'ArrowRight') big.next();
    });

    items.forEach((item) => item.querySelector('img')?.addEventListener('click', open));
    el.querySelector('[data-gallery-zoom]')?.addEventListener('click', open);
}

function buildLightbox(el, items) {
    const box = document.createElement('div');
    box.className = 'modal fade gallery-lightbox';
    box.tabIndex = -1;
    box.setAttribute('aria-hidden', 'true');
    box.setAttribute('aria-label', 'Galerie en plein écran');

    const several = items.length > 1;
    box.innerHTML = `
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                <div id="gallery-lightbox" class="carousel slide">
                    <div class="carousel-inner"></div>
                    ${several ? `
                        <button class="carousel-control-prev" type="button" data-bs-target="#gallery-lightbox" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Précédente</span></button>
                        <button class="carousel-control-next" type="button" data-bs-target="#gallery-lightbox" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Suivante</span></button>
                    ` : ''}
                </div>
            </div>
        </div>`;

    const inner = box.querySelector('.carousel-inner');
    items.forEach((item, i) => {
        const src = item.querySelector('img');
        const slide = document.createElement('div');
        slide.className = 'carousel-item' + (i === 0 ? ' active' : '');
        const img = document.createElement('img');
        img.src = src?.getAttribute('src') ?? '';
        img.alt = src?.getAttribute('alt') ?? '';
        img.loading = 'lazy';
        slide.appendChild(img);
        const caption = item.querySelector('.carousel-caption')?.textContent.trim();
        const legend = document.createElement('div');
        legend.className = 'gallery-lightbox-caption';
        legend.textContent = [caption, several ? `${i + 1} / ${items.length}` : ''].filter(Boolean).join(' · ');
        if (legend.textContent) slide.appendChild(legend);
        inner.appendChild(slide);
    });

    document.body.appendChild(box);
    return box;
}
