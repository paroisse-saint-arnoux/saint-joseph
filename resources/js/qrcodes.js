// Page /qrcodes : lien court + QR code d'une page du site avec balises UTM (QrCodeController).
import '@fontsource/cormorant-garamond/latin-500.css';
import '@fontsource/cormorant-garamond/latin-600.css';
import '@fontsource-variable/hanken-grotesk/index.css';

const form = document.getElementById('generateur');
if (form) {
    const $ = (id) => document.getElementById(id);
    const result = form.querySelector('.l-result');
    const error = form.querySelector('.l-error');
    const submit = form.querySelector('button[type=submit]');
    const csrf = document.querySelector('meta[name=csrf-token]').content;

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

    // Campagne proposée selon la page visée, tant qu'on ne l'a pas changée soi-même.
    const campaign = $('l-campaign');
    let campaignEdited = false;
    const suggestCampaign = () => {
        if (!campaignEdited) campaign.value = $('l-target').selectedOptions[0]?.dataset.campaign || '';
    };
    campaign.addEventListener('input', () => { campaignEdited = campaign.value.trim() !== ''; });
    $('l-target').addEventListener('change', suggestCampaign);
    suggestCampaign();

    // Le résultat affiché ne correspond plus aux choix dès qu'on les modifie.
    const reset = () => { result.hidden = true; error.hidden = true; };
    form.addEventListener('input', reset);
    form.addEventListener('change', reset);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        reset();
        submit.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: new FormData(form),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || (response.status === 429 ? 'Trop de QR codes créés : réessayez dans quelques minutes.' : 'Le QR code n\'a pas pu être créé.'));
            }
            form.querySelector('.l-qr').innerHTML = data.svg;
            $('l-short').value = data.short_url;
            $('l-url').value = data.url;
            $('l-png').href = data.png;
            $('l-png').download = `${data.filename}.png`;
            $('l-svg').href = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(data.svg)}`;
            $('l-svg').download = `${data.filename}.svg`;
            result.hidden = false;
            result.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (err) {
            error.textContent = err.message || 'Le QR code n\'a pas pu être créé.';
            error.hidden = false;
        } finally {
            submit.disabled = false;
        }
    });

    form.querySelectorAll('[data-copy]').forEach((b) => b.addEventListener('click', () => copy($(b.dataset.copy).value, b)));
    ['l-short', 'l-url'].forEach((id) => $(id).addEventListener('focus', (e) => e.target.select()));

    // « Derniers liens créés » : remet les choix dans le formulaire et réaffiche le QR code (même lien, même code).
    document.querySelectorAll('.l-copy-row[data-target]').forEach((b) => b.addEventListener('click', () => {
        $('l-target').value = b.dataset.target;
        $('l-source').value = b.dataset.source;
        campaign.value = b.dataset.campaign;
        campaignEdited = true;
        $('l-content').value = b.dataset.content;
        form.requestSubmit();
        form.scrollIntoView({ behavior: 'smooth' });
    }));
}
