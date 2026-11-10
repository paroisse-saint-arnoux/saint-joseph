{{-- Opération « huile d'olive » : page ouverte par le QR code des étiquettes. Page autonome (sans le menu du site),
     pensée pour le téléphone. Publique mais non référencée : noindex + X-Robots-Tag, hors sitemap. --}}
@php
    $title = 'Merci pour votre huile d\'olive';
    $description = 'Cette huile d\'olive aide à bâtir l\'église Saint Joseph et son centre paroissial, au Rouret. Découvrez le projet et suivez-le.';
    $saved = session('huile_saved');
    $percent = $goal > 0 ? min(100, round($raised / $goal * 100)) : 0;
    $money = fn (int $v) => $v >= 1_000_000
        ? str_replace('.', ',', rtrim(rtrim(number_format($v / 1_000_000, 1, '.', ''), '0'), '.')).'&nbsp;M€'
        : number_format($v, 0, ',', '&nbsp;').'&nbsp;€';
    $img = fn (?string $path) => $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }} — Église Saint Joseph</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#00204E">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-saint-joseph.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-saint-joseph.png') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Église Saint Joseph — Le Rouret">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ route('huile') }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:image" content="{{ asset('images/og-saint-joseph.jpg') }}">
    @vite(['resources/scss/huile.scss', 'resources/js/huile.js'])
    @include('partials.matomo')
</head>
<body class="huile">

<header class="hero" @if ($heroImage) style="background-image: url('{{ $img($heroImage) }}')" @endif>
    <div class="in">
        <img class="logo" src="{{ asset('images/logo-saint-joseph-blanc.svg') }}" alt="Église Saint Joseph" width="88" height="88">
        <p class="kicker">Paroisse Saint-Arnoux · Le Rouret</p>
        <h1>Merci<em>&nbsp;!</em></h1>
        <p class="lead">En choisissant cette huile d'olive, vous aidez à bâtir <b>l'église Saint Joseph et son centre paroissial</b>, au Rouret.</p>
        <nav class="jump">
            <a class="btn btn-gold" href="#don">Faire un don</a>
            <a class="btn btn-ghost" href="#nouvelles">Suivre le projet</a>
        </nav>
    </div>
</header>

<main>
    <section class="block">
        <p class="eyebrow">Votre huile</p>
        <h2>Une huile d'ici, faite ensemble.</h2>
        <p>Les olives ont été récoltées le <b>samedi 7 novembre 2026</b>, sur les oliviers du terrain de la future église et chez des paroissiens. L'huile a ensuite été pressée spécialement pour nous au <a href="https://www.domaine-h.com/le-moulin" target="_blank" rel="noopener">Moulin d'H</a>.</p>
        <figure class="visual">
            <img src="{{ asset('images/huile-moulin-d-h.webp') }}" alt="Le Moulin d'H : la trémie de réception des olives et la machine de lavage devant le moulin en pierre" width="1200" height="517" loading="lazy">
            <figcaption>Le Moulin d'H, où notre huile a été pressée. Photo ©&nbsp;Domaine&nbsp;H</figcaption>
        </figure>
    </section>

    <section class="block">
        <p class="eyebrow">Le projet</p>
        <h2>Un lieu pour prier, se retrouver et accueillir.</h2>
        <p>La Paroisse Saint-Arnoux rassemble neuf communes autour du Rouret. Communauté vivante et en pleine croissance, elle construit sur un terrain acquis au Rouret un ensemble pensé pour durer&nbsp;:</p>
        <ul class="buildings">
            <li><b>Une église</b> de plus de 300 places, avec son clocher et son parvis&nbsp;;</li>
            <li><b>Un centre paroissial</b> ouvert à tous&nbsp;: grande salle, salles de catéchisme et d'aumônerie, café, espaces de rencontre&nbsp;;</li>
            <li><b>Un presbytère</b> pour les prêtres de la paroisse.</li>
        </ul>
        @if ($projectImage && ($src = $img($projectImage['path'] ?? null)))
            <figure class="visual">
                <img src="{{ $src }}" alt="{{ $projectImage['alt'] ?? '' }}" loading="lazy">
                @if (filled($projectImage['caption'] ?? null))<figcaption>{{ $projectImage['caption'] }}</figcaption>@endif
            </figure>
        @endif
        @if ($stats)
            <dl class="stats">
                @foreach ($stats as $stat)
                    <div><dt>{{ $stat['label'] ?? '' }}</dt><dd>{{ $stat['value'] ?? '' }}<small>{{ $stat['unit'] ?? '' }}</small></dd></div>
                @endforeach
            </dl>
        @endif
        <a class="more" href="{{ route('home') }}#decouvrir">Découvrir tout le projet →</a>
    </section>

    <section class="block don" id="don">
        <p class="eyebrow">Aller plus loin</p>
        <h2>Chaque pierre compte.</h2>
        @if ($goal > 0)
            <div class="gauge" role="img" aria-label="{{ $percent }} % de l'objectif atteint">
                <div class="bar"><span style="width: {{ $percent }}%"></span></div>
                <p><b>{!! $money($raised) !!}</b> déjà réunis sur {!! $money($goal) !!}</p>
            </div>
        @endif
        <p>Votre don est <b>déductible à 66&nbsp;%</b> de votre impôt sur le revenu&nbsp;: un don de 100&nbsp;€ ne vous coûte que 34&nbsp;€.</p>
        <a class="btn btn-gold btn-big" href="{{ $donUrl }}" data-don="huile" target="_blank" rel="noopener">Faire un don</a>
        <p class="small">Paiement sécurisé sur la plateforme de dons du diocèse de Nice. Un reçu fiscal vous est envoyé.</p>
    </section>

    <section class="block card" id="nouvelles">
        @if ($saved)
            <h2>C'est noté, merci<em>&nbsp;!</em></h2>
            <p>Vous recevrez des nouvelles du projet&nbsp;: avancement du chantier, temps forts et rendez-vous de la communauté.</p>
            <a class="btn btn-navy" href="https://wa.me/?text={{ rawurlencode('Le projet de l\'église Saint Joseph au Rouret : '.route('home')) }}" target="_blank" rel="noopener">Faire connaître le projet</a>
        @else
            <p class="eyebrow">Rester en lien</p>
            <h2>Recevez des nouvelles du projet.</h2>
            <p>Avancement du chantier, temps forts, rendez-vous de la communauté&nbsp;: quelques e-mails par an, pas plus.</p>
            <form action="{{ route('huile.store') }}#nouvelles" method="post" novalidate>
                @csrf
                @if ($errors->any())
                    <p class="error" role="alert">{{ $errors->first() }}</p>
                @endif
                <label for="email">Votre e-mail</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" inputmode="email" placeholder="vous@exemple.fr" maxlength="190" @if ($errors->has('email')) aria-invalid="true" @endif>
                <div class="names">
                    <div>
                        <label for="prenom">Prénom <span class="opt">(facultatif)</span></label>
                        <input type="text" id="prenom" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" maxlength="80">
                    </div>
                    <div>
                        <label for="nom">Nom <span class="opt">(facultatif)</span></label>
                        <input type="text" id="nom" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" maxlength="80">
                    </div>
                </div>
                <input type="hidden" name="_t" value="{{ \App\Support\SpamGuard::timestamp() }}">
                <div class="hp" aria-hidden="true">
                    <label for="hu-website">Ne pas remplir</label>
                    <input id="hu-website" type="text" name="website" tabindex="-1" autocomplete="off">
                </div>
                <label class="chk"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))> <span>J'accepte de recevoir par e-mail les nouvelles du projet Saint Joseph.</span></label>
                <button class="btn btn-navy btn-big" type="submit">M'inscrire</button>
            </form>
            <p class="legal">Vos données sont traitées par l'Association Diocésaine de Nice, paroisse Saint-Arnoux, uniquement pour vous informer du projet Saint Joseph. Elles ne sont pas transmises à des tiers. Désinscription à tout moment par le lien présent dans chaque message. <a href="{{ route('privacy') }}">Confidentialité</a></p>
        @endif
    </section>
</main>

<footer>
    <a href="{{ route('home') }}"><img src="{{ asset('images/logo-saint-joseph-blanc.svg') }}" alt="" width="40" height="40"> Centre paroissial et église Saint Joseph</a>
    <span>Paroisse Saint-Arnoux · Association Diocésaine de Nice</span>
    <span><a href="{{ route('contact') }}">Contact</a> · <a href="{{ route('legal') }}">Mentions légales</a></span>
</footer>
</body>
</html>
