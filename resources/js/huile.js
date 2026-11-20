// Pages /huile et /commande-huile : polices auto-hébergées, carrousel de la récolte, choix des bouteilles.
import '@fontsource-variable/fraunces/opsz.css';
import '@fontsource-variable/hanken-grotesk/index.css';

// Carrousel : la bande défile au doigt (scroll-snap) ; boutons précédent / suivant pour la souris.
document.querySelectorAll('[data-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('.track');
    const buttons = ['prev', 'next'].map((dir) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = `nav ${dir}`;
        b.setAttribute('aria-label', dir === 'prev' ? 'Photo précédente' : 'Photo suivante');
        b.textContent = dir === 'prev' ? '‹' : '›';
        b.addEventListener('click', () => track.scrollBy({ left: (dir === 'prev' ? -1 : 1) * track.clientWidth * 0.8, behavior: 'smooth' }));
        carousel.append(b);
        return b;
    });
    const update = () => {
        buttons[0].disabled = track.scrollLeft < 4;
        buttons[1].disabled = track.scrollLeft + track.clientWidth > track.scrollWidth - 4;
    };
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    track.querySelectorAll('img').forEach((img) => img.addEventListener('load', update));
    update();
});

// Réservation : boutons − / + (grandes zones à toucher) et total.
document.querySelectorAll('[data-oil-order]').forEach((form) => {
    const inputs = [...form.querySelectorAll('input[data-price]')];
    const total = form.querySelector('[data-total]');
    const euros = (cents) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: cents % 100 ? 2 : 0 }).format(cents / 100);

    const refresh = () => {
        let sum = 0;
        inputs.forEach((input) => {
            const max = Number(input.max) || 0;
            const value = Math.min(max, Math.max(0, parseInt(input.value, 10) || 0));
            sum += value * Number(input.dataset.price);
            const [minus, plus] = input.parentElement.querySelectorAll('.step');
            minus.disabled = value <= 0;
            plus.disabled = value >= max;
        });
        total.textContent = euros(sum);
        total.parentElement.hidden = sum === 0;
    };

    inputs.forEach((input) => {
        input.parentElement.querySelectorAll('.step').forEach((button) => {
            button.hidden = false;
            button.addEventListener('click', () => {
                const max = Number(input.max) || 0;
                input.value = Math.min(max, Math.max(0, (parseInt(input.value, 10) || 0) + Number(button.dataset.step)));
                refresh();
            });
        });
        input.addEventListener('input', refresh);
        input.addEventListener('change', () => {
            input.value = Math.min(Number(input.max) || 0, Math.max(0, parseInt(input.value, 10) || 0));
            refresh();
        });
    });
    refresh();
});

// Après une réservation, la confirmation est mise en avant.
document.querySelector('.done')?.focus();
