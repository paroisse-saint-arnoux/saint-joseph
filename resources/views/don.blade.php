{{-- Page /don (essai) : formulaire de don RGIVE du diocèse intégré dans la page (« Embed » : embed.js remplace
     la div data-rgive-src par une iframe ; nos domaines, dev compris, sont autorisés côté RGIVE). --}}
@php
    $donUrl = $site['don_url'] ?? 'https://don-nice.catholique.fr/centre-paroissial-st-arnoux-rouret/';
    $raised = (float) ($collecte['raised'] ?? 0);
    $goal = max((float) ($collecte['goal'] ?? 6000000), 1);
    $pct = min(100, round($raised / $goal * 100, 1));
    $fmt = fn ($n) => number_format($n, 0, ',', ' ');
@endphp
@extends('layouts.app', [
    'title' => 'Faire un don',
    'description' => 'Soutenez la construction du centre paroissial et de l\'église Saint Joseph au Rouret : faites un don en ligne.',
    'noindex' => true,
])

@push('head')
    <script>(function(){const sc = document.createElement("script");sc.setAttribute("src", "https://don-nice.catholique.fr/assets/embed.js");document.head.appendChild(sc);})()</script>
@endpush

@section('content')
    <section class="page-hero">
        <div class="wrap">
            <span class="eyebrow">Faire un don</span>
            <h1>Bâtissons ensemble Saint Joseph.</h1>
            <p class="lead">Chaque don, petit ou grand, fait monter le clocher. Votre don est reçu par le diocèse de Nice, qui vous adresse un reçu fiscal.</p>
        </div>
    </section>

    <section class="don-page">
        <div class="wrap don-grid">
            <aside class="don-aside">
                <span class="eyebrow">La collecte</span>
                <div class="don-gauge">
                    <div class="don-gauge-line"><b>{{ $fmt($raised) }} €</b><span>{{ str_replace('.', ',', $pct) }} %</span></div>
                    <div class="don-gauge-bar"><i style="width: {{ $pct }}%"></i></div>
                    <div class="don-gauge-sub">Objectif : {{ $fmt($goal) }} €</div>
                </div>
                <p>1 pierre = 10 €. Chaque don de 10 € pose une pierre du clocher.</p>
            </aside>

            <div class="don-card-embed">
                {{-- ?type=regular : onglet « Don mensuel » sélectionné d'office (paramètre d'URL accepté par RGIVE) --}}
                <div data-rgive-src="{{ $donUrl }}?type=regular"></div>
            </div>
        </div>
    </section>
@endsection
