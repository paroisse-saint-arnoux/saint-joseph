<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request, ?Category $category = null)
    {
        $articles = Article::published()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->ordered()
            ->simplePaginate(self::PER_PAGE);

        // Défilement infini : le JS demande la page suivante et n'insère que les cartes.
        if ($request->ajax()) {
            return response()->json([
                'html' => view('articles.partials.cards', ['articles' => $articles])->render(),
                'next' => $articles->nextPageUrl(),
            ]);
        }

        return view('articles.index', [
            'articles' => $articles,
            'category' => $category,
            'categories' => Category::cases(),
        ]);
    }

    public function show(Request $request, Category $category, string $slug)
    {
        $article = Article::where('slug', $slug)->firstOrFail();

        $isPreview = ! $article->isPubliclyVisible();

        // Brouillon : accessible uniquement avec le jeton de prévisualisation (ou connecté à l'admin).
        abort_if($isPreview && ! auth()->check() && ! hash_equals($article->preview_token, (string) $request->query('apercu')), 404);

        // La catégorie a changé : on redirige vers la bonne URL.
        if ($article->category !== $category) {
            return redirect()->to($isPreview ? $article->previewUrl() : $article->url(), 301);
        }

        return view('articles.show', [
            'article' => $article,
            'isPreview' => $isPreview,
            'related' => Article::published()
                ->where('category', $article->category)
                ->whereKeyNot($article->id)
                ->ordered()
                ->limit(3)
                ->get(),
        ]);
    }
}
