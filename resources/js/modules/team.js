// Fiches de l'équipe (article HTML) : un clic sur un portrait ouvre une modale
// avec le rôle et la biographie, lus dans les <span> cachés de la carte.

export function initTeam(Modal) {
    const members = document.querySelectorAll('.prose .member');
    if (!members.length) return;

    const el = document.createElement('div');
    el.className = 'modal fade member-modal';
    el.tabIndex = -1;
    el.setAttribute('aria-hidden', 'true');
    el.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                <div class="mcard-top">
                    <img src="" alt="">
                    <div><span class="mrole"></span><h3></h3></div>
                </div>
                <div class="mcard-bio"></div>
            </div>
        </div>`;
    document.body.appendChild(el);
    const modal = new Modal(el);

    members.forEach((m) => m.addEventListener('click', () => {
        const name = m.querySelector('.nm')?.textContent ?? '';
        const img = el.querySelector('img');
        img.src = m.querySelector('img')?.src ?? '';
        img.alt = name;
        el.querySelector('h3').textContent = name;
        el.querySelector('.mrole').textContent = m.querySelector('.member-role')?.textContent ?? '';
        el.querySelector('.mcard-bio').textContent = m.querySelector('.member-bio')?.textContent || 'Présentation à venir.';
        modal.show();
    }));
}
