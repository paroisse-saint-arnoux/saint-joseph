@php
    $donUrl = $site['don_url'] ?? 'https://don-nice.catholique.fr/centre-paroissial-st-arnoux-rouret/';
    $team = \App\Models\Article::published()->where('category', \App\Enums\Category::Equipe)->ordered()->first();
@endphp
<footer class="site-footer">
    <div class="wrap">
        <div class="foot-top">
            <div class="foot-brand">
                <img class="fl" src="{{ asset('images/logo-saint-joseph.png') }}" alt="Église Saint Joseph — Bâtisseurs du Seigneur" loading="lazy">
                <p>{{ $site['about'] ?? '' }}</p>
                <p class="foot-addr">{{ $site['address'] ?? '' }}</p>
            </div>
            <div class="foot-col">
                <h4>Le projet</h4>
                <a href="{{ route('home') }}#decouvrir">Découvrir</a>
                <a href="{{ route('articles.index') }}">Les articles</a>
                <a href="{{ $team?->url() ?? route('articles.category', \App\Enums\Category::Equipe) }}">Qui sommes-nous</a>
                <a href="{{ route('contact') }}">Contact</a>
                <a href="{{ $donUrl }}" target="_blank" rel="noopener">Faire un don</a>
            </div>
            <div class="foot-col">
                <h4>Nous suivre</h4>
                <div class="foot-social">
                    @if (filled($site['facebook_url'] ?? null))
                        <a href="{{ $site['facebook_url'] }}" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12a10 10 0 10-11.5 9.9v-7H8v-2.9h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6v1.8H16l-.4 2.9h-2.1v7A10 10 0 0022 12z"/></svg></a>
                    @endif
                    @if (filled($site['linkedin_url'] ?? null))
                        <a href="{{ $site['linkedin_url'] }}" target="_blank" rel="noopener" aria-label="LinkedIn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 3a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h14zM8.3 18.3V9.7H5.6v8.6h2.7zM7 8.5a1.6 1.6 0 100-3.1 1.6 1.6 0 000 3.1zm11.4 9.8v-4.7c0-2.5-1.3-3.7-3.1-3.7-1.4 0-2 .8-2.4 1.3V9.7H10v8.6h2.7v-4.8c0-.3 0-.5.1-.7.2-.5.7-1 1.5-1 1 0 1.4.8 1.4 2v4.5h2.7z"/></svg></a>
                    @endif
                </div>
                <a href="#newsletter">Lettre d'information</a>
            </div>
            <div class="foot-col">
                <h4>Liens utiles</h4>
                @foreach ($site['useful_links'] ?? [] as $link)
                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener">{{ $link['label'] }} ↗</a>
                @endforeach
            </div>
        </div>
        <div class="foot-bot">
            <span>© {{ date('Y') }} Paroisse Saint-Arnoux · Association Diocésaine de Nice — Le Rouret (06) · <a href="{{ route('legal') }}">Mentions légales</a> · <a href="{{ route('privacy') }}">Confidentialité</a></span>
            <span class="dio">Sous l'égide du <img src="{{ asset('images/diocese-de-nice.png') }}" alt="Diocèse de Nice" loading="lazy"></span>
        </div>
        <div class="tagline">Pour aujourd'hui, pour demain, pour la Mission.<br><span>Foi · Transmission · Fraternité · Espérance</span></div>
    </div>
</footer>
