<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Support\ArticleData;
use Illuminate\Http\Request;

/**
 * API de lecture/écriture des articles (utilisée par Claude Code pour les articles HTML).
 * Authentification : jeton API (Passport) créé dans l'admin (Utilisateurs > Créer un jeton API).
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
        return $request->validate(ArticleData::rules($article));
    }
}
