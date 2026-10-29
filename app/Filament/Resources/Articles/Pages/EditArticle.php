<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Articles\Schemas\ArticleForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ArticleForm::applyStatus($data);
    }

    /** En bas : Supprimer à gauche, Annuler / Enregistrer à droite (les actions sont alignées à droite, de droite à gauche). */
    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            DeleteAction::make()->extraAttributes(['style' => 'margin-inline-end: auto']),
        ];
    }
}
