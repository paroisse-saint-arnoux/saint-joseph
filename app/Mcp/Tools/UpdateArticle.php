<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ArticleSchema;
use App\Mcp\Tools\Concerns\JsonResponse;
use App\Models\Article;
use App\Support\ArticleData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
#[Description('Modifie un article existant. Seuls les champs fournis sont changés ; « body » remplace tout le corps (partir du texte renvoyé par get-page). Ne change pas le statut sauf demande explicite.')]
class UpdateArticle extends Tool
{
    use ArticleSchema, JsonResponse;

    public function handle(Request $request): Response
    {
        $article = Article::findByReference((string) $request->get('article'));
        if (! $article) {
            return Response::error('Article introuvable : utiliser list-articles pour retrouver son slug.');
        }

        $data = $request->validate(['article' => ['required', 'string'], ...ArticleData::rules($article)]);
        unset($data['article']);
        if ($error = $this->checkImageAlt($data + ['og_image_alt' => $article->og_image_alt])) {
            return Response::error($error);
        }

        $article->update($data);

        return $this->json(
            "Article modifié. Lien d'édition : {$article->adminUrl()} — aperçu : {$article->previewUrl()}",
            ArticleData::present($article->refresh()),
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'article' => $schema->string()->description('Slug, adresse publique ou lien d\'édition de l\'article à modifier.')->required(),
            ...$this->articleFields($schema),
        ];
    }
}
