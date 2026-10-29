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

#[Description('Crée un article, en brouillon par défaut. Renvoie admin_url (à donner à l\'utilisateur pour relire et publier) et preview_url (aperçu du brouillon tel qu\'il apparaîtra sur le site).')]
class CreateArticle extends Tool
{
    use ArticleSchema, JsonResponse;

    public function handle(Request $request): Response
    {
        $data = $request->validate(ArticleData::rules());
        if ($error = $this->checkImageAlt($data)) {
            return Response::error($error);
        }

        $article = Article::create($data);

        return $this->json(
            "Article créé ({$article->status->getLabel()}). Donner à l'utilisateur le lien d'édition : {$article->adminUrl()}",
            ArticleData::present($article->refresh()),
        );
    }

    public function schema(JsonSchema $schema): array
    {
        $fields = $this->articleFields($schema);
        $fields['title']->required();
        $fields['category']->required();

        return $fields;
    }
}
