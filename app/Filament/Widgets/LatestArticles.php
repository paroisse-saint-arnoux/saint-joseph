<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Widgets\Widget;

/** Tableau de bord : les 3 derniers articles créés, avec un accès direct à leur édition. */
class LatestArticles extends Widget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.latest-articles';

    protected function getViewData(): array
    {
        return [
            'articles' => Article::with('author')->latest('created_at')->latest('id')->limit(3)->get(),
            'createUrl' => ArticleResource::getUrl('create'),
            'listUrl' => ArticleResource::getUrl(),
        ];
    }
}
