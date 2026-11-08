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
            Le {{ $opensAt->translatedFormat('l j F à H\hi') }}, elle passera d'elle-même en mode vote (bouton « Je vote maintenant », formulaire qui renvoie vers le site de vote, bandeau sur l'accueil).
        @elseif ($state === 'open')
            <strong>Le vote est ouvert</strong> jusqu'au {{ $closesAt->translatedFormat('l j F à H\hi') }} : bouton « Je vote maintenant », le formulaire renvoie vers le site de vote{{ Croix::showHomeBanner() ? ', bandeau sur l\'accueil' : '' }}.
        @else
            <strong>Le vote est clos</strong> depuis le {{ $closesAt->translatedFormat('j F à H\hi') }}. La page propose de laisser ses coordonnées pour recevoir le résultat.
        @endif
    </div>

    <div class="cx-tiles">
        <div class="cx-tile"><b>{{ $t['visitors'] }}</b><span>personnes ont ouvert la page</span><small>{{ $t['pageviews'] }} affichages au total</small></div>
        <div class="cx-tile"><b>{{ $t['signups'] }}</b><span>ont laissé leurs coordonnées</span><small>{{ $pct($t['signups'], $t['visitors']) }} des visiteurs</small></div>
        <div class="cx-tile"><b>{{ $t['votes'] }}</b><span>ont cliqué vers le vote</span><small>{{ $pct($t['votes'] - $t['direct_votes'], $t['visitors']) }} des visiteurs · {{ $t['direct_votes'] }} par le lien direct</small></div>
        <div class="cx-tile"><b>{{ $t['contacts'] }}</b><span>contacts dans la liste Brevo</span><small>{{ $t['unsynced'] ? $t['unsynced'].' pas encore envoyé(s) à Brevo' : 'tous envoyés à Brevo' }}</small></div>
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

    <x-filament::section>
        <x-slot name="heading">Dernières inscriptions</x-slot>
        <div class="cx-wrap">
            <table class="cx-table">
                <thead><tr><th>Nom</th><th>E-mail</th><th>Mobile</th><th>Source</th><th>Date</th><th>Brevo</th></tr></thead>
                <tbody>
                    @forelse ($signups as $s)
                        <tr>
                            <td>{{ $s->fullName() ?: '—' }}</td>
                            <td>{{ $s->email }}</td>
                            <td>{{ $s->phone ?? '—' }}</td>
                            <td>{{ $sourceLabel($s->source) }}</td>
                            <td class="cx-muted" style="white-space: nowrap">{{ $s->created_at->translatedFormat('j M, H:i') }}</td>
                            <td>{{ $s->brevo_synced_at ? '✓' : 'en attente' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="cx-muted">Aucune inscription pour l'instant.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($signups->count() === 100)
            <p class="cx-muted">Seules les 100 dernières sont affichées : la liste complète est dans Brevo (liste {{ config('services.brevo.croix_list_id') }}).</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
