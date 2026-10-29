@use('App\Enums\ArticleFormat')
@php
    $url = $article->url();
    $encoded = urlencode($url);
    $gallery = $article->galleryImages();
@endphp

@extends('layouts.app', [
    'title' => $article->title,
    'description' => $article->excerpt,
    'image' => $article->ogImageUrl(),
    'imageAlt' => $article->og_image_alt,
    'ogType' => 'article',
    'canonical' => $url,
    'noindex' => $isPreview,
])

@push('head')
    @if (! $isPreview)
        <meta property="article:published_time" content="{{ $article->published_at?->toAtomString() }}">
        <meta property="article:section" content="{{ $article->category->getLabel() }}">
        <script type="application/ld+json">
            {!! json_encode(array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $article->title,
                'description' => $article->excerpt,
                'image' => $article->ogImageUrl(),
                'datePublished' => $article->published_at?->toAtomString(),
                'dateModified' => $article->updated_at?->toAtomString(),
                'mainEntityOfPage' => $url,
                'publisher' => ['@type' => 'Organization', 'name' => 'Paroisse Saint-Arnoux', 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo-saint-joseph.png')]],
            ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif
@endpush

@section('before-header')
    @if ($isPreview)
        <div class="draft-banner">Aperçu d'un brouillon — cette page n'est pas publique. Partagez ce lien uniquement avec les personnes concernées.</div>
    @endif
@endsection

@section('content')
    <section @class(['page-hero', 'has-cover' => $article->ogImageUrl()])>
        <div @class(['article-wrap', 'wide' => $article->format === ArticleFormat::Html])>
            <span class="eyebrow"><a href="{{ route('articles.category', $article->category) }}">{{ $article->category->getLabel() }}</a></span>
            <h1>{{ $article->title }}</h1>
            @if ($article->published_at)
                <div class="meta">Publié le <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->translatedFormat('j F Y') }}</time></div>
            @endif
        </div>
    </section>

    <article class="article">
        <div @class(['article-wrap', 'wide' => $article->format === ArticleFormat::Html])>
            @if ($article->ogImageUrl())
                <figure class="article-cover"><img src="{{ $article->ogImageUrl() }}" alt="{{ $article->og_image_alt ?? '' }}"></figure>
            @endif

            @if (filled($article->excerpt))
                <p class="article-lead">{{ $article->excerpt }}</p>
            @endif

            <div class="prose">
                {!! $article->bodyHtml() !!}
            </div>

            @if ($gallery)
                <div class="gallery">
                    <h2>En images</h2>
                    <div id="gallery" class="carousel slide" data-bs-ride="false">
                        @if (count($gallery) > 1)
                            <div class="carousel-indicators">
                                @foreach ($gallery as $i => $img)
                                    <button type="button" data-bs-target="#gallery" data-bs-slide-to="{{ $i }}" @class(['active' => $i === 0]) @if ($i === 0) aria-current="true" @endif aria-label="Photo {{ $i + 1 }}"></button>
                                @endforeach
                            </div>
                        @endif
                        <div class="carousel-inner">
                            @foreach ($gallery as $i => $img)
                                <div @class(['carousel-item', 'active' => $i === 0])>
                                    <img src="{{ $img['url'] }}" alt="{{ $img['alt'] }}" loading="lazy">
                                    @if (filled($img['alt']))<div class="carousel-caption">{{ $img['alt'] }}</div>@endif
                                </div>
                            @endforeach
                        </div>
                        @if (count($gallery) > 1)
                            <button class="carousel-control-prev" type="button" data-bs-target="#gallery" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Précédente</span></button>
                            <button class="carousel-control-next" type="button" data-bs-target="#gallery" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Suivante</span></button>
                        @endif
                    </div>
                </div>
            @endif

            @unless ($isPreview)
                <div class="share">
                    <span>Partager</span>
                    <button type="button" data-share-native data-url="{{ $url }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 16a3 3 0 00-2.4 1.2l-6.7-3.4a3 3 0 000-1.6l6.7-3.4A3 3 0 1015 7l-6.7 3.4a3 3 0 100 3.2L15 17a3 3 0 103-1z"/></svg>Partager</button>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encoded }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12a10 10 0 10-11.5 9.9v-7H8v-2.9h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6v1.8H16l-.4 2.9h-2.1v7A10 10 0 0022 12z"/></svg>Facebook</a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $encoded }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 3a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h14zM8.3 18.3V9.7H5.6v8.6h2.7zM7 8.5a1.6 1.6 0 100-3.1 1.6 1.6 0 000 3.1zm11.4 9.8v-4.7c0-2.5-1.3-3.7-3.1-3.7-1.4 0-2 .8-2.4 1.3V9.7H10v8.6h2.7v-4.8c0-.3 0-.5.1-.7.2-.5.7-1 1.5-1 1 0 1.4.8 1.4 2v4.5h2.7z"/></svg>LinkedIn</a>
                    <a href="https://wa.me/?text={{ urlencode($article->title.' '.$url) }}" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm5.3 14.2c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-3.3-.8-2.8-1.1-4.5-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.6-.3.4c-.1.1-.3.3-.1.6.2.3.7 1.2 1.6 2 1.1 1 2 1.3 2.3 1.4.3.2.4.1.6-.1l.8-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.1.1.7-.1 1.3z"/></svg>WhatsApp</a>
                    <a href="mailto:?subject={{ rawurlencode($article->title) }}&body={{ rawurlencode($url) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>Email</a>
                    <button type="button" data-copy-link data-url="{{ $url }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.9 12a3.1 3.1 0 013.1-3.1h4V7H7a5 5 0 000 10h4v-1.9H7A3.1 3.1 0 013.9 12zM8 13h8v-2H8v2zm9-6h-4v1.9h4a3.1 3.1 0 010 6.2h-4V17h4a5 5 0 000-10z"/></svg><span>Copier le lien</span></button>
                </div>
            @endunless
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="related">
            <div class="wrap">
                <span class="eyebrow">{{ $article->category->getLabel() }}</span>
                <h2>À lire aussi</h2>
                <div class="cards-grid">
                    @include('articles.partials.cards', ['articles' => $related])
                </div>
            </div>
        </section>
    @endif
@endsection
