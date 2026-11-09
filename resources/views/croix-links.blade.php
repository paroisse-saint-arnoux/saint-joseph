{{-- Générateur de liens avec balises UTM pour la page /croix (publique, non référencée : noindex, absente du sitemap).
     Les cas de figure (source + medium) sont définis ici ; utm_campaign est toujours « croix ». --}}
@php
    $cases = [
        ['label' => 'Newsletter / campagne Brevo', 'source' => 'brevo', 'medium' => 'email'],
        ['label' => 'E-mail personnel', 'source' => 'email', 'medium' => 'email'],
        ['label' => 'WhatsApp (message ou groupe)', 'source' => 'whatsapp', 'medium' => 'message'],
        ['label' => 'SMS', 'source' => 'sms', 'medium' => 'message'],
        ['label' => 'Facebook', 'source' => 'facebook', 'medium' => 'social'],
        ['label' => 'Instagram', 'source' => 'instagram', 'medium' => 'social'],
        ['label' => 'LinkedIn', 'source' => 'linkedin', 'medium' => 'social'],
        ['label' => 'Affiche ou flyer (QR code)', 'source' => 'affiche', 'medium' => 'print'],
        ['label' => 'Feuille paroissiale', 'source' => 'feuille-paroissiale', 'medium' => 'print'],
        ['label' => 'Site de la paroisse Saint-Arnoux', 'source' => 'saint-arnoux', 'medium' => 'site'],
    ];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Liens de la page Croix du Pape — Église Saint Joseph</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-saint-joseph.png') }}">
    @vite(['resources/scss/croix.scss', 'resources/js/croix.js'])
</head>
<body class="croix croix-links">

<main>
    <h1>Liens à diffuser<em>.</em></h1>
    <div class="rule"></div>
    <p class="lead">Chaque lien porte des balises UTM&nbsp;: les statistiques (Admin &gt; Stats &gt; Croix du Pape, et Matomo) montrent alors d'où viennent les visiteurs et les votes. Choisissez le cas qui correspond, copiez le lien, collez-le dans votre message.</p>

    <div class="card" id="generateur">
        <label for="l-case">Où allez-vous publier le lien&nbsp;?</label>
        <select id="l-case">
            @foreach ($cases as $case)
                <option value="{{ $case['source'] }}" data-medium="{{ $case['medium'] }}">{{ $case['label'] }}</option>
            @endforeach
            <option value="" data-medium="">Autre…</option>
        </select>

        <div class="names l-custom" hidden>
            <div>
                <label for="l-source">Source <span class="opt">(utm_source)</span></label>
                <input type="text" id="l-source" placeholder="ex. bulletin-diocese">
            </div>
            <div>
                <label for="l-medium">Type <span class="opt">(utm_medium)</span></label>
                <input type="text" id="l-medium" placeholder="ex. print, email, social">
            </div>
        </div>

        <label for="l-content">Précision <span class="opt">(facultatif, utm_content)</span></label>
        <input type="text" id="l-content" maxlength="60" placeholder="ex. Aumônerie du Rouret" aria-describedby="l-content-help">
        <p class="l-help" id="l-content-help">Pour un groupe WhatsApp ou Facebook, saisissez <b>le nom du groupe</b>&nbsp;: on verra dans Matomo quel groupe a fait venir le plus de monde. Pour une affiche ou une feuille paroissiale, indiquez le lieu ou la date (ex. «&nbsp;église du Rouret&nbsp;», «&nbsp;messe du 15 novembre&nbsp;»).</p>

        <label>Le lien mène…</label>
        <label class="chk"><input type="radio" name="l-target" value="{{ route('croix') }}" checked> <span>à la page <b>/croix</b> <span class="opt">(recommandé : on peut y laisser son adresse avant de voter)</span></span></label>
        <label class="chk"><input type="radio" name="l-target" value="{{ route('croix.vote') }}"> <span>directement au site de vote <span class="opt">(lien /croix/vote : le clic est compté, puis redirection vers S.O.S Calvaires)</span></span></label>

        <label for="l-url">Votre lien</label>
        <div class="l-out">
            <input type="text" id="l-url" readonly>
            <button class="btn" type="button" id="l-copy">Copier</button>
        </div>
    </div>

    <h2 class="l-h2">Liens tout prêts</h2>
    <div class="l-table">
        <table>
            <thead><tr><th>Cas</th><th>Lien vers la page /croix</th></tr></thead>
            <tbody>
                @foreach ($cases as $case)
                    @php $url = route('croix', ['utm_source' => $case['source'], 'utm_medium' => $case['medium'], 'utm_campaign' => 'croix']); @endphp
                    <tr><td>{{ $case['label'] }}</td><td><code>{{ $url }}</code> <button type="button" class="l-copy-row" data-url="{{ $url }}">Copier</button></td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</main>

<footer>
    <a href="{{ route('croix') }}">La Croix du Pape en Provence ?</a>
    <span>Centre paroissial et église Saint Joseph</span>
</footer>
</body>
</html>
