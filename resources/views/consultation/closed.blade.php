@extends('consultation.layout')

@section('content')
    <h1 class="cs-title">La consultation est terminée</h1>
    <p class="cs-lead">Merci à toutes celles et ceux qui ont donné leur avis sur les projets&nbsp;!</p>
    <a href="{{ route('articles.index') }}" class="cs-btn cs-btn-primary">Suivre l'avancement du projet</a>
@endsection
