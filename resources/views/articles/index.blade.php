@extends('layouts.app', [
    'title' => $category ? $category->getLabel().' — Le projet' : 'Le projet',
    'description' => $category
        ? 'Les articles « '.$category->getLabel().' » du projet de centre paroissial et d\'église Saint Joseph au Rouret.'
        : 'Toutes les nouvelles du projet de centre paroissial et d\'église Saint Joseph au Rouret : architecture, budget, construction, équipe.',
    'canonical' => $category ? route('articles.category', $category) : route('articles.index'),
])

@section('content')
    <section class="page-hero">
        <div class="wrap">
            <span class="eyebrow">Le projet</span>
            <h1>{{ $category ? $category->getLabel() : 'Le projet au fil des jours.' }}</h1>
            <p class="lead">Avancement, architecture, budget, construction et vie de l'équipe : suivez la naissance de l'église Saint Joseph.</p>
        </div>
    </section>

    <section class="articles-list">
        <div class="wrap">
            <nav class="cat-filter" aria-label="Filtrer par catégorie">
                <a href="{{ route('articles.index') }}" @class(['active' => ! $category])>Tous</a>
                @foreach ($categories as $cat)
                    <a href="{{ route('articles.category', $cat) }}" @class(['active' => $category === $cat])>{{ $cat->getLabel() }}</a>
                @endforeach
            </nav>

            @if ($articles->isEmpty())
                <p class="empty-state">Aucun article publié dans cette catégorie pour le moment.</p>
            @else
                <div class="cards-grid" id="articles-grid">
                    @include('articles.partials.cards')
                </div>
                <div class="loader" id="loader">Chargement…</div>
                @if ($articles->hasMorePages())
                    <div class="load-more" id="load-more"><a href="{{ $articles->nextPageUrl() }}" class="cta-navy">Voir plus d'articles</a></div>
                @endif
            @endif
        </div>
    </section>
@endsection
