{{-- Concours S.O.S Calvaires « Que va devenir la Croix du Pape ? » — page autonome, sans le menu du site.
     $state : before (formulaire seul) · open (bouton « Je vote maintenant », formulaire → site de vote) · closed. --}}
@php
    $title = 'La Croix du Pape au Rouret ? Votez';
    $description = 'Dressée devant 80 000 jeunes au Stade de France, la Croix du Pape cherche son lieu. Votez pour qu\'elle soit plantée sur le terrain de la future église Saint Joseph, au Rouret.';
    $ogDescription = 'Ici, on construit une église. Un seul lieu en France planterait la Croix avant les murs : le nôtre. Votez du 9 au 30 novembre.';
    $saved = session('croix_saved');
    $error = fn (string $field) => $errors->first($field);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Église Saint Joseph</title>
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#00204E">
    <link rel="canonical" href="{{ route('croix') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-saint-joseph.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-saint-joseph.png') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Église Saint Joseph — Le Rouret">
    <meta property="og:title" content="La Croix du Pape au Rouret ? Votez.">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ route('croix') }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:image" content="{{ asset('images/og-croix-du-pape.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="La Croix du Pape au Rouret ? Votez du 9 au 30 novembre — le terrain de la future église Saint Joseph">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="La Croix du Pape au Rouret ? Votez.">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ asset('images/og-croix-du-pape.jpg') }}">
    @vite(['resources/scss/croix.scss', 'resources/js/croix.js'])
    @include('partials.matomo')
</head>
<body class="croix">

<section class="hero" style="background-image: url('{{ asset('images/croix-terrain.webp') }}')">
    <a class="kicker" href="{{ route('home') }}">Paroisse Saint-Arnoux · Le Rouret<span> · Appel à projets S.O.S Calvaires</span></a>
    <div class="in"><div class="sign">Ici, on construit une église !</div></div>
</section>

<main>
    <div class="grid">
        <div>
            <h1>La Croix du Pape<br>au Rouret<em> ?</em></h1>
            <div class="rule"></div>
            <div class="lead">
                <p>Le 25 septembre 2026, 80 000 jeunes ont prié devant elle au Stade de France, avec le pape Léon XIV. Aujourd'hui, cette Croix cherche le lieu où elle sera dressée pour les générations qui viennent. Le choix se fait par vote parmi 30 candidats en France.</p>
                <p><b>Un seul projet la planterait avant de construire les murs de son église&nbsp;: le nôtre&nbsp;!</b></p>
                <p>Sur le terrain où nous bâtissons l'église Saint Joseph, elle serait le premier signe visible du chantier et le lieu de prière de la communauté pendant toute la construction, avant de prendre sa place définitive sur le parvis.</p>
                <p>Votez maintenant et partagez massivement cette page afin que notre candidature soit retenue&nbsp;!</p>
            </div>
            <div class="dates">
                <div><b>9 nov.</b>ouverture du vote</div>
                <div><b>30 nov.</b>clôture, 23h59</div>
                <div><b>3 déc.</b>résultat, 18h</div>
            </div>
            <div class="steps">
                @if ($state === 'before')
                    <div><span>1</span><p>Laissez votre e-mail&nbsp;: vous recevrez le lien de vote dès l'ouverture, puis le résultat.</p></div>
                @else
                    <div><span>1</span><p>Laissez votre e-mail&nbsp;: vous recevrez le résultat, puis les nouvelles du projet.</p></div>
                @endif
                <div><span>2</span><p>Votez sur le site de S.O.S Calvaires. Un vote par personne, une minute.</p></div>
                <div><span>3</span><p>Faites voter trois personnes autour de vous. C'est ainsi que l'on gagne.</p></div>
            </div>
        </div>

        <aside class="card" id="formulaire">
            @if ($saved)
                <h2>Merci<em>.</em></h2>
                @if ($state === 'before')
                    <p class="why">C'est noté. Nous vous enverrons le lien de vote lundi 9 novembre, à l'ouverture, puis le résultat le 3 décembre.</p>
                @else
                    <p class="why">C'est noté. Nous vous enverrons le résultat le 3 décembre, puis les nouvelles du projet Saint Joseph.</p>
                @endif
                <p class="why">Pour que Le Rouret l'emporte, faites passer cette page à trois personnes autour de vous&nbsp;:</p>
                <a class="btn" href="https://wa.me/?text={{ rawurlencode('La Croix du Pape au Rouret ? Votez : '.route('croix', ['utm_source' => 'whatsapp', 'utm_medium' => 'partage', 'utm_campaign' => 'croix'])) }}" target="_blank" rel="noopener">Partager sur WhatsApp</a>
                <a class="skip" href="mailto:?subject={{ rawurlencode('La Croix du Pape au Rouret ?') }}&body={{ rawurlencode('Votez pour que la Croix du Pape soit plantée sur le terrain de la future église Saint Joseph : '.route('croix', ['utm_source' => 'email', 'utm_medium' => 'partage', 'utm_campaign' => 'croix'])) }}">Envoyer par e-mail</a>
            @else
                @if ($state === 'before')
                    <h2>Recevez le lien de vote<em>.</em></h2>
                    <p class="why">Le vote ouvre lundi 9 novembre. Laissez votre adresse&nbsp;: nous vous envoyons le lien à l'ouverture, puis le résultat le 3 décembre.</p>
                @elseif ($state === 'open')
                    <h2>Votez pour Le Rouret<em>.</em></h2>
                    <p class="why">Le vote est ouvert jusqu'au 30 novembre. Laissez votre adresse&nbsp;: vous arrivez ensuite sur le site de vote, et nous vous enverrons le résultat le 3 décembre.</p>
                @else
                    <h2>Le vote est clos<em>.</em></h2>
                    <p class="why">Merci à tous ceux qui ont voté ! Le résultat sera annoncé le 3 décembre à 18 h. Laissez votre adresse pour le recevoir.</p>
                @endif

                <form action="{{ route('croix.store') }}" method="post" novalidate>
                    @csrf
                    @if ($errors->any())
                        <p class="error" role="alert">{{ $errors->first() }}</p>
                    @endif
                    <div class="names">
                        <div>
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" maxlength="80">
                        </div>
                        <div>
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" maxlength="80">
                        </div>
                    </div>
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="vous@exemple.fr" maxlength="190" @if ($error('email')) aria-invalid="true" @endif>
                    <label for="sms">Mobile <span class="opt">(facultatif, pour recevoir le lien par SMS)</span></label>
                    <input type="tel" id="sms" name="phone" value="{{ old('phone') }}" autocomplete="tel" placeholder="06 00 00 00 00" maxlength="30" @if ($error('phone')) aria-invalid="true" @endif>
                    <input type="hidden" name="source" value="{{ old('source', $source) }}">
                    <input type="hidden" name="_t" value="{{ \App\Support\SpamGuard::timestamp() }}">
                    <div class="hp" aria-hidden="true">
                        <label for="cx-website">Ne pas remplir</label>
                        <input id="cx-website" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <label class="chk"><input type="checkbox" name="consent_email" value="1" required @checked(old('consent_email'))> J'accepte de recevoir par e-mail le lien de vote et les nouvelles du projet Saint Joseph (église et centre paroissial du Rouret).</label>
                    <label class="chk"><input type="checkbox" name="consent_sms" value="1" @checked(old('consent_sms'))> J'accepte de recevoir le lien de vote et le résultat par SMS.</label>
                    <button class="btn" type="submit">{{ $state === 'closed' ? 'Recevoir le résultat' : 'Je veux voter pour Le Rouret' }}</button>
                </form>

                @if ($state === 'open')
                    <a class="btn gold" href="{{ route('croix.vote') }}" target="_blank" rel="noopener">Je vote maintenant</a>
                    <a class="skip" href="{{ route('croix.vote') }}" target="_blank" rel="noopener">Voter sans laisser d'adresse</a>
                @endif
            @endif

            <p class="legal">Vos données sont traitées par l'Association Diocésaine de Nice, paroisse Saint-Arnoux, pour vous informer du projet Saint Joseph et de l'opération « La Croix demeure ». Elles ne sont pas transmises à des tiers. Vous pouvez vous désinscrire à tout moment par le lien présent dans chaque message, ou écrire à contact@saint-arnoux.fr. Le vote lui-même se déroule sur le site de S.O.S Calvaires, qui en est seul responsable. <a href="{{ route('privacy') }}">Confidentialité</a></p>
        </aside>
    </div>
</main>

<footer>
    <a href="{{ route('home') }}">Centre paroissial et église Saint Joseph · Association Diocésaine de Nice</a>
    <span>Appel à projets « Que va devenir la Croix du Pape ? » · S.O.S Calvaires</span>
</footer>
</body>
</html>
