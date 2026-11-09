@use('App\Support\Croix')
@php
    $t = $funnel['total'];
    $pct = fn (int $n, int $of) => $of > 0 ? round(100 * $n / $of).' %' : '—';
    $sourceLabel = fn (?string $s) => $s ?? 'sans balise UTM';
@endphp
<x-filament-panels::page>
    <style>
        .cx-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; }
        .cx-tile { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 14px 16px; }
        .cx-tile b { display: block; font-size: 1.9rem; line-height: 1.1; color: #00204E; font-variant-numeric: tabular-nums; }
        .cx-tile span { color: #6b7280; font-size: .9rem; }
        .cx-tile small { display: block; color: #9a7b2c; font-size: .8rem; margin-top: 4px; }
        .cx-state { border-radius: 10px; padding: 10px 14px; border: 1px solid; }
        .cx-state.before { background: #fffbeb; color: #78350f; border-color: #fde68a; }
        .cx-state.open { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
        .cx-state.closed { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
        .cx-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        .cx-table th { text-align: left; font-weight: 600; color: #6b7280; padding: 8px 10px; border-bottom: 1px solid #eee; white-space: nowrap; }
        .cx-table td { padding: 8px 10px; border-bottom: 1px solid #f3f3f3; vertical-align: middle; }
        .cx-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .cx-wrap { overflow-x: auto; }
        .cx-muted { color: #6b7280; font-size: .85rem; }
    </style>

    <div @class(['cx-state', $state])>
        @if ($state === 'before')
            <strong>Avant l'ouverture du vote.</strong> La page propose de laisser ses coordonnées pour recevoir le lien.
            Le {{ $opensAt->translatedFormat('l j F à H\hi') }}, elle passera d'elle-même en mode vote (formulaire qui renvoie vers le site de vote, lien « Aller sur la page de vote sans m'inscrire », bandeau sur l'accueil).
        @elseif ($state === 'open')
            <strong>Le vote est ouvert</strong> jusqu'au {{ $closesAt->translatedFormat('l j F à H\hi') }} : le formulaire renvoie vers le site de vote, lien « Aller sur la page de vote sans m'inscrire »{{ Croix::showHomeBanner() ? ', bandeau sur l\'accueil' : '' }}.
        @else
            <strong>Le vote est clos</strong> depuis le {{ $closesAt->translatedFormat('j F à H\hi') }}. La page propose de laisser ses coordonnées pour recevoir le résultat.
        @endif
    </div>

    <div class="cx-tiles">
        <div class="cx-tile"><b>{{ $t['visitors'] }}</b><span>personnes ont ouvert la page</span><small>{{ $t['pageviews'] }} affichages au total</small></div>
        <div class="cx-tile"><b>{{ $t['signups'] }}</b><span>ont laissé leurs coordonnées</span><small>{{ $pct($t['signups'], $t['visitors']) }} des visiteurs</small></div>
        <div class="cx-tile"><b>{{ $t['votes'] }}</b><span>ont cliqué vers le vote</span><small>{{ $pct($t['votes'] - $t['direct_votes'], $t['visitors']) }} des visiteurs · {{ $t['direct_votes'] }} par le lien direct</small></div>
        <div class="cx-tile"><b>{{ $brevoContacts ?? '—' }}</b><span>contacts dans la liste Brevo n° {{ $brevoList }}</span></div>
    </div>

    <x-filament::section>
        <x-slot name="heading">Par source</x-slot>
        <x-slot name="description">Une personne = un navigateur (téléphone, ordinateur), compté une fois même s'il revient. La source est la balise utm_source du premier lien utilisé. Robots et aperçus de liens (WhatsApp, Facebook) exclus.</x-slot>
        <div class="cx-wrap">
            <table class="cx-table">
                <thead><tr><th>Source (utm_source)</th><th class="cx-num">Visiteurs</th><th class="cx-num">Coordonnées</th><th class="cx-num">Taux</th><th class="cx-num">Clics vers le vote</th><th class="cx-num">dont lien direct</th></tr></thead>
                <tbody>
                    @forelse ($funnel['sources'] as $row)
                        <tr>
                            <td>{{ $sourceLabel($row['source']) }}</td>
                            <td class="cx-num">{{ $row['visitors'] }}</td>
                            <td class="cx-num">{{ $row['signups'] }}</td>
                            <td class="cx-num">{{ $pct($row['signups'], $row['visitors']) }}</td>
                            <td class="cx-num">{{ $row['votes'] }}</td>
                            <td class="cx-num">{{ $row['direct_votes'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="cx-muted">Personne n'est encore venu sur la page.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

</x-filament-panels::page>
