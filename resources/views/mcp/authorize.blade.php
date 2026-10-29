{{-- Page affichée quand on connecte Claude au site (OAuth, Passport::authorizationView). --}}
@extends('layouts.app', ['title' => 'Autoriser Claude', 'noindex' => true, 'bare' => true])

@section('content')
    <section class="page-hero"><div class="wrap"><span class="eyebrow">Connexion de Claude</span></div></section>
    <section class="simple-page">
        <div class="wrap">
            <div class="simple-card oauth-card">
                <h1>Autoriser {{ $client->name }} ?</h1>
                <p>
                    <strong>{{ $client->name }}</strong> demande à accéder au site en votre nom
                    (<strong>{{ $user->email }}</strong>). Il pourra :
                </p>
                <ul>
                    <li>lire les articles, la page d'accueil et les pages légales ;</li>
                    <li>créer des articles (en brouillon, sauf si vous lui demandez de publier) ;</li>
                    <li>modifier les articles et les textes du site quand vous le lui demandez.</li>
                </ul>
                <p class="oauth-note">Il ne pourra rien supprimer. Vous pourrez couper cet accès à tout moment
                    depuis l'admin, page « Connecter Claude ».</p>

                <div class="oauth-actions">
                    <form method="POST" action="{{ route('passport.authorizations.deny') }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="state" value="">
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        <button type="submit" class="cta-ghost">Refuser</button>
                    </form>
                    <form method="POST" action="{{ route('passport.authorizations.approve') }}" id="oauth-approve">
                        @csrf
                        <input type="hidden" name="state" value="">
                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        <button type="submit" class="cta-primary">Autoriser</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <script>
        document.getElementById('oauth-approve').addEventListener('submit', (e) => {
            const button = e.target.querySelector('button');
            button.disabled = true;
            button.textContent = 'Connexion…';
        });
    </script>
@endsection
