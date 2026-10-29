// Boutons de partage : partage natif sur mobile, copie du lien sinon.

export function initShare() {
    document.querySelectorAll('[data-share-native]').forEach((btn) => {
        if (!navigator.share) {
            btn.remove();
            return;
        }
        btn.addEventListener('click', () => navigator.share({ title: document.title, url: btn.dataset.url }).catch(() => {}));
    });

    document.querySelectorAll('[data-copy-link]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(btn.dataset.url);
                const label = btn.querySelector('span');
                const old = label.textContent;
                label.textContent = 'Lien copié !';
                setTimeout(() => (label.textContent = old), 2000);
            } catch {
                window.prompt('Copiez ce lien :', btn.dataset.url);
            }
        });
    });
}
