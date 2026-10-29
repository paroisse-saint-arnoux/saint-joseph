<x-mail::message>
# Bonjour {{ $user->name }},

Cliquez sur le bouton ci-dessous pour vous connecter à l'administration du site.

<x-mail::button :url="$url">
Me connecter
</x-mail::button>

Ce lien est valable {{ $minutes }} minutes et ne peut servir qu'une seule fois.
Si vous n'avez pas demandé à vous connecter, ignorez simplement ce message.

{{ config('app.name') }}
</x-mail::message>
