{{-- Bandeau présent sur toutes les pages : newsletter + don. --}}
@php $donUrl = $site['don_url'] ?? 'https://don-nice.catholique.fr/centre-paroissial-st-arnoux-rouret/'; @endphp
<section class="engage" id="newsletter">
    <div class="wrap engage-grid">
        <div class="newsletter-card reveal">
            <span class="eyebrow">Lettre d'information</span>
            <h2>{{ $site['newsletter_title'] ?? 'Suivez la construction.' }}</h2>
            <p>{{ $site['newsletter_text'] ?? '' }}</p>

            <form class="form-site" method="post" action="{{ route('newsletter') }}" data-newsletter>
                @csrf
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="nl-first-name" class="form-label">Prénom</label>
                        <input id="nl-first-name" class="form-control" type="text" name="first_name" required maxlength="80" autocomplete="given-name">
                    </div>
                    <div class="col-sm-6">
                        <label for="nl-last-name" class="form-label">Nom</label>
                        <input id="nl-last-name" class="form-control" type="text" name="last_name" required maxlength="80" autocomplete="family-name">
                    </div>
                    <div class="col-12">
                        <label for="nl-email" class="form-label">Adresse email</label>
                        <input id="nl-email" class="form-control" type="email" name="email" required maxlength="190" autocomplete="email">
                    </div>
                </div>
                <div class="hp-field" aria-hidden="true">
                    <label for="nl-website">Ne pas remplir</label>
                    <input id="nl-website" type="text" name="website" tabindex="-1" autocomplete="off">
                </div>
                <input type="hidden" name="_t" value="{{ \App\Support\SpamGuard::timestamp() }}">
                <button type="submit" class="cta-navy">Je m'inscris</button>
                <p class="form-feedback @if (session('newsletter')) {{ session('newsletter.ok') ? 'ok' : 'error' }} @endif" role="status" aria-live="polite">{{ session('newsletter.message') }}</p>
                <p class="note">Vos données servent uniquement à vous envoyer des nouvelles du projet. Désinscription en un clic. <a href="{{ route('privacy') }}">Confidentialité</a></p>
            </form>
        </div>

        <aside class="don-card reveal">
            <span class="eyebrow">Faire un don</span>
            <h2>{{ $site['don_title'] ?? 'Chaque pierre compte.' }}</h2>
            <p>{{ $site['don_text'] ?? '' }}</p>
            <a class="cta-primary" href="{{ $donUrl }}" target="_blank" rel="noopener">Faire un don &rarr;</a>
            <div class="qr">
                <img src="{{ asset('images/qr-code-don.png') }}" alt="QR code vers la page de don" width="72" height="72" loading="lazy">
                <span>Scannez pour<br>faire un don</span>
            </div>
        </aside>
    </div>
</section>
