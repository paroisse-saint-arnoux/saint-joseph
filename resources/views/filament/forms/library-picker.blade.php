<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle(@js($getStatePath())),
            multiple: @js($isMultiple()),
            url: @js(route('library.search')),
            q: '', page: 1, items: [], hasMore: false, loading: false,
            init() {
                if (! Array.isArray(this.state)) this.state = [];
                this.load(true);
                this.$watch('q', Alpine.debounce(() => this.load(true), 350));
            },
            async load(reset) {
                if (reset) this.page = 1;
                this.loading = true;
                const response = await fetch(this.url + '?' + new URLSearchParams({ q: this.q, page: this.page }), { headers: { Accept: 'application/json' } });
                const data = await response.json();
                this.items = reset ? data.items : [...this.items, ...data.items];
                this.hasMore = data.hasMore;
                this.loading = false;
            },
            more() { this.page++; this.load(false) },
            toggle(id) {
                const on = this.state.includes(id);
                this.state = this.multiple ? (on ? this.state.filter((i) => i !== id) : [...this.state, id]) : (on ? [] : [id]);
            },
            rank(id) { return this.state.indexOf(id) + 1 },
        }"
        class="sj-picker"
    >
        <style>
            .sj-picker-bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 12px; }
            .sj-picker-bar > :first-child { flex: 1 1 260px; }
            .sj-picker-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; max-height: 55vh; overflow-y: auto; padding: 2px; }
            .sj-picker-item { position: relative; border: 2px solid transparent; border-radius: 10px; overflow: hidden; background: #fff; cursor: pointer; text-align: left; box-shadow: 0 0 0 1px #e5e7eb; }
            .sj-picker-item:hover { box-shadow: 0 0 0 1px #C5A24B; }
            .sj-picker-item.is-on { border-color: #00204E; box-shadow: none; }
            .sj-picker-thumb { position: relative; aspect-ratio: 4 / 3; background: #f3f4f6; }
            .sj-picker-thumb img { position: absolute; inset: 4px; width: calc(100% - 8px); height: calc(100% - 8px); object-fit: contain; }
            .sj-picker-title { font-size: .75rem; padding: 6px 8px; line-height: 1.25; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
            .sj-picker-rank { position: absolute; top: 6px; left: 6px; z-index: 1; min-width: 24px; height: 24px; border-radius: 12px; background: #00204E; color: #fff;
                font-size: .75rem; font-weight: 700; display: grid; place-items: center; padding: 0 6px; }
            .sj-picker-empty { color: #6b7280; padding: 30px; text-align: center; }
        </style>

        <div class="sj-picker-bar">
            <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                <x-filament::input type="search" x-model="q" placeholder="Rechercher : titre, description, article…" />
            </x-filament::input.wrapper>
            <x-filament::link :href="\App\Filament\Resources\LibraryImages\LibraryImageResource::getUrl()" target="_blank" icon="heroicon-o-arrow-top-right-on-square" size="sm">
                Ajouter des images à la bibliothèque
            </x-filament::link>
            <x-filament::link tag="button" type="button" x-on:click="load(true)" icon="heroicon-o-arrow-path" size="sm" color="gray">
                Actualiser
            </x-filament::link>
        </div>

        <div class="sj-picker-grid">
            <template x-for="item in items" x-bind:key="item.id">
                <button type="button" class="sj-picker-item" x-bind:class="{ 'is-on': state.includes(item.id) }" x-on:click="toggle(item.id)" x-bind:title="item.title + ' (' + item.dimensions + ' px)'">
                    <span class="sj-picker-rank" x-show="state.includes(item.id)" x-text="multiple ? rank(item.id) : '✓'"></span>
                    <div class="sj-picker-thumb"><img x-bind:src="item.thumb" x-bind:alt="item.alt || ''" loading="lazy"></div>
                    <div class="sj-picker-title" x-text="item.title"></div>
                </button>
            </template>
            <div class="sj-picker-empty" x-show="! loading && ! items.length" x-cloak>Aucune image trouvée.</div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px; font-size:.875rem; color:#6b7280;">
            <span x-text="state.length ? state.length + (state.length > 1 ? ' images choisies' : ' image choisie') + (multiple ? ' (dans l\'ordre des numéros)' : '') : (multiple ? 'Cliquez sur les images à ajouter.' : 'Cliquez sur une image.')"></span>
            <x-filament::button size="sm" color="gray" x-show="hasMore" x-on:click="more()" x-bind:disabled="loading">Voir plus d'images</x-filament::button>
        </div>
    </div>
</x-dynamic-component>
