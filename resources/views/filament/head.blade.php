{{-- Admin : icônes du site et aperçu des liens partagés (sans ces balises, WhatsApp, Slack… prenaient le logo
     crème, invisible sur fond blanc). Les robots ne voient que la page de connexion. --}}
@include('partials.icons')
<meta name="robots" content="noindex, nofollow">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Église Saint Joseph — Le Rouret">
<meta property="og:title" content="Administration du site Saint Joseph">
<meta property="og:description" content="Espace réservé à l'équipe du projet de l'église Saint Joseph (Paroisse Saint-Arnoux, Le Rouret).">
<meta property="og:locale" content="fr_FR">
<meta property="og:image" content="{{ asset('images/og-saint-joseph.jpg') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="{{ asset('images/og-saint-joseph.jpg') }}">
