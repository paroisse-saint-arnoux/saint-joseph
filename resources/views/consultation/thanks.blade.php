@use('App\Support\Consultation')
@extends('consultation.layout', ['title' => 'Merci'])

@section('content')
    @php $missing = $projects->filter(fn ($p) => ($votes[$p->id]->overall ?? null) === null); @endphp

    <h1 class="cs-title">Merci pour votre avis&nbsp;!</h1>
    @if ($missing->isEmpty())
        <p class="cs-lead">Vous avez donné votre avis sur les {{ $projects->count() }} projets. Vous pouvez encore modifier vos réponses jusqu'à la fin de la consultation.</p>
    @else
        <p class="cs-lead">Il reste {{ $missing->count() > 1 ? $missing->count().' projets' : 'un projet' }} sans avis.</p>
    @endif

    <ul class="cs-summary">
        @foreach ($projects as $i => $p)
            @php $overall = $votes[$p->id]->overall ?? null; @endphp
            <li>
                <a href="{{ route('consultation.project', $p) }}">
                    <span @class(['cs-nav-item', 'is-done' => $overall !== null])>{{ $i + 1 }}</span>
                    <span class="cs-summary-name">{{ $p->name }}</span>
                    <span class="cs-summary-vote">{{ $overall === null ? 'Donner mon avis' : Consultation::SCALES['overall']['steps'][$overall] }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <a href="{{ route('articles.index') }}" class="cs-btn cs-btn-ghost">Découvrir le projet sur le site</a>

    @include('consultation.partials.identity')
@endsection
