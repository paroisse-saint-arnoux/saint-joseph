<x-filament-widgets::widget>
    <x-filament::section heading="Derniers articles">
        <x-slot name="afterHeader">
            <div style="display:flex; gap:8px;">
                <x-filament::button tag="a" :href="$listUrl" size="sm" color="gray">Tous les articles</x-filament::button>
                <x-filament::button tag="a" :href="$createUrl" size="sm" icon="heroicon-m-plus">Nouvel article</x-filament::button>
            </div>
        </x-slot>

        <style>
            .sj-latest { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; }
            .sj-latest a { display: flex; flex-direction: column; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; background: #fff; transition: border-color .2s, box-shadow .2s; }
            .sj-latest a:hover { border-color: #C5A24B; box-shadow: 0 8px 24px rgba(0, 32, 78, .1); }
            .sj-latest-thumb { aspect-ratio: 16 / 9; background: #F6F1E6 center / cover no-repeat; }
            .sj-latest-body { padding: 12px 14px 14px; display: grid; gap: 6px; }
            .sj-latest-title { font-weight: 600; line-height: 1.3; }
            .sj-latest-meta { font-size: .78rem; color: #6b7280; }
        </style>

        @if ($articles->isEmpty())
            <p style="color:#6b7280; font-size:.875rem;">Aucun article pour l'instant.</p>
        @else
            <div class="sj-latest">
                @foreach ($articles as $article)
                    <a href="{{ $article->adminUrl() }}">
                        <div class="sj-latest-thumb" style="background-image: url('{{ $article->ogImageUrl() ?? asset('images/og-saint-joseph.jpg') }}')"></div>
                        <div class="sj-latest-body">
                            <div><x-filament::badge :color="$article->status->getColor()" size="sm" style="display:inline-flex">{{ $article->status->getLabel() }}</x-filament::badge></div>
                            <div class="sj-latest-title">{{ $article->title }}</div>
                            <div class="sj-latest-meta">
                                {{ $article->category->getLabel() }} · créé le {{ $article->created_at->format('d/m/Y') }}{{ $article->author ? ' par '.$article->author->name : '' }}
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
