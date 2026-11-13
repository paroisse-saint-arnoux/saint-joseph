{{-- Page /don : formulaire de don RGIVE du diocèse intégré dans la page (« Embed » : embed.js remplace
     la div data-rgive-src par une iframe ; nos domaines, dev compris, sont autorisés côté RGIVE).
     Textes : Admin > Contenu > Page de don (réglage `don`, DonController). --}}
@php
    $donUrl = $site['don_url'] ?? 'https://don-nice.catholique.fr/centre-paroissial-st-arnoux-rouret/';
    $goal = max($goal, 1);
    $pct = min(100, round($raised / $goal * 100, 1));
    $fmt = fn ($n) => number_format($n, 0, ',', ' ');
    $hero = $don['hero'] ?? [];
    $fiscal = $don['fiscal'] ?? [];
    $testimony = $don['testimony'] ?? [];
    $faq = array_values(array_filter($don['faq'] ?? [], fn ($q) => filled($q['question'] ?? null)));
@endphp
@extends('layouts.app', [
    'title' => 'Faire un don',
    'description' => $hero['lead'] ?? 'Soutenez la construction du centre paroissial et de l\'église Saint Joseph au Rouret : faites un don en ligne.',
    'noindex' => true,
    'engageWithoutDon' => true, // pas de second appel au don dans le bandeau du bas
])

@push('head')
    <script>(function(){const sc = document.createElement("script");sc.setAttribute("src", "https://don-nice.catholique.fr/assets/embed.js");document.head.appendChild(sc);})()</script>
@endpush

@section('content')
    <section class="page-hero">
        <div class="wrap">
            <span class="eyebrow">{{ $hero['eyebrow'] ?? 'Faire un don' }}</span>
            <h1>{{ $hero['title'] ?? 'Bâtissons ensemble Saint Joseph.' }}</h1>
            @if (filled($hero['lead'] ?? null))
                <p class="lead">{{ $hero['lead'] }}</p>
            @endif
        </div>
    </section>

    <section class="don-page" id="formulaire">
        <div class="wrap don-grid">
            <aside class="don-aside">
                <span class="eyebrow">La collecte</span>
                <div class="don-gauge">
                    <div class="don-gauge-line"><b>{{ $fmt($raised) }} €</b><span>{{ str_replace('.', ',', $pct) }} %</span></div>
                    <div class="don-gauge-bar"><i style="width: {{ $pct }}%"></i></div>
                    <div class="don-gauge-sub">Objectif : {{ $fmt($goal) }} €</div>
                </div>
                @if (filled($don['gauge_text'] ?? null))
                    <p>{{ $don['gauge_text'] }}</p>
                @endif
            </aside>

            <div class="don-card-embed">
                {{-- ?type=regular : onglet « Don mensuel » sélectionné d'office (paramètre d'URL accepté par RGIVE) --}}
                <div data-rgive-src="{{ $donUrl }}?type=regular"></div>
            </div>
        </div>
    </section>

    {{-- Pourquoi ce projet / le projet --}}
    @if ($html['why'] || $html['projet'])
        <section class="don-section">
            <div class="wrap don-columns">
                @foreach (['why', 'projet'] as $block)
                    @if ($html[$block])
                        <div class="don-block">
                            @if (filled($don[$block]['eyebrow'] ?? null))<span class="eyebrow">{{ $don[$block]['eyebrow'] }}</span>@endif
                            @if (filled($don[$block]['title'] ?? null))<h2>{{ $don[$block]['title'] }}</h2>@endif
                            <div class="prose">{!! $html[$block] !!}</div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    @if (filled($testimony['quote'] ?? null))
        <section class="don-quote">
            <div class="wrap">
                <figure>
                    <blockquote>« {{ $testimony['quote'] }} »</blockquote>
                    @if (filled($testimony['author'] ?? null))<figcaption>{{ $testimony['author'] }}</figcaption>@endif
                </figure>
            </div>
        </section>
    @endif

    {{-- Déduction fiscale : particuliers / entreprises --}}
    @if ($html['individuals'] || $html['companies'])
        <section class="don-section">
            <div class="wrap">
                @if (filled($fiscal['eyebrow'] ?? null))<span class="eyebrow">{{ $fiscal['eyebrow'] }}</span>@endif
                @if (filled($fiscal['title'] ?? null))<h2 class="don-section-title">{{ $fiscal['title'] }}</h2>@endif
                <div class="don-fiscal">
                    @if ($html['individuals'])
                        <div class="don-fiscal-card"><h3>Particuliers</h3><div class="prose">{!! $html['individuals'] !!}</div></div>
                    @endif
                    @if ($html['companies'])
                        <div class="don-fiscal-card"><h3>Entreprises</h3><div class="prose">{!! $html['companies'] !!}</div></div>
                    @endif
                </div>
                @if (filled($fiscal['note'] ?? null))<p class="don-note">{{ $fiscal['note'] }}</p>@endif
            </div>
        </section>
    @endif

    @if ($faq)
        <section class="don-section don-section--faq">
            <div class="wrap">
                <h2 class="don-section-title">Foire aux questions</h2>
                <div class="don-faq">
                    @foreach ($faq as $item)
                        <details>
                            <summary>{{ $item['question'] }}</summary>
                            <p>{{ $item['answer'] ?? '' }}</p>
                        </details>
                    @endforeach
                </div>
                <p class="don-faq-more"><a class="cta-navy" href="#formulaire">Faire un don &uarr;</a></p>
            </div>
        </section>
    @endif
@endsection
