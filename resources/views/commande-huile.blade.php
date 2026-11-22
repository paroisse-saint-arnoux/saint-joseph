{{-- Réservation de bouteilles d'huile d'olive (à retirer à la kermesse). Page autonome, sans le menu du site,
     pensée pour le téléphone (styles de huile.scss). Publique mais non référencée : noindex + X-Robots-Tag, hors sitemap. --}}
@use('App\Support\OilOrders')
@use('App\Models\OilProduct')
@php
    $title = 'Réservez l\'huile des Bâtisseurs';
    $description = 'Offrez l\'huile d\'olive récoltée sur le terrain de la future église Saint Joseph, au Rouret : un beau cadeau de Noël qui fait connaître le projet.';
    $open = $settings['open'];
    $available = $products->sum(fn ($p) => $p->maxPerOrder()) > 0;
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
    <meta property="og:url" content="{{ route('huile.order') }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:image" content="{{ asset('images/huile-bouteilles.webp') }}">
    @vite(['resources/scss/huile.scss', 'resources/js/huile.js'])
    @include('partials.matomo')
</head>
<body class="huile commande">

<header class="hero hero-short">
    <div class="in">
        <img class="logo" src="{{ asset('images/logo-saint-joseph-blanc.svg') }}" alt="Église Saint Joseph" width="88" height="88">
        <p class="kicker">Paroisse Saint-Arnoux · Le Rouret</p>
        <h1 class="h1-small">L'huile des <em>Bâtisseurs</em></h1>
        <p class="lead">Offrez à vos proches l'huile d'olive récoltée sur le terrain de la <b>future église Saint Joseph</b>.</p>
    </div>
</header>

<main>
    @if ($order)
        <section class="block card done" id="commande" tabindex="-1">
            <p class="eyebrow">Réservation enregistrée</p>
            <h2>Merci {{ $order->first_name }}<em>&nbsp;!</em></h2>
            <p class="order-number">Commande n°&nbsp;<b>{{ $order->number }}</b></p>
            <table class="recap">
                <tbody>
                    @foreach ($order->items as $item)
                        <tr><td>{{ $item->quantity }} × {{ $item->label }}</td><td>{{ OilOrders::money($item->totalCents()) }}</td></tr>
                    @endforeach
                    @if ($order->discounted)
                        <tr><td>Tarif réduit (−{{ OilOrders::DISCOUNT_PERCENT }}&nbsp;%)</td><td>−{{ OilOrders::money($order->fullPriceCents() - $order->total_cents) }}</td></tr>
                    @endif
                </tbody>
                <tfoot><tr><th>Total</th><th>{{ $order->total() }}</th></tr></tfoot>
            </table>
            <p class="pickup"><b>À retirer {{ $settings['pickup'] }}.</b> {{ $settings['payment'] }}</p>
            <p>Un e-mail récapitulatif vous est envoyé à <b>{{ $order->email }}</b>. Pensez à regarder dans les indésirables si vous ne le voyez pas.</p>
            <a class="btn btn-navy" href="https://wa.me/?text={{ rawurlencode('J\'ai réservé l\'huile des Bâtisseurs, récoltée pour le projet de l\'église Saint Joseph au Rouret. Vous aussi : '.route('huile.order')) }}" target="_blank" rel="noopener">Partager à mes proches</a>
            <a class="more" href="{{ route('huile.order', ['nouvelle' => 1]) }}#commande">Faire une autre réservation →</a>
        </section>
    @else

    <section class="block intro">
        <figure class="bottles">
            <img src="{{ asset('images/huile-bouteilles.webp') }}" alt="Bouteilles d'huile d'olive dorée, étiquetées « L'huile des Bâtisseurs », avec le logo de l'église Saint Joseph et un QR code" width="900" height="1142">
        </figure>
        <div>
            <p class="eyebrow">Un cadeau qui a du sens</p>
            <h2>Un beau cadeau de Noël.</h2>
            <p>Les olives ont été récoltées le 7 novembre par les paroissiens, petits et grands, sur les oliviers du terrain où sera construite l'église et chez des paroissiens, puis pressées au Moulin d'H. <b>Toute la vente aide à bâtir l'église et le centre paroissial.</b></p>
            <p>Sur chaque bouteille, un <b>QR code</b> mène à une page qui raconte la récolte et le projet&nbsp;: en l'offrant, vous faites connaître la construction de la paroisse.</p>
        </div>
    </section>

    <section class="block card" id="commande">
        @if (! $open)
            <h2>Les réservations sont closes.</h2>
            <p>Merci à tous&nbsp;! Pour toute question, <a href="{{ route('contact') }}">écrivez-nous</a>.</p>
        @elseif (! $available)
            <h2>Toutes les bouteilles ont trouvé preneur<em>&nbsp;!</em></h2>
            <p>Merci pour votre enthousiasme. Vous pouvez toujours soutenir le projet par un don.</p>
            <a class="btn btn-gold" href="{{ \App\Models\Setting::get('site.don_url') ?: '/don' }}" data-don="commande-huile">Faire un don</a>
        @else
            <p class="eyebrow">Réserver</p>
            <h2>Choisissez vos bouteilles.</h2>
            <p class="pickup">À retirer <b>{{ $settings['pickup'] }}</b>. {{ $settings['payment'] }}</p>

            <form action="{{ route('huile.order.store') }}" method="post" novalidate data-oil-order>
                @csrf
                @if ($errors->any())
                    <div class="error" role="alert">
                        @foreach (array_unique($errors->all()) as $message)<p>{{ $message }}</p>@endforeach
                        @if (session('oil_stock_short'))
                            <p>D'autres bouteilles vont être remises en stock d'ici Noël&nbsp;: <a href="{{ route('contact') }}">contactez-nous</a> pour en réserver, nous avons bien noté vos coordonnées. Vous pouvez aussi réserver les bouteilles encore disponibles ci-dessous.</p>
                        @endif
                    </div>
                @endif

                <fieldset class="products">
                    <legend>Bouteilles <span class="opt">({{ OilProduct::MAX_PER_ORDER }} au plus de chaque format)</span></legend>
                    @foreach ($products as $product)
                        @php $max = $product->maxPerOrder(); $value = min($max, (int) old('quantities.'.$product->id, 0)); @endphp
                        <div @class(['product', 'out' => $max === 0])>
                            <label for="q{{ $product->id }}">
                                <b>{{ $product->label }}</b>
                                <span class="price">{{ OilOrders::money($product->price_cents) }}</span>
                                @if ($max === 0)
                                    <span class="stock">Épuisé</span>
                                @else
                                    <span class="stock-count">(stock&nbsp;: {{ $product->stock }}&nbsp;bouteille{{ $product->stock > 1 ? 's' : '' }})</span>
                                @endif
                            </label>
                            <div class="qty">
                                <button type="button" class="step" data-step="-1" aria-label="Une bouteille de {{ $product->label }} en moins" hidden>−</button>
                                <input type="number" id="q{{ $product->id }}" name="quantities[{{ $product->id }}]" value="{{ $value }}" min="0" max="{{ $max }}" inputmode="numeric" data-price="{{ $product->price_cents }}" @disabled($max === 0) @if ($errors->has('quantities.'.$product->id)) aria-invalid="true" @endif>
                                <button type="button" class="step" data-step="1" aria-label="Une bouteille de {{ $product->label }} en plus" hidden>+</button>
                            </div>
                        </div>
                    @endforeach
                    <p class="total" aria-live="polite" hidden>Total&nbsp;: <b data-total>0&nbsp;€</b></p>
                </fieldset>

                <div class="names">
                    <div>
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name" maxlength="80" @if ($errors->has('first_name')) aria-invalid="true" @endif>
                    </div>
                    <div>
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name" maxlength="80" @if ($errors->has('last_name')) aria-invalid="true" @endif>
                    </div>
                </div>
                <label for="tel">Téléphone</label>
                <input type="tel" id="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel" inputmode="tel" placeholder="06 12 34 56 78" maxlength="40" @if ($errors->has('phone')) aria-invalid="true" @endif>
                <label for="email">E-mail <span class="opt">(pour recevoir votre numéro de commande)</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" inputmode="email" placeholder="vous@exemple.fr" maxlength="190" @if ($errors->has('email')) aria-invalid="true" @endif>

                <input type="hidden" name="_t" value="{{ \App\Support\SpamGuard::timestamp() }}">
                <div class="hp" aria-hidden="true">
                    <label for="ho-website">Ne pas remplir</label>
                    <input id="ho-website" type="text" name="website" tabindex="-1" autocomplete="off">
                </div>
                <button class="btn btn-gold btn-big" type="submit">Réserver mes bouteilles</button>
            </form>
            <p class="legal">Vos coordonnées sont traitées par l'Association Diocésaine de Nice, paroisse Saint-Arnoux, pour préparer votre commande et vous tenir informé(e) du projet Saint Joseph. Elles ne sont pas transmises à des tiers. <a href="{{ route('privacy') }}">Confidentialité</a></p>
        @endif
    </section>
    @endif

    <section class="block harvest-block">
        <p class="eyebrow">La récolte</p>
        <h2>Une huile d'ici, faite ensemble.</h2>
        @include('partials.huile-carousel')
    </section>
</main>

<footer>
    <a href="{{ route('home') }}"><img src="{{ asset('images/logo-saint-joseph-blanc.svg') }}" alt="" width="40" height="40"> Centre paroissial et église Saint Joseph</a>
    <span>Paroisse Saint-Arnoux · Association Diocésaine de Nice</span>
    <span><a href="{{ route('contact') }}">Contact</a> · <a href="{{ route('legal') }}">Mentions légales</a></span>
</footer>
</body>
</html>
