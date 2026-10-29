@foreach ($articles as $article)
    <a href="{{ $article->url() }}" class="article-card">
        @if ($article->is_pinned)<span class="pin">À la une</span>@endif
        <div class="thumb">
            @if ($article->ogImageUrl())
                <img src="{{ $article->ogImageUrl() }}" alt="{{ $article->og_image_alt ?? '' }}" loading="lazy">
            @else
                <div class="thumb-placeholder"><img src="{{ asset('images/logo-saint-joseph.png') }}" alt=""></div>
            @endif
        </div>
        <div class="body">
            <div class="meta">
                <span class="cat">{{ $article->category->getLabel() }}</span>
                <time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->translatedFormat('j F Y') }}</time>
            </div>
            <h3>{{ $article->title }}</h3>
            @if (filled($article->excerpt))<p>{{ Str::limit($article->excerpt, 160) }}</p>@endif
        </div>
    </a>
@endforeach
