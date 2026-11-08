// Consultation sur les projets : chaque réponse est enregistrée aussitôt (la page fonctionne aussi sans JS).
import '@fontsource-variable/fraunces/opsz.css';
import '@fontsource-variable/hanken-grotesk/index.css';

const form = document.querySelector('form[data-vote]');

if (form) {
    const status = form.querySelector('[data-status]');
    const details = form.querySelector('[data-details]');
    const comment = form.querySelector('textarea[name=comment]');
    const token = form.querySelector('input[name=_token]').value;
    let pending = {};
    let sending = false;
    let retryTimer = null;
    let commentTimer = null;

    const show = (text, error = false) => {
        status.textContent = text;
        status.classList.toggle('is-error', error);
    };

    async function flush(keepalive = false) {
        if (sending || Object.keys(pending).length === 0) return;
        clearTimeout(retryTimer);
        retryTimer = null;
        const fields = pending;
        pending = {};
        sending = true;
        show('Enregistrement…');

        const body = new FormData();
        body.append('_token', token);
        Object.entries(fields).forEach(([k, v]) => body.append(k, v));

        try {
            const res = await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json' }, body, keepalive });
            const data = await res.json().catch(() => ({}));
            if (res.status === 409 && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            if (!res.ok) throw new Error(data.message || 'Erreur');
            show('Réponse enregistrée ✓');
        } catch {
            // Pas de réseau : on garde la réponse et on réessaie.
            pending = { ...fields, ...pending };
            show('Pas de connexion pour le moment : votre réponse sera envoyée dès que possible.', true);
            clearTimeout(retryTimer);
            retryTimer = setTimeout(flush, 5000);
        } finally {
            sending = false;
            if (Object.keys(pending).length && !retryTimer) flush();
        }
    }

    const queue = (name, value) => {
        pending[name] = value;
        flush();
    };

    form.querySelectorAll('[data-scale]').forEach((fieldset) => {
        const choice = fieldset.querySelector('[data-choice]');
        fieldset.addEventListener('change', (e) => {
            const input = e.target;
            choice.innerHTML = '';
            choice.append('Votre réponse : ');
            const b = document.createElement('b');
            b.textContent = input.dataset.text;
            choice.append(b);
            queue(input.name, input.value);

            if (input.name === 'overall' && details.hidden) {
                details.hidden = false;
                setTimeout(() => details.scrollIntoView({ behavior: 'smooth', block: 'start' }), 250);
            }
        });
    });

    comment.addEventListener('input', () => {
        clearTimeout(commentTimer);
        commentTimer = setTimeout(() => queue('comment', comment.value), 1200);
    });
    comment.addEventListener('change', () => {
        clearTimeout(commentTimer);
        queue('comment', comment.value);
    });

    window.addEventListener('online', () => flush());
    // On quitte la page (projet suivant, téléphone verrouillé…) : envoi de ce qui reste.
    const leave = () => {
        if (commentTimer) {
            clearTimeout(commentTimer);
            commentTimer = null;
            pending.comment = comment.value;
        }
        flush(true);
    };
    window.addEventListener('pagehide', leave);
    document.addEventListener('visibilitychange', () => document.visibilityState === 'hidden' && leave());
}
