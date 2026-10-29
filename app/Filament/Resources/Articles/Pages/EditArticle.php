<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label(fn (Article $record) => $record->isPubliclyVisible() ? 'Voir sur le site' : 'Prévisualiser')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn (Article $record) => $record->isPubliclyVisible() ? $record->url() : $record->previewUrl())
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
