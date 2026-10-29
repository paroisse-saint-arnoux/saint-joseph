<x-mail::message>
# Nouveau message depuis le site

**De :** {{ $data['name'] }} ({{ $data['email'] }})
@if (filled($data['subject']))
**Objet :** {{ $data['subject'] }}
@endif

<x-mail::panel>
{{ $data['message'] }}
</x-mail::panel>

Répondez directement à cet email pour écrire à {{ $data['name'] }}.
</x-mail::message>
