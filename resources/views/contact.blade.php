@use('App\Support\SpamGuard')
@extends('layouts.app', [
    'title' => 'Nous contacter',
    'description' => 'Une question sur le projet d\'église Saint Joseph, envie de rejoindre les bénévoles ? Écrivez-nous.',
])

@if (SpamGuard::turnstileEnabled())
    @push('head')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endpush
@endif

@section('content')
    <section class="page-hero">
        <div class="wrap">
            <span class="eyebrow">Contact</span>
            <h1>Écrivez-nous.</h1>
            <p class="lead">Une question sur le projet, une envie de rejoindre les bénévoles, une proposition de mécénat ? Nous vous répondrons rapidement.</p>
        </div>
    </section>

    <section class="contact-page">
        <div class="wrap contact-grid">
            <aside>
                <span class="eyebrow">Projet Saint Joseph</span>
                <h2>Paroisse Saint-Arnoux</h2>
                <p>{{ $site['address'] ?? '' }}</p>
                <p>Vous pouvez aussi nous écrire directement à<br><a href="mailto:{{ config('services.contact.email') }}">{{ config('services.contact.email') }}</a></p>
            </aside>

            <div class="contact-card">
                @if (session('contact-sent'))
                    <div class="alert-success-site" role="status">Merci, votre message a bien été envoyé. Nous vous répondrons très vite.</div>
                @endif

                <form class="form-site" method="post" action="{{ route('contact.send') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="c-name" class="form-label">Nom et prénom</label>
                            <input id="c-name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="120" autocomplete="name">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="c-email" class="form-label">Adresse email</label>
                            <input id="c-email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required maxlength="190" autocomplete="email">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="c-subject" class="form-label">Objet <small class="text-lowercase fw-normal">(facultatif)</small></label>
                            <input id="c-subject" name="subject" type="text" class="form-control" value="{{ old('subject') }}" maxlength="190">
                        </div>
                        <div class="col-12">
                            <label for="c-message" class="form-label">Message</label>
                            <textarea id="c-message" name="message" rows="7" class="form-control @error('message') is-invalid @enderror" required maxlength="5000">{{ old('message') }}</textarea>
                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="hp-field" aria-hidden="true">
                        <label for="c-website">Ne pas remplir</label>
                        <input id="c-website" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <input type="hidden" name="_t" value="{{ SpamGuard::timestamp() }}">

                    @if (SpamGuard::turnstileEnabled())
                        <div class="cf-turnstile mt-3" data-sitekey="{{ config('services.turnstile.site_key') }}" data-language="fr"></div>
                    @endif

                    <button type="submit" class="cta-navy">Envoyer mon message</button>
                    <p class="note">Vos coordonnées servent uniquement à vous répondre. <a href="{{ route('privacy') }}">Confidentialité</a></p>
                </form>
            </div>
        </div>
    </section>
@endsection
