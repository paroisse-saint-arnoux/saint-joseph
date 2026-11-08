@extends('consultation.layout', ['title' => $project->name])

@section('nav')
    <nav class="cs-nav" aria-label="Les projets">
        <span class="cs-nav-label">Projet {{ $index + 1 }} sur {{ $projects->count() }}</span>
        <ol class="cs-nav-list">
            @foreach ($projects as $i => $p)
                <li>
                    <a href="{{ route('consultation.project', $p) }}"
                       @class(['cs-nav-item', 'is-current' => $p->is($project), 'is-done' => in_array($p->id, $voted)])
                       @if ($p->is($project)) aria-current="page" @endif
                       aria-label="Projet {{ $i + 1 }} : {{ $p->name }}{{ in_array($p->id, $voted) ? ' (avis donné)' : '' }}">{{ $i + 1 }}</a>
                </li>
            @endforeach
        </ol>
    </nav>
@endsection

@section('content')
    <article class="cs-project">
        <figure class="cs-model">
            @if ($project->modelPhotoUrl())
                <img src="{{ $project->modelPhotoUrl() }}" alt="Maquette du projet {{ $project->name }}">
            @else
                <div class="cs-model-placeholder">Maquette n°&nbsp;{{ $index + 1 }}</div>
            @endif
        </figure>

        <h1 class="cs-title">{{ $project->name }}</h1>

        <div class="cs-architect">
            @if ($project->architectPhotoUrl())
                <img src="{{ $project->architectPhotoUrl() }}" alt="" width="84" height="105">
            @endif
            <div>
                <b>{{ $project->architect }}</b>
                @if ($project->agency && $project->agency !== $project->name)<span>{{ $project->agency }}</span>@endif
                @if ($project->architect_title)<span>{{ $project->architect_title }}</span>@endif
                @if ($project->city)<span class="cs-city">{{ $project->city }}</span>@endif
            </div>
        </div>

        <form method="POST" action="{{ route('consultation.save', $project) }}" class="cs-vote" data-vote>
            @csrf
            @include('consultation.partials.scale', ['name' => 'overall', 'value' => $vote?->overall])

            <div class="cs-details" data-details @if ($vote?->overall === null) hidden @endif>
                <p class="cs-details-intro">Merci&nbsp;! Pour aller plus loin&nbsp;:</p>
                @include('consultation.partials.scale', ['name' => 'aesthetic', 'value' => $vote?->aesthetic])
                @include('consultation.partials.scale', ['name' => 'spiritual', 'value' => $vote?->spiritual])
                @include('consultation.partials.scale', ['name' => 'convivial', 'value' => $vote?->convivial])

                <label for="cs-comment" class="cs-scale-title">Un commentaire&nbsp;? <span class="cs-optional">(facultatif)</span></label>
                <textarea id="cs-comment" name="comment" rows="4" maxlength="5000" class="cs-input"
                          placeholder="Ce qui vous plaît, ce qui vous gêne…">{{ $vote?->comment }}</textarea>
            </div>

            <p class="cs-status" data-status role="status" aria-live="polite">@if (session('saved'))Réponse enregistrée ✓@endif</p>

            <noscript>
                <button type="submit" class="cs-btn cs-btn-primary">Enregistrer ma réponse</button>
            </noscript>
        </form>

        <div class="cs-pager">
            @if ($previous)
                <a href="{{ route('consultation.project', $previous) }}" class="cs-btn cs-btn-ghost">← Précédent</a>
            @else
                <span></span>
            @endif
            @if ($next)
                <a href="{{ route('consultation.project', $next) }}" class="cs-btn cs-btn-primary">Suivant →</a>
            @else
                <a href="{{ route('consultation.thanks') }}" class="cs-btn cs-btn-primary">Terminer</a>
            @endif
        </div>
    </article>

    @include('consultation.partials.identity')
@endsection
