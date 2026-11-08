<form method="POST" action="{{ route('consultation.forget') }}" class="cs-identity">
    @csrf
    Vous répondez avec l'adresse <b>{{ $participant->email }}</b>.
    <button type="submit" class="cs-link">Ce n'est pas vous&nbsp;?</button>
</form>
