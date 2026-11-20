{{-- Carrousel de la récolte des olives (photos dans public/images/huile), sur /huile et /commande-huile. --}}
@php
    $harvest = [
        'Un paroissien perché sur une échelle dans un grand olivier, filets verts étendus au sol',
        'Cueillette en haut de l\'échelle, au milieu des branches d\'olivier',
        'Filets étendus sous les oliviers, les cueilleurs au travail',
        'Plusieurs échelles dressées dans les oliviers du terrain',
        'Ramassage des olives tombées dans les filets',
        'Une paroissienne souriante porte une cagette remplie d\'olives',
        'Tri des olives sur une table, au-dessus des cagettes',
        'Tri des olives : on retire les feuilles avant le passage au moulin',
        'Les mains dans les olives, sur la table de tri',
        'Une bande de garçons fiers de leur seau d\'olives',
        'Deux jeunes filles avec une branche d\'olivier',
        'Les enfants de la paroisse, ravis de leur journée de récolte',
        'Les bidons d\'huile d\'olive, tout juste revenus du moulin',
    ];
@endphp
<div class="harvest" data-carousel>
    <div class="track" tabindex="0" aria-label="Photos de la récolte du 7 novembre 2026">
        @foreach ($harvest as $i => $alt)
            @php $file = sprintf('images/huile/recolte-%02d.webp', $i + 1); [$w, $h] = getimagesize(public_path($file)) ?: [600, 640]; @endphp
            <figure><img src="{{ asset($file) }}" alt="{{ $alt }}" width="{{ $w }}" height="{{ $h }}" @if ($i > 1) loading="lazy" @endif></figure>
        @endforeach
    </div>
    <figcaption>La récolte du 7 novembre 2026, sur le terrain de la future église. Faites défiler&nbsp;→</figcaption>
</div>
