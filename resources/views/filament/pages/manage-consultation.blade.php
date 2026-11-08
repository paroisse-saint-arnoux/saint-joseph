@use('App\Support\Consultation')
@php
    $sign = fn ($v) => $v === null ? '—' : ($v > 0 ? '+' : '').number_format($v, 1, ',', ' ');
    $colors = [-2 => '#A8432F', -1 => '#D4876A', 0 => '#8A8578', 1 => '#5B8BD0', 2 => '#2F5C9E'];
    $steps = Consultation::SCALES['overall']['steps'];
@endphp
<x-filament-panels::page>
    <style>
        .sc-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; }
        .sc-tile { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 14px 16px; }
        .sc-tile b { display: block; font-size: 1.9rem; line-height: 1.1; color: #00204E; font-variant-numeric: tabular-nums; }
        .sc-tile span { color: #6b7280; font-size: .9rem; }
        .sc-table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        .sc-table th { text-align: left; font-weight: 600; color: #6b7280; padding: 8px 10px; border-bottom: 1px solid #eee; white-space: nowrap; }
        .sc-table td { padding: 10px; border-bottom: 1px solid #f3f3f3; vertical-align: middle; }
        .sc-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .sc-wrap { overflow-x: auto; }
        .sc-bar { display: flex; gap: 2px; height: 18px; min-width: 180px; }
        .sc-bar span { display: block; height: 100%; border-radius: 3px; min-width: 0; }
        .sc-bar-empty { height: 18px; min-width: 180px; border-radius: 3px; background: #f3f4f6; }
        .sc-legend { display: flex; flex-wrap: wrap; gap: 6px 14px; font-size: .82rem; color: #4b5563; margin-bottom: 10px; }
        .sc-legend i { display: inline-block; width: 11px; height: 11px; border-radius: 3px; margin-right: 5px; vertical-align: -1px; }
        .sc-comment { padding: 12px 0; border-top: 1px solid #f1f1f1; }
        .sc-comment p { white-space: pre-line; margin: 4px 0 0; }
        .sc-muted { color: #6b7280; font-size: .85rem; }
        .sc-qr { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; }
        .sc-qr-item { text-align: center; border: 1px solid #eee; border-radius: 12px; padding: 12px; background: #fff; }
        .sc-qr-code svg { width: 140px; height: 140px; margin: 0 auto 8px; display: block; }
        .sc-qr-item a.sc-url { display: block; font-size: .72rem; color: #6b7280; word-break: break-all; margin: 4px 0 8px; }
        .sc-closed { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 10px; padding: 10px 14px; }
    </style>

    @unless ($open)
        <div class="sc-closed"><strong>La consultation est close.</strong> Les visiteurs voient « La consultation est terminée ». Les réponses ci-dessous sont conservées.</div>
    @endunless

    <div class="sc-tiles">
        <div class="sc-tile"><b>{{ $totals['participants'] }}</b><span>adresses email saisies</span></div>
        <div class="sc-tile"><b>{{ $totals['voters'] }}</b><span>personnes ont donné au moins un avis</span></div>
        <div class="sc-tile"><b>{{ $totals['complete'] }}</b><span>ont noté tous les projets</span></div>
        <div class="sc-tile"><b>{{ $totals['votes'] }}</b><span>avis au total</span></div>
        <div class="sc-tile"><b>{{ $totals['comments'] }}</b><span>commentaires</span></div>
    </div>

    <x-filament::section>
        <x-slot name="heading">Résultats par projet</x-slot>
        <x-slot name="description">Échelles de −2 à +2. « Favorables » : part des avis à +1 ou +2. Les critères ne sont demandés qu'après l'avis général.</x-slot>

        <div class="sc-legend" aria-label="Légende de la répartition">
            @foreach ($steps as $v => $label)
                <span><i style="background: {{ $colors[$v] }}"></i>{{ $v > 0 ? '+'.$v : $v }} {{ in_array($v, [-2, 0, 2]) ? '· '.$label : '' }}</span>
            @endforeach
        </div>

        <div class="sc-wrap">
            <table class="sc-table">
                <thead>
                    <tr>
                        <th>Projet</th>
                        <th class="sc-num">Avis</th>
                        <th>Répartition de l'avis général</th>
                        <th class="sc-num">Moyenne</th>
                        <th class="sc-num">Favorables</th>
                        <th class="sc-num">Esthétique</th>
                        <th class="sc-num">Spirituel</th>
                        <th class="sc-num">Convivial</th>
                        <th class="sc-num">Comment.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td><strong>{{ $row['project']->position }}. {{ $row['project']->name }}</strong><div class="sc-muted">{{ $row['project']->architect }}</div></td>
                            <td class="sc-num">{{ $row['count'] }}</td>
                            <td>
                                @if ($row['count'])
                                    <div class="sc-bar" role="img" aria-label="{{ collect($row['distribution'])->map(fn ($n, $v) => ($v > 0 ? '+'.$v : $v).' : '.$n)->join(', ') }}">
                                        @foreach ($row['distribution'] as $v => $n)
                                            @if ($n)
                                                <span style="flex: {{ $n }}; background: {{ $colors[$v] }}" title="{{ $v > 0 ? '+'.$v : $v }} ({{ $steps[$v] }}) : {{ $n }} avis, {{ round(100 * $n / $row['count']) }} %"></span>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <div class="sc-bar-empty"></div>
                                @endif
                            </td>
                            <td class="sc-num"><strong>{{ $sign($row['average']) }}</strong></td>
                            <td class="sc-num">{{ $row['favorable'] === null ? '—' : $row['favorable'].' %' }}</td>
                            @foreach (['aesthetic', 'spiritual', 'convivial'] as $c)
                                <td class="sc-num" title="{{ $row['criteria'][$c]['count'] }} réponse(s)">{{ $sign($row['criteria'][$c]['average']) }}</td>
                            @endforeach
                            <td class="sc-num">{{ $row['comments'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Commentaires</x-slot>
        <x-slot name="afterHeader">
            <select wire:model.live="commentProject" class="fi-select-input" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 4px 28px 4px 10px; font-size: .9rem;">
                <option value="">Tous les projets</option>
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}">{{ $p->position }}. {{ $p->name }}</option>
                @endforeach
            </select>
        </x-slot>

        @forelse ($comments as $vote)
            <div class="sc-comment">
                <div class="sc-muted">
                    <strong>{{ $vote->project->name }}</strong> · {{ $vote->participant->email }}
                    @if ($vote->overall !== null) · avis {{ $vote->overall > 0 ? '+'.$vote->overall : $vote->overall }} @endif
                    · {{ $vote->updated_at->translatedFormat('j F à H:i') }}
                </div>
                <p>{{ $vote->comment }}</p>
            </div>
        @empty
            <p class="sc-muted">Aucun commentaire pour l'instant.</p>
        @endforelse
        @if ($comments->count() === 100)
            <p class="sc-muted">Seuls les 100 derniers sont affichés : l'export Excel les contient tous.</p>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">QR codes à imprimer</x-slot>
        <x-slot name="description">Le QR code général ouvre la consultation sur le premier projet sans avis. Celui d'un projet, posé à côté de sa maquette, ouvre directement sa page (après l'email, la première fois).</x-slot>

        <div class="sc-qr">
            <div class="sc-qr-item">
                <div class="sc-qr-code">{!! $qr($startUrl) !!}</div>
                <strong>Consultation</strong>
                <a class="sc-url" href="{{ $startUrl }}" target="_blank">{{ $startUrl }}</a>
                <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="downloadQr">PNG</x-filament::button>
            </div>
            @foreach ($projects as $p)
                @php $url = route('consultation.project', $p); @endphp
                <div class="sc-qr-item">
                    <div class="sc-qr-code">{!! $qr($url) !!}</div>
                    <strong>{{ $p->position }}. {{ $p->name }}</strong>
                    <a class="sc-url" href="{{ $url }}" target="_blank">{{ $url }}</a>
                    <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-down-tray" wire:click="downloadQr({{ $p->id }})">PNG</x-filament::button>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
