<x-filament-panels::page>
    <style>
        .lib-toolbar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .lib-toolbar .lib-search { flex: 1 1 280px; }
        .lib-count { color: #6b7280; font-size: .875rem; }
        .lib-drop { position: relative; min-height: 240px; border-radius: 14px; }
        .lib-drop.is-over { outline: 3px dashed #C5A24B; outline-offset: 6px; }
        .lib-drop-overlay { position: absolute; inset: 0; z-index: 5; display: grid; place-items: center; border-radius: 14px;
            background: rgba(246, 241, 230, .92); color: #00204E; font-weight: 600; font-size: 1.15rem; pointer-events: none; text-align: center; padding: 20px; }
        .lib-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 16px; }
        .lib-card { display: flex; flex-direction: column; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; transition: box-shadow .2s, border-color .2s; }
        .lib-card:hover { border-color: #C5A24B; box-shadow: 0 8px 24px rgba(0, 32, 78, .12); }
        .lib-thumb { position: relative; aspect-ratio: 4 / 3; background: #f3f4f6; }
        .lib-thumb img { position: absolute; inset: 6px; width: calc(100% - 12px); height: calc(100% - 12px); object-fit: contain; }
        .lib-body { padding: 10px 12px 12px; }
        .lib-title { font-weight: 600; font-size: .9rem; line-height: 1.3; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .lib-meta { color: #6b7280; font-size: .75rem; margin-top: 4px; }
        .lib-used { color: #8a6d1f; }
        .lib-empty { text-align: center; color: #6b7280; padding: 60px 20px; border: 2px dashed #e5e7eb; border-radius: 14px; }
        .lib-card { position: relative; }
        .lib-card.is-selected { border-color: #00204E; box-shadow: 0 0 0 2px #00204E; }
        .lib-check { position: absolute; top: 8px; left: 8px; z-index: 2; width: 26px; height: 26px; border-radius: 7px; border: 2px solid #fff;
            background: rgba(0, 0, 0, .25); color: transparent; display: grid; place-items: center; font-size: 15px; font-weight: 700; line-height: 1;
            opacity: 0; transition: opacity .15s; box-shadow: 0 1px 4px rgba(0, 0, 0, .3); }
        .lib-card:hover .lib-check, .lib-selecting .lib-check { opacity: 1; }
        .lib-card.is-selected .lib-check { background: #00204E; border-color: #00204E; color: #fff; opacity: 1; }
        .lib-selbar { position: sticky; top: 72px; z-index: 20; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
            background: #00204E; color: #fff; border-radius: 12px; padding: 10px 14px; box-shadow: 0 8px 24px rgba(0, 32, 78, .25); }
        .lib-selbar strong { margin-right: auto; }
        .lib-selbar .lib-link { color: #E2C97F; font-size: .875rem; text-decoration: underline; background: none; border: 0; cursor: pointer; }
        .lib-more { display: flex; justify-content: center; padding: 20px; color: #6b7280; font-size: .875rem; }
    </style>

    <div class="lib-toolbar">
        <div class="lib-search">
            <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                <x-filament::input type="search" wire:model.live.debounce.400ms="search"
                    placeholder="Rechercher : titre, description, nom de fichier ou article qui utilise l'image" />
            </x-filament::input.wrapper>
        </div>
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="sort" aria-label="Trier">
                @foreach ($sorts as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
        <span class="lib-count">{{ $total }} image{{ $total > 1 ? 's' : '' }}</span>
    </div>

    {{-- Sélection multiple : état partagé avec Livewire ($selected), utilisé par la suppression groupée. --}}
    <div x-data="{
            selected: $wire.entangle('selected'),
            toggle(id) { this.selected = this.selected.includes(id) ? this.selected.filter((i) => i !== id) : [...this.selected, id] },
            isSelected(id) { return this.selected.includes(id) },
            selectAll() { this.selected = [...document.querySelectorAll('[data-image-id]')].map((el) => Number(el.dataset.imageId)) },
        }" x-bind:class="{ 'lib-selecting': selected.length }" style="display:flex; flex-direction:column; gap:16px;">

    <div class="lib-selbar" x-show="selected.length" x-cloak>
        <strong x-text="selected.length + (selected.length > 1 ? ' images sélectionnées' : ' image sélectionnée')"></strong>
        <button type="button" class="lib-link" x-on:click="selectAll()">Tout sélectionner</button>
        <button type="button" class="lib-link" x-on:click="selected = []">Annuler</button>
        <x-filament::button tag="a" size="sm" color="gray" icon="heroicon-o-arrow-down-tray"
            x-bind:href="'{{ route('library.archive') }}?ids=' + selected.join(',')">
            Télécharger
        </x-filament::button>
        {{ $this->deleteSelectedAction }}
    </div>

    {{-- Glisser-déposer : les fichiers sont envoyés par Livewire dans $dropped, puis ajoutés (updatedDropped). --}}
    <div class="lib-drop"
        x-data="{ depth: 0, uploading: false, progress: 0 }"
        x-bind:class="{ 'is-over': depth > 0 }"
        x-on:dragenter.prevent="depth++"
        x-on:dragover.prevent
        x-on:dragleave.prevent="depth = Math.max(0, depth - 1)"
        x-on:drop.prevent="
            depth = 0;
            const files = [...$event.dataTransfer.files];
            if (! files.length) return;
            uploading = true; progress = 0;
            $wire.uploadMultiple('dropped', files,
                () => { uploading = false },
                () => { uploading = false },
                (e) => { progress = e.detail.progress });
        ">
        <div class="lib-drop-overlay" x-show="depth > 0 || uploading" x-cloak>
            <span x-show="! uploading">Déposez vos photos ou votre document Word ici</span>
            <span x-show="uploading" x-text="progress < 100 ? 'Envoi… ' + progress + ' %' : 'Analyse des images…'"></span>
        </div>

        @if ($images->isEmpty())
            <div class="lib-empty">
                @if ($search !== '')
                    Aucune image ne correspond à « {{ $search }} ».
                @else
                    La bibliothèque est vide. Glissez vos photos ici, ou cliquez sur « Ajouter des images ».
                @endif
            </div>
        @else
            <div class="lib-grid">
                @foreach ($images as $image)
                    <a class="lib-card" href="{{ \App\Filament\Resources\LibraryImages\LibraryImageResource::getUrl('edit', ['record' => $image]) }}"
                        wire:key="image-{{ $image->id }}" data-image-id="{{ $image->id }}"
                        x-bind:class="{ 'is-selected': isSelected({{ $image->id }}) }"
                        x-on:click="if (selected.length) { $event.preventDefault(); toggle({{ $image->id }}) }">
                        <button type="button" class="lib-check" aria-label="Sélectionner" x-on:click.prevent.stop="toggle({{ $image->id }})">✓</button>
                        <div class="lib-thumb"><img src="{{ $image->thumbUrl() }}" alt="{{ $image->alt }}" loading="lazy"></div>
                        <div class="lib-body">
                            <div class="lib-title">{{ $image->title }}</div>
                            <div class="lib-meta">{{ $image->dimensions() }} px · {{ $image->humanSize() }}</div>
                            <div class="lib-meta {{ $image->usages_count ? 'lib-used' : '' }}">
                                @if ($image->usages_count)
                                    Utilisée {{ $image->usages_count }} fois · {{ \Illuminate\Support\Carbon::parse($image->usages_max_created_at)->format('d/m/Y') }}
                                @else
                                    Non utilisée · ajoutée le {{ $image->created_at->format('d/m/Y') }}
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if ($hasMore)
                <div class="lib-more" x-data x-intersect.margin.400px="$wire.loadMore()" wire:key="more-{{ $images->count() }}">
                    <x-filament::loading-indicator class="h-5 w-5" /> &nbsp;Chargement…
                </div>
            @endif
        @endif
    </div>
    </div>
</x-filament-panels::page>
