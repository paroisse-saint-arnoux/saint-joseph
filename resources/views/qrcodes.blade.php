{{-- Générateur de liens avec balises UTM, liens courts (sj.saint-arnoux.fr/xxxxx) et QR codes (QrCodeController).
     Publique mais non référencée : noindex, absente du sitemap. Les sources sont dans App\Support\ShortLinks::SOURCES. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>QR codes et liens — Église Saint Joseph</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-saint-joseph.png') }}">
    @vite(['resources/scss/croix.scss', 'resources/js/qrcodes.js'])
</head>
<body class="croix croix-links">

<main>
    <h1>QR codes et liens<em>.</em></h1>
    <div class="rule"></div>
    <p class="lead">Choisissez la page et l'endroit où le lien sera diffusé : vous obtenez un <b>lien court</b> et son <b>QR code</b>. Le lien porte des balises UTM, et Matomo (comme les statistiques de l'admin) montre alors d'où viennent les visiteurs.</p>

    <form class="card" id="generateur" action="{{ route('qrcodes.store') }}" method="post">
        <label for="l-target">Page visée</label>
        <select id="l-target" name="target" required>
            @foreach ($targets as $group => $pages)
                @if ($pages)
                    <optgroup label="{{ $group }}">
                        @foreach ($pages as $path => $label)
                            <option value="{{ $path }}" data-campaign="{{ \App\Support\ShortLinks::campaignFor($path) }}">{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @endif
            @endforeach
        </select>

        <label for="l-source">Où allez-vous diffuser le lien&nbsp;? <span class="opt">(utm_source)</span></label>
        <select id="l-source" name="source" required>
            @foreach ($sources as $source => [$label])
                <option value="{{ $source }}">{{ $label }}</option>
            @endforeach
        </select>

        <label for="l-campaign">Campagne <span class="opt">(utm_campaign)</span></label>
        <input type="text" id="l-campaign" name="campaign" maxlength="60" list="l-campaigns" required autocomplete="off" aria-describedby="l-campaign-help">
        <datalist id="l-campaigns">
            @foreach ($campaigns as $campaign)
                <option value="{{ $campaign }}"></option>
            @endforeach
        </datalist>
        <p class="l-help" id="l-campaign-help">Proposée selon la page visée&nbsp;; changez-la pour regrouper les liens d'une même opération (ex. «&nbsp;noel-2026&nbsp;», «&nbsp;kermesse&nbsp;»). Elle apparaît telle quelle dans Matomo.</p>

        <label for="l-content">Précision <span class="opt">(facultatif, utm_content)</span></label>
        <input type="text" id="l-content" name="content" maxlength="60" placeholder="ex. église du Rouret" aria-describedby="l-content-help">
        <p class="l-help" id="l-content-help">Pour une affiche ou un flyer, indiquez <b>le lieu ou la date</b> («&nbsp;église du Rouret&nbsp;», «&nbsp;messe du 15 novembre&nbsp;») ; pour un groupe WhatsApp ou Facebook, <b>le nom du groupe</b>. On verra dans Matomo lequel a fait venir le plus de monde.</p>

        <button class="btn" type="submit">Créer le QR code</button>
        <noscript><p class="l-help">Cette page a besoin de JavaScript.</p></noscript>
        <p class="l-error" role="alert" hidden></p>

        <div class="l-result" hidden>
            <div class="l-qr"></div>
            <div class="l-links">
                <label for="l-short">Lien court <span class="opt">(celui du QR code)</span></label>
                <div class="l-out">
                    <input type="text" id="l-short" readonly>
                    <button class="btn" type="button" data-copy="l-short">Copier</button>
                </div>
                <label for="l-url">Lien complet</label>
                <div class="l-out">
                    <input type="text" id="l-url" readonly>
                    <button class="btn" type="button" data-copy="l-url">Copier</button>
                </div>
                <p class="l-downloads">
                    <a class="btn" id="l-png" download>Télécharger en PNG</a>
                    <a class="btn btn-ghost" id="l-svg" download>en SVG <span class="opt">(impression)</span></a>
                </p>
            </div>
        </div>
    </form>

    @if ($recent->isNotEmpty())
        <h2 class="l-h2">Derniers liens créés</h2>
        <div class="l-table">
            <table>
                <thead><tr><th>Lien court</th><th>Page</th><th>Campagne</th><th>Diffusion</th><th></th></tr></thead>
                <tbody>
                    @foreach ($recent as $link)
                        <tr>
                            <td><code>{{ Str::after($link->shortUrl(), "://") }}</code></td>
                            <td><code>{{ $link->target }}</code></td>
                            <td>{{ $link->utm_campaign }}</td>
                            <td>{{ $sources[$link->utm_source][0] ?? $link->utm_source }}@if ($link->utm_content) — {{ $link->utm_content }}@endif</td>
                            <td><button type="button" class="l-copy-row" data-target="{{ $link->target }}" data-source="{{ $link->utm_source }}" data-campaign="{{ $link->utm_campaign }}" data-content="{{ $link->utm_content }}">QR code</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</main>

<footer>
    <a href="{{ route('home') }}">Centre paroissial et église Saint Joseph</a>
    <span>Paroisse Saint-Arnoux · Le Rouret</span>
</footer>
</body>
</html>
