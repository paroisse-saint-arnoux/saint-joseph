@php
    $siteName = config('app.name');
    $pageTitle = isset($title) ? $title.' · '.$siteName : $siteName.' — Paroisse Saint-Arnoux · Le Rouret';
    $pageDescription = Str::limit(strip_tags($description ?? ($site['description'] ?? '')), 300);
    $pageImage = $image ?? asset('images/og-default.jpg');
    $canonical = $canonical ?? url()->current();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="theme-color" content="#00204E">
    @if (! empty($noindex))
        <meta name="robots" content="noindex, nofollow">
    @else
        <link rel="canonical" href="{{ $canonical }}">
    @endif
    <link rel="icon" type="image/png" href="{{ asset('images/logo-saint-joseph.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-saint-joseph.png') }}">

    {{-- Open Graph (Facebook, WhatsApp, LinkedIn) --}}
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:site_name" content="{{ $siteName }} — Le Rouret">
    <meta property="og:title" content="{{ $title ?? $siteName }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:image" content="{{ $pageImage }}">
    @isset($imageAlt)<meta property="og:image:alt" content="{{ $imageAlt }}">@endisset
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? $siteName }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">
    @stack('head')

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @include('partials.matomo')
</head>
<body class="{{ $bodyClass ?? 'page-inner' }}">
    <a href="#main" class="visually-hidden-focusable">Aller au contenu</a>
    @yield('before-header')
    @include('partials.header')

    <main id="main">
        @yield('content')
    </main>

    @include('partials.engage')
    @include('partials.footer')
    @stack('scripts')
</body>
</html>
