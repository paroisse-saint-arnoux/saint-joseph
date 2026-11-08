@use('App\Support\SpamGuard')
@extends('consultation.layout')

@section('content')
    <h1 class="cs-title">Quel projet pour l'église Saint Joseph&nbsp;?</h1>
    <p class="cs-lead">Cinq équipes d'architectes ont imaginé le futur centre paroissial et la future église.
        Donnez-nous votre avis sur chaque maquette&nbsp;: cela prend environ 5&nbsp;minutes.</p>

    <form method="POST" action="{{ route('consultation.identify') }}" class="cs-card" novalidate>
        @csrf
        <label for="cs-email" class="cs-label">Pour commencer, votre adresse email&nbsp;:</label>
        <input id="cs-email" name="email" type="email" inputmode="email" autocomplete="email" autocapitalize="none" spellcheck="false"
               value="{{ old('email') }}" required maxlength="190" placeholder="prenom.nom@exemple.fr"
               @class(['cs-input', 'is-invalid' => $errors->has('email')]) @error('email') aria-describedby="cs-email-error" aria-invalid="true" @enderror>
        @error('email')<p class="cs-error" id="cs-email-error">{{ $message }}</p>@enderror

        <div class="hp-field" aria-hidden="true">
            <label for="cs-website">Ne pas remplir</label>
            <input id="cs-website" type="text" name="website" tabindex="-1" autocomplete="off">
        </div>
        <input type="hidden" name="_t" value="{{ SpamGuard::timestamp() }}">

        <button type="submit" class="cs-btn cs-btn-primary">Continuer</button>

        <p class="cs-note">Votre adresse sert à enregistrer vos réponses (vous pourrez les modifier plus tard)
            et à vous tenir informé(e) de l'avancement du projet. Elle n'est jamais communiquée à des tiers.</p>
    </form>
@endsection
