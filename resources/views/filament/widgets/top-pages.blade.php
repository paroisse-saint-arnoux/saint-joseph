<x-filament-widgets::widget>
    <x-filament::section heading="Pages les plus vues (30 derniers jours)">
        @if (! $configured)
            <p style="color:#6b7280; font-size:.875rem;">Statistiques non configurées : ajoutez MATOMO_TOKEN dans le fichier .env du serveur.</p>
        @elseif (empty($pages))
            <p style="color:#6b7280; font-size:.875rem;">Pas encore de données.</p>
        @else
            @php($max = max(array_column($pages, 'pageviews')) ?: 1)
            <div style="display:grid; gap:10px;">
                @foreach ($pages as $page)
                    <div>
                        <div style="display:flex; justify-content:space-between; gap:12px; font-size:.875rem;">
                            @if ($page['url'])
                                <a href="{{ $page['url'] }}" target="_blank" rel="noopener" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $page['label'] }}</a>
                            @else
                                <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $page['label'] }}</span>
                            @endif
                            <strong>{{ number_format($page['pageviews'], 0, ',', ' ') }}</strong>
                        </div>
                        <div style="height:6px; border-radius:3px; background:#F6F1E6; margin-top:4px;">
                            <div style="height:6px; border-radius:3px; background:#C5A24B; width:{{ round($page['pageviews'] / $max * 100) }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
