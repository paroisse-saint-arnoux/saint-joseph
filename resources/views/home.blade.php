@use('App\Support\Images')
@use('App\Support\Markdown')
@use('App\Support\Text')
@php
    $hero = $home['hero'] ?? [];
    $decouvrir = $home['decouvrir'] ?? [];
    $projet = $home['projet'] ?? [];
    $avancement = $home['avancement'] ?? [];
    $collecte = $home['collecte'] ?? [];
    $statusLabels = ['done' => 'Réalisé', 'now' => 'En cours', 'todo' => 'À venir'];
@endphp

@extends('layouts.app', ['bodyClass' => 'page-home'])

@push('head')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Church',
            'name' => 'Église Saint Joseph',
            'alternateName' => 'Projet SJO2 — Paroisse Saint-Arnoux',
            'url' => route('home'),
            'image' => asset('images/og-default.jpg'),
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Le Rouret', 'postalCode' => '06650', 'addressRegion' => 'Alpes-Maritimes', 'addressCountry' => 'FR'],
            'parentOrganization' => ['@type' => 'Organization', 'name' => 'Association Diocésaine de Nice', 'url' => 'https://nice.catholique.fr/'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    {{-- HERO --}}
    <section class="hero" id="accueil" @if (filled($hero['image'] ?? null)) style="background-image: linear-gradient(rgba(0,18,46,.5), rgba(0,18,46,.82)), url('{{ Images::url($hero['image']) }}')" @endif>
        <div class="glow"></div>
        <div class="wrap hero-inner">
            <h1 class="stag">{{ Text::inline($hero['title'] ?? '') }}</h1>
            @if (! empty($hero['facts']))
                <div class="meta stag">
                    @foreach ($hero['facts'] as $fact)
                        <span>{{ Text::inline(is_array($fact) ? ($fact['text'] ?? '') : $fact) }}</span>
                    @endforeach
                </div>
            @endif
            <div class="hero-cta stag">
                <a href="#collecte" class="cta-primary">Soutenir le projet</a>
                <a href="#decouvrir" class="cta-ghost">Découvrir le projet</a>
            </div>
        </div>
        <a href="#decouvrir" class="scroll-hint"><span>Défiler</span><span class="line"></span></a>
    </section>

    {{-- DÉCOUVRIR --}}
    <section class="decouvrir section" id="decouvrir">
        <div class="wrap">
            <div @class(['decouvrir-grid', 'no-image' => blank($decouvrir['image'] ?? null)])>
                <div class="reveal">
                    <span class="eyebrow">{{ $decouvrir['eyebrow'] ?? 'Découvrir' }}</span>
                    <h2>{{ $decouvrir['title'] ?? '' }}</h2>
                    <div class="text">{!! Markdown::render($decouvrir['text'] ?? '') !!}</div>
                    <a href="{{ route('articles.index') }}" class="cta-navy">En savoir plus &rarr;</a>
                </div>
                @if (filled($decouvrir['image'] ?? null))
                    <img class="decouvrir-img reveal" src="{{ Images::url($decouvrir['image']) }}" alt="{{ $decouvrir['image_alt'] ?? '' }}" loading="lazy">
                @endif
            </div>
        </div>
    </section>

    {{-- LE PROJET --}}
    <section class="projet section" id="projet">
        <div class="wrap">
            <div class="section-head reveal">
                <span class="eyebrow">{{ $projet['eyebrow'] ?? 'Le projet' }}</span>
                <h2>{{ $projet['title'] ?? '' }}</h2>
                <p>{{ $projet['intro'] ?? '' }}</p>
            </div>

            @if (! empty($projet['stats']))
                <div class="stats reveal">
                    @foreach ($projet['stats'] as $stat)
                        <div class="stat">
                            @if (is_numeric($stat['value'] ?? null))
                                <div class="num" data-count="{{ $stat['value'] }}" data-unit="{{ $stat['unit'] ?? '' }}">{{ number_format((float) $stat['value'], 0, ',', ' ') }} <small>{{ $stat['unit'] ?? '' }}</small></div>
                            @else
                                <div class="num">{{ $stat['value'] ?? '' }} <small>{{ $stat['unit'] ?? '' }}</small></div>
                            @endif
                            <div class="lbl">{{ $stat['label'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (! empty($projet['buildings']))
                <div class="projet-grid">
                    @foreach ($projet['buildings'] as $building)
                        <div class="phase-card reveal">
                            <h3>{{ $building['title'] ?? '' }}</h3>
                            @if (filled($building['badge'] ?? null))<span class="erp">{{ $building['badge'] }}</span>@endif
                            <ul>
                                @foreach (Text::lines($building['items'] ?? '') as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (! empty($projet['images']))
                <div class="render-row reveal" style="--cols: {{ min(count($projet['images']), 3) }}">
                    @foreach ($projet['images'] as $img)
                        <figure class="shotfig">
                            <img src="{{ Images::url($img['path'] ?? null) }}" alt="{{ $img['alt'] ?? '' }}" loading="lazy">
                            @if (filled($img['caption'] ?? null))<figcaption>{{ $img['caption'] }}</figcaption>@endif
                        </figure>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- AVANCEMENT --}}
    <section class="dynamique section" id="avancement">
        <div class="wrap">
            <div class="section-head reveal">
                <span class="eyebrow">{{ $avancement['eyebrow'] ?? "L'avancement" }}</span>
                <h2>{{ $avancement['title'] ?? '' }}</h2>
                <p>{{ $avancement['text'] ?? '' }}</p>
            </div>
            <div class="tl reveal">
                @foreach ($avancement['steps'] ?? [] as $step)
                    @php $status = $step['status'] ?? 'todo'; @endphp
                    <div class="tl-item {{ $status }}">
                        <div class="yr">{{ $step['date'] ?? '' }}@if ($status !== 'done')<small>{{ $statusLabels[$status] ?? '' }}</small>@endif</div>
                        <div class="tl-rail"><div class="tl-dot"></div></div>
                        <div class="tl-body">
                            <h4>{{ $step['title'] ?? '' }}</h4>
                            @if ($status !== 'done')<div class="st">{{ $statusLabels[$status] ?? '' }}</div>@endif
                            <p>{{ $step['description'] ?? '' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- COLLECTE --}}
    @include('partials.clocher', ['collecte' => $collecte])

    {{-- DERNIERS ARTICLES --}}
    @if ($latest->isNotEmpty())
        <section class="latest section" id="actualites">
            <div class="wrap">
                <div class="section-head reveal">
                    <span class="eyebrow">Le projet au fil des jours</span>
                    <h2>Les dernières nouvelles.</h2>
                </div>
                <div class="cards-grid reveal">
                    @include('articles.partials.cards', ['articles' => $latest])
                </div>
                <div class="latest-foot"><a href="{{ route('articles.index') }}" class="cta-navy">Tous les articles &rarr;</a></div>
            </div>
        </section>
    @endif
@endsection
