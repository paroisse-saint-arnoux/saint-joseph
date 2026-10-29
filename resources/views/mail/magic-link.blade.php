<x-mail::message>
# Bonjour {{ $user->name }},

@if ($invitation)
Vous avez désormais accès à l'administration du site {{ config('app.name') }} : articles, page d'accueil, photos et vidéos.

Il n'y a pas de mot de passe : cliquez sur le bouton ci-dessous pour vous connecter.

<x-mail::button :url="$url">
Me connecter
</x-mail::button>

Ce lien est valable {{ $validity }} et ne peut servir qu'une seule fois. Vous resterez ensuite connecté sur cet appareil.
Plus tard, pour vous connecter depuis un autre appareil, saisissez simplement votre adresse sur
[la page de connexion]({{ filament()->getLoginUrl() }}) : vous recevrez un nouveau lien.
@else
Cliquez sur le bouton ci-dessous pour vous connecter à l'administration du site.

<x-mail::button :url="$url">
Me connecter
</x-mail::button>

Ce lien est valable {{ $validity }} et ne peut servir qu'une seule fois.
Si vous n'avez pas demandé à vous connecter, ignorez simplement ce message.
@endif

{{ config('app.name') }}
</x-mail::message>
