<?php

namespace App\Mcp\Tools;

use App\Enums\ArticleStatus;
use App\Enums\Category;
use App\Mcp\Tools\Concerns\JsonResponse;
use App\Models\Article;
use App\Support\ArticleData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Liste les articles du site (publiés et brouillons), épinglés d\'abord puis du plus récent au plus ancien. Sans le corps du texte : utiliser get-page pour lire un article.')]
class ListArticles extends Tool
{
    use JsonResponse;

    public function handle(Request $request): Response
    {
        $request->validate([
            'status' => ['nullable', 'in:'.implode(',', array_column(ArticleStatus::cases(), 'value'))],
            'category' => ['nullable', 'in:'.implode(',', array_column(Category::cases(), 'value'))],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $articles = Article::query()
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->get('category'), fn ($q, $c) => $q->where('category', $c))
            ->when($request->get('search'), fn ($q, $s) => $q->where('title', 'like', '%'.$s.'%'))
            ->ordered()
            ->limit(100)
            ->get();

        return $this->json(
            $articles->count().' article(s).',
            $articles->map(fn (Article $a) => ArticleData::present($a))->all(),
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(array_column(ArticleStatus::cases(), 'value'))->description('Filtrer : draft (brouillon) ou published (publié).'),
            'category' => $schema->string()->enum(array_column(Category::cases(), 'value'))->description('Filtrer par catégorie.'),
            'search' => $schema->string()->description('Mot à chercher dans le titre.'),
        ];
    }
}
