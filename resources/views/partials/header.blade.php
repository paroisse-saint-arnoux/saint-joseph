@php $donUrl = $site['don_url'] ?? '/don'; @endphp
<header class="site-header" id="header">
    <div class="wrap nav-bar">
        <a href="{{ route('home') }}" class="brand">
            <img src="{{ asset('images/logo-saint-joseph-blanc.svg') }}" alt="Église Saint Joseph — Bâtisseurs du Seigneur" width="80" height="80">
            <span class="txt"><b>Saint Joseph</b><span>Paroisse Saint-Arnoux · Le Rouret</span></span>
        </a>
        <nav class="menu" id="menu" aria-label="Menu principal">
            <a href="{{ route('home') }}#decouvrir">Découvrir</a>
            <a href="{{ route('home') }}#collecte">Soutenir</a>
            <a href="{{ route('articles.index') }}" @class(['active' => request()->routeIs('articles.*')])>Le projet</a>
            <a href="{{ $donUrl }}" class="btn-don" data-don="en-tete">Faire un don</a>
        </nav>
        <button class="burger" id="burger" aria-label="Ouvrir le menu" aria-controls="menu" aria-expanded="false"><span></span><span></span><span></span></button>
    </div>
</header>
