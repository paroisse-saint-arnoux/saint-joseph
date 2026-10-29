<?php

namespace App\Http\Controllers\Api;

use App\Enums\ArticleFormat;
use App\Enums\ArticleStatus;
use App\Enums\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * API de lecture/écriture des articles (utilisée par Claude Code pour les articles HTML).
 * Authentification : jeton Sanctum créé dans l'admin (Utilisateurs > Créer un jeton API).
 */
class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $articles = Article::query()
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('category'), fn ($q, $c) => $q->where('category', $c))
            ->ordered()
            ->get();

        return ArticleResource::collection($articles);
    }

    public function show(Article $article)
    {
        return new ArticleResource($article);
    }

    public function store(Request $request)
    {
        $article = Article::create($this->validated($request));

        return (new ArticleResource($article))->response()->setStatusCode(201);
    }

    public function update(Request $request, Article $article)
    {
        $article->update($this->validated($request, $article));

        return new ArticleResource($article->refresh());
    }

    public function destroy(Article $article)
    {
        $article->delete();

        return response()->noContent();
    }

    private function validated(Request $request, ?Article $article = null): array
    {
        $required = $article ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:190'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('articles')->ignore($article)],
            'category' => [$required, Rule::enum(Category::class)],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:400'],
            'format' => ['sometimes', Rule::enum(ArticleFormat::class)],
            'body' => ['sometimes', 'nullable', 'string'],
            'og_image' => ['sometimes', 'nullable', 'string', 'max:255'],
            'og_image_alt' => ['sometimes', 'nullable', 'string', 'max:190'],
            'gallery' => ['sometimes', 'nullable', 'array'],
            'gallery.*.path' => ['required', 'string', 'max:255'],
            'gallery.*.alt' => ['required', 'string', 'max:190'],
            'status' => ['sometimes', Rule::enum(ArticleStatus::class)],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'is_pinned' => ['sometimes', 'boolean'],
        ]);
    }
}
