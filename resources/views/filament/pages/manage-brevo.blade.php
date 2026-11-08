@php
    $n = fn ($v) => number_format((int) $v, 0, ',', ' ');
    $pct = fn (int $a, int $b) => $b > 0 ? number_format(100 * $a / $b, 1, ',', ' ').' %' : '—';
    $lists = $stats['lists'];
    $c30 = collect($campaigns)->filter(fn ($c) => $c['sent_at']?->gte(now()->subDays(30)));
@endphp
<x-filament-panels::page>
    <style>
        .bv-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        .bv-tile { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 14px 16px; }
        .bv-tile b { display: block; font-size: 1.9rem; line-height: 1.1; color: #00204E; font-variant-numeric: tabular-nums; }
        .bv-tile span { color: #6b7280; font-size: .9rem; }
        .bv-tile small { display: block; color: #9a7b2c; font-size: .8rem; margin-top: 4px; }
        .bv-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        .bv-table th { text-align: left; font-weight: 600; color: #6b7280; padding: 8px 10px; border-bottom: 1px solid #eee; white-space: nowrap; }
        .bv-table td { padding: 8px 10px; border-bottom: 1px solid #f3f3f3; vertical-align: middle; }
        .bv-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .bv-up { color: #047857; }
        .bv-wrap { overflow-x: auto; }
        .bv-muted { color: #6b7280; font-size: .85rem; }
        .bv-spark { display: flex; align-items: flex-end; gap: 2px; height: 28px; min-width: 120px; }
        .bv-spark span { flex: 1; background: #C5A24B; border-radius: 2px 2px 0 0; min-height: 1px; }
    </style>

    @unless ($configured)
        <x-filament::section>
            <p class="bv-muted">Brevo n'est pas configuré : ajoutez BREVO_API_KEY dans le fichier .env du serveur.</p>
        </x-filament::section>
    @else
        <div class="bv-tiles">
            <div class="bv-tile"><b>{{ $n(collect($lists)->sum('new_30')) }}</b><span>nouveaux contacts en 30 jours</span><small>dont {{ $n(collect($lists)->sum('new_7')) }} cette semaine (toutes listes)</small></div>
            <div class="bv-tile"><b>{{ $n($c30->count()) }}</b><span>campagnes envoyées en 30 jours</span><small>{{ $n($c30->sum('sent')) }} emails · {{ $pct($c30->sum('opens'), $c30->sum('delivered')) }} d'ouverture</small></div>
            <div class="bv-tile"><b>{{ $n($transactional['requests']) }}</b><span>emails transactionnels en 30 jours</span><small>{{ $pct($transactional['delivered'], $transactional['requests']) }} délivrés · {{ $n($transactional['bounces'] + $transactional['blocked']) }} rejetés</small></div>
        </div>

        <x-filament::section>
            <x-slot name="heading">Listes de contacts</x-slot>
            <x-slot name="description">Nouveaux contacts d'après leur date de création dans Brevo. La courbe : nombre de contacts à la fin de chacune des {{ count($stats['weeks']) }} dernières semaines.</x-slot>
            <div class="bv-wrap">
                <table class="bv-table">
                    <thead><tr><th>Liste</th><th class="bv-num">Contacts</th><th class="bv-num">7 jours</th><th class="bv-num">30 jours</th><th class="bv-num">Désinscrits</th><th>{{ count($stats['weeks']) }} semaines</th></tr></thead>
                    <tbody>
                        @forelse ($lists as $list)
                            @php($max = max($list['size']) ?: 1)
                            <tr>
                                <td><strong>{{ $list['name'] }}</strong> <span class="bv-muted">n° {{ $list['id'] }}{{ isset($listIds[$list['id']]) ? ' · '.$listIds[$list['id']] : '' }}</span></td>
                                <td class="bv-num"><strong>{{ $n($list['subscribers']) }}</strong></td>
                                <td class="bv-num {{ $list['new_7'] ? 'bv-up' : '' }}">{{ $list['new_7'] ? '+'.$n($list['new_7']) : '—' }}</td>
                                <td class="bv-num {{ $list['new_30'] ? 'bv-up' : '' }}">{{ $list['new_30'] ? '+'.$n($list['new_30']) : '—' }}</td>
                                <td class="bv-num">{{ $n($list['blacklisted']) }}</td>
                                <td>
                                    <div class="bv-spark" role="img" aria-label="Évolution sur {{ count($list['size']) }} semaines : de {{ $list['size'][0] ?? 0 }} à {{ end($list['size']) }} contacts">
                                        @foreach ($list['size'] as $i => $v)
                                            <span style="height: {{ max(4, round(100 * $v / $max)) }}%" title="Semaine du {{ $stats['weeks'][$i]->translatedFormat('j M') }} : {{ $v }} contacts (+{{ $list['new'][$i] }})"></span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="bv-muted">Aucune liste (ou Brevo ne répond pas pour le moment).</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Campagnes envoyées</x-slot>
            <x-slot name="description">Ouvertures et clics uniques, rapportés aux emails délivrés. Les ouvertures sont sous-estimées (images bloquées) ou surestimées (Apple Mail).</x-slot>
            <div class="bv-wrap">
                <table class="bv-table">
                    <thead><tr><th>Date</th><th>Campagne</th><th class="bv-num">Envoyés</th><th class="bv-num">Délivrés</th><th class="bv-num">Ouvertures</th><th class="bv-num">Clics</th><th class="bv-num">Désinscriptions</th><th class="bv-num">Rebonds</th></tr></thead>
                    <tbody>
                        @forelse ($campaigns as $c)
                            <tr>
                                <td class="bv-muted" style="white-space: nowrap">{{ $c['sent_at']?->translatedFormat('j M Y') ?? '—' }}</td>
                                <td><strong>{{ $c['name'] }}</strong><div class="bv-muted">{{ $c['subject'] }}</div></td>
                                <td class="bv-num">{{ $n($c['sent']) }}</td>
                                <td class="bv-num">{{ $n($c['delivered']) }}</td>
                                <td class="bv-num">{{ $n($c['opens']) }} <span class="bv-muted">{{ $pct($c['opens'], $c['delivered']) }}</span></td>
                                <td class="bv-num">{{ $n($c['clicks']) }} <span class="bv-muted">{{ $pct($c['clicks'], $c['delivered']) }}</span></td>
                                <td class="bv-num">{{ $n($c['unsubscriptions']) }}</td>
                                <td class="bv-num">{{ $n($c['bounces']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="bv-muted">Aucune campagne envoyée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endunless
</x-filament-panels::page>
