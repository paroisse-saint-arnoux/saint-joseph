@extends('layouts.app', ['title' => 'Connexion', 'noindex' => true])

@section('content')
    <section class="page-hero"><div class="wrap"><span class="eyebrow">Administration</span></div></section>
    <section class="simple-page">
        <div class="wrap">
            <div class="simple-card">
                @if ($valid)
                    <h1>Connexion à l'administration</h1>
                    <p>Cliquez sur le bouton pour terminer votre connexion.</p>
                    <form method="post" action="{{ $action }}">
                        @csrf
                        <button type="submit" class="cta-primary mt-3">Me connecter</button>
                    </form>
                @else
                    <h1>Lien expiré</h1>
                    <p>Ce lien de connexion n'est plus valable : il a déjà servi ou a plus de {{ \App\Support\MagicLink::MINUTES }} minutes.</p>
                    <a class="cta-primary mt-3" href="{{ filament()->getLoginUrl() }}">Recevoir un nouveau lien</a>
                @endif
            </div>
        </div>
    </section>
@endsection
