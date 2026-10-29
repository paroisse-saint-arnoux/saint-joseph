<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Models\Article;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $articles = Article::published()->ordered()->get();

        $urls = collect([
            ['loc' => route('home'), 'lastmod' => $articles->max('updated_at')],
            ['loc' => route('articles.index'), 'lastmod' => $articles->max('updated_at')],
            ...collect(Category::cases())
                ->filter(fn (Category $c) => $articles->contains('category', $c))
                ->map(fn (Category $c) => ['loc' => route('articles.category', $c), 'lastmod' => $articles->where('category', $c)->max('updated_at')]),
            ...$articles->map(fn (Article $a) => ['loc' => $a->url(), 'lastmod' => $a->updated_at]),
            ['loc' => route('contact'), 'lastmod' => null],
        ]);

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }
}
