@if (app()->isProduction() && filled(config('services.matomo.url')) && filled(config('services.matomo.site_id')))
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
    </script>
@endif
