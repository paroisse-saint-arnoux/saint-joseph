@extends('layouts.app', ['title' => 'Connexion', 'noindex' => true])

@section('content')
    <section class="page-hero"><div class="wrap"><span class="eyebrow">Administration</span></div></section>
    <section class="simple-page">
        <div class="wrap">
            <div class="simple-card">
                @if ($valid)
                    <h1>Connexion en cours…</h1>
                    <p>Vous allez être redirigé vers l'administration.</p>
                    {{-- Envoi automatique en JS : l'utilisateur arrive directement dans l'admin, tandis que
                         les antivirus des messageries (qui ouvrent les liens sans exécuter le JS) ne consomment pas le lien. --}}
                    <form method="post" action="{{ $action }}" id="magic-link-form">
                        @csrf
                        <noscript><button type="submit" class="cta-primary mt-3">Me connecter</button></noscript>
                    </form>
                    <script>document.getElementById('magic-link-form').submit();</script>
                @else
                    <h1>Lien expiré</h1>
                    <p>Ce lien de connexion n'est plus valable : il a déjà servi ou il a expiré.</p>
                    <a class="cta-primary mt-3" href="{{ filament()->getLoginUrl() }}">Recevoir un nouveau lien</a>
                @endif
            </div>
        </div>
    </section>
@endsection
