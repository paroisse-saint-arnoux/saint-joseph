// Envoi du formulaire newsletter sans recharger la page (il fonctionne aussi sans JS).

export function initNewsletter() {
    document.querySelectorAll('form[data-newsletter]').forEach((form) => {
        const feedback = form.querySelector('.form-feedback');
        const button = form.querySelector('button[type=submit]');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            button.disabled = true;
            feedback.textContent = '';
            feedback.className = 'form-feedback';
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: new FormData(form),
                });
                const data = await res.json();
                const ok = res.ok && data.ok;
                const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                feedback.textContent = firstError || data.message;
                feedback.classList.add(ok ? 'ok' : 'error');
                if (ok) form.reset();
            } catch {
                feedback.textContent = "L'inscription n'a pas pu aboutir. Merci de réessayer.";
                feedback.classList.add('error');
            } finally {
                button.disabled = false;
            }
        });
    });
}
