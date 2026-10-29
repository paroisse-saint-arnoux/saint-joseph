@vite(['resources/js/admin/cropper.js'])
<style>
    /* Barre « Enregistrer / Annuler » fixée en bas (pages avec $formActionsAreSticky) : seulement quand le
       formulaire a des modifications non enregistrées. Sinon, elle reste à sa place, en bas du formulaire. */
    body:not(.sj-form-dirty) .fi-sticky .fi-ac {
        position: static; width: auto !important; margin: 0; padding: 0; background: none; box-shadow: none; border-radius: 0;
    }
    body.sj-form-dirty .fi-sticky .fi-ac { box-shadow: 0 -4px 24px rgba(0, 32, 78, .18), 0 0 0 2px #C5A24B; }
</style>
<script>
    // Insère du Markdown à l'endroit du curseur de l'éditeur d'article (EasyMDE / CodeMirror de Filament),
    // appelé par l'action « Choisir dans la bibliothèque » (App\Filament\Forms\LibraryActions::markdown).
    window.sjInsertMarkdown = (text) => {
        const root = [...document.querySelectorAll('[x-data]')].find((el) => el._editor);
        if (! root) return;
        const codemirror = Alpine.raw(root._editor).codemirror;
        codemirror.focus();
        codemirror.replaceSelection(text);
    };

    // Formulaire modifié ? Même calcul que l'alerte « modifications non enregistrées » de Filament :
    // empreinte des données du formulaire comparée à celle des données enregistrées.
    (() => {
        let timer = null;
        const update = () => {
            // ($wire renvoie une fonction pour une propriété inconnue : on vérifie les types.)
            const component = window.Livewire?.all().find((c) => typeof c.$wire?.savedDataHash === 'string' && typeof c.$wire?.data === 'object');
            const dirty = !! component && !! window.jsMd5
                && window.jsMd5(JSON.stringify(component.$wire.data).replace(/\\/g, '')) !== component.$wire.savedDataHash;
            document.body.classList.toggle('sj-form-dirty', dirty);
        };
        const schedule = () => { clearTimeout(timer); timer = setTimeout(update, 150); };

        ['input', 'change', 'click', 'keyup'].forEach((type) => document.addEventListener(type, schedule, true));
        const hook = () => window.Livewire.hook('commit', ({ succeed }) => succeed(() => setTimeout(update, 50)));
        window.Livewire ? hook() : document.addEventListener('livewire:init', hook);
        document.addEventListener('livewire:navigated', schedule);
        schedule();
    })();
</script>
