@extends('layouts.app', ['title' => $title])

@section('content')
    <section class="page-hero">
        <div class="article-wrap">
            <span class="eyebrow">Informations</span>
            <h1>{{ $title }}</h1>
        </div>
    </section>
    <section class="article">
        <div class="article-wrap">
            <div class="prose">{!! $html !!}</div>
        </div>
    </section>
@endsection
