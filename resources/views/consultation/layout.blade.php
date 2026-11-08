{{-- Application mobile de la consultation : page légère, grands caractères, sans le menu du site. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ isset($title) ? $title.' · ' : '' }}Consultation · Église Saint Joseph</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#00204E">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-saint-joseph.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-saint-joseph.png') }}">
    @vite(['resources/scss/consultation.scss', 'resources/js/consultation.js'])
    @include('partials.matomo')
</head>
<body class="cs">
    <header class="cs-header">
        <img src="{{ asset('images/logo-saint-joseph-blanc.svg') }}" alt="" width="44" height="44">
        <div>
            <b>Église Saint Joseph</b>
            <span>Votre avis sur les projets</span>
        </div>
    </header>

    @yield('nav')

    <main class="cs-main">
        @yield('content')
    </main>

    <footer class="cs-footer">
        <a href="{{ route('home') }}">Le site du projet</a> · <a href="{{ route('privacy') }}">Confidentialité</a>
    </footer>
</body>
</html>
