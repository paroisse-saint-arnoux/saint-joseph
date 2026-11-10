@if (app()->isProduction() && filled(config('services.matomo.url')) && filled(config('services.matomo.site_id')))
    @php
        // Adresse de la page de don : sert à reconnaître aussi les liens de don écrits dans les articles.
        $donUrl = \App\Models\Setting::get('site.don_url') ?: 'https://don-nice.catholique.fr/centre-paroissial-st-arnoux-rouret/';
    @endphp
    {{-- Matomo auto-hébergé, sans cookie : pas de bandeau de consentement nécessaire. --}}
    <script>
        var _paq = window._paq = window._paq || [];
        _paq.push(['disableCookies']);
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function() {
            var u = @json(Str::finish(config('services.matomo.url'), '/'));
            _paq.push(['setTrackerUrl', u + 'matomo.php']);
            _paq.push(['setSiteId', @json((string) config('services.matomo.site_id'))]);
            var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
            g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
        })();

        // Événement sur tout bouton ou lien de don : catégorie « Don », action = emplacement du bouton
        // (attribut data-don, sinon « article » pour un lien dans le texte d'un article, ou « autre »),
        // nom = page où l'on a cliqué.
        (function() {
            var donUrl = @json(rtrim($donUrl, '/'));
            document.addEventListener('click', function(e) {
                var a = e.target.closest && e.target.closest('a[href]');
                if (!a) return;
                var place = a.getAttribute('data-don');
                if (!place) {
                    if (a.href.replace(/\/$/, '').indexOf(donUrl) !== 0) return;
                    place = a.closest('.prose') ? 'article' : 'autre';
                }
                _paq.push(['trackEvent', 'Don', place, location.pathname]);
            }, true);
        })();
    </script>
@endif
