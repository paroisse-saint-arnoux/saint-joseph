{{-- Page /don (essai) : formulaire de don RGIVE du diocèse intégré dans le site.
     Deux intégrations proposées par RGIVE : « Embed » (formulaire dans la page, le site doit être autorisé côté RGIVE)
     et « Capsule » (fenêtre par-dessus la page, ouverte par un lien .rgive-button ou par ?capsule=…). --}}
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
    <script>(function(){const sc = document.createElement("script");sc.setAttribute("src", "https://don-nice.catholique.fr/assets/overlay.js");document.head.appendChild(sc);})()</script>
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

                <div class="don-alt">
                    <p>Le formulaire ne s'affiche pas&nbsp;?</p>
                    <a href="{{ $donUrl }}" target="_blank" rel="noopener" class="rgive-button cta-navy" data-don="page-don-capsule">Ouvrir le formulaire de don</a>
                    <a href="{{ $donUrl }}" target="_blank" rel="noopener" class="don-direct" data-don="page-don-lien">ou donner sur le site du diocèse &rarr;</a>
                </div>
            </aside>

            <div class="don-card-embed">
                <div data-rgive-src="{{ $donUrl }}"></div>
            </div>
        </div>
    </section>
@endsection
