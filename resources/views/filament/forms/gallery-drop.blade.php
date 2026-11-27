{{--
    Zone d'ajout de photos à la galerie : glisser-déposer ou clic (DropsGalleryPhotos).
    Les photos partent par paquets (5 photos et 20 Mo au plus), l'un après l'autre : PHP refuse plus de 20 fichiers
    par envoi (max_file_uploads) et limite le poids d'un envoi (post_max_size). Chaque paquet est ajouté à la galerie
    dès son arrivée.
--}}
<div
    x-data="{
        depth: 0, uploading: false, done: 0, total: 0, progress: 0, failed: 0,
        batches(files) {
            const batches = [];
            let batch = [], size = 0;
            for (const file of files) {
                if (batch.length && (batch.length >= 5 || size + file.size > 20 * 1024 * 1024)) {
                    batches.push(batch); batch = []; size = 0;
                }
                batch.push(file); size += file.size;
            }
            if (batch.length) batches.push(batch);
            return batches;
        },
        async send(files) {
            files = [...files].filter((f) => f.type.startsWith('image/'));
            if (! files.length || this.uploading) return;
            this.uploading = true; this.done = 0; this.total = files.length; this.failed = 0;
            for (const batch of this.batches(files)) {
                this.progress = 0;
                await new Promise((resolve) => $wire.uploadMultiple('galleryDrop', batch,
                    () => resolve(),
                    () => { this.failed += batch.length; resolve() },
                    (e) => { this.progress = e.detail.progress }));
                this.done += batch.length;
            }
            this.uploading = false;
        },
    }"
    x-on:dragenter.prevent="depth++"
    x-on:dragover.prevent
    x-on:dragleave.prevent="depth = Math.max(0, depth - 1)"
    x-on:drop.prevent="depth = 0; send($event.dataTransfer.files)"
    x-on:click="if (! uploading) $refs.input.click()"
    x-bind:class="{ 'is-over': depth > 0, 'is-busy': uploading }"
    class="sj-gallery-drop"
    role="button"
    tabindex="0"
    x-on:keydown.enter.prevent="$refs.input.click()"
>
    <input type="file" multiple accept="image/jpeg,image/png,image/webp,image/gif" hidden x-ref="input"
        x-on:click.stop x-on:change="send($event.target.files); $event.target.value = ''">
    <x-filament::icon icon="heroicon-o-photo" class="sj-gallery-drop-icon" />
    <span x-show="! uploading">
        <strong>Glissez ici une ou plusieurs photos</strong> ou cliquez pour les choisir.<br>
        Elles s'ajoutent à la fin de la galerie ; une photo déjà présente n'est pas ajoutée deux fois.
    </span>
    <span x-show="uploading" x-cloak x-text="'Envoi des photos… ' + Math.min(total, done + Math.round(progress / 100 * Math.min(5, total - done))) + ' / ' + total"></span>
    <span x-show="! uploading && failed" x-cloak class="sj-gallery-drop-error"
        x-text="failed + (failed > 1 ? ' photos n\'ont pas pu être envoyées (30 Mo maximum par photo).' : ' photo n\'a pas pu être envoyée (30 Mo maximum par photo).')"></span>
    @if ($message = $errors->first('galleryDrop.*')) <span class="sj-gallery-drop-error">{{ $message }}</span> @endif
</div>

<style>
    .sj-gallery-drop { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; border: 2px dashed rgb(0 32 78 / .25);
        border-radius: .75rem; cursor: pointer; font-size: .875rem; line-height: 1.5; color: rgb(55 65 81); }
    .dark .sj-gallery-drop { color: rgb(209 213 219); border-color: rgb(255 255 255 / .2); }
    .sj-gallery-drop:hover, .sj-gallery-drop.is-over { border-color: #C5A24B; background: rgb(197 162 75 / .08); }
    .sj-gallery-drop.is-busy { cursor: progress; }
    .sj-gallery-drop-icon { width: 2rem; height: 2rem; flex: none; color: #C5A24B; }
    .sj-gallery-drop-error { display: block; color: rgb(220 38 38); }
</style>
