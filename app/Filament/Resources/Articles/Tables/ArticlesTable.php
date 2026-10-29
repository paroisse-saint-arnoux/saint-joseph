<?php

namespace App\Filament\Resources\Articles\Tables;

use App\Enums\ArticleStatus;
use App\Enums\Category;
use App\Filament\Resources\Articles\ArticleResource;
use App\Models\Article;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Clic sur la ligne = édition ; la page publique s'ouvre seulement via « Voir ».
            ->recordUrl(fn (Article $record) => ArticleResource::getUrl('edit', ['record' => $record]))
            ->modifyQueryUsing(fn ($query) => $query->with('author'))
            ->defaultSort(fn ($query) => $query->orderByDesc('is_pinned')->orderByRaw('published_at IS NULL DESC')->orderByDesc('published_at'))
            ->columns([
                ImageColumn::make('og_image')->label('')->disk('public')->square()->imageSize(48),
                TextColumn::make('title')->label('Titre')->searchable()->sortable()->wrap()
                    ->description(fn (Article $record) => '/le-projet/'.$record->category->value.'/'.$record->slug),
                TextColumn::make('category')->label('Catégorie')->badge()->color('gray')->sortable(),
                TextColumn::make('status')->label('Statut')->badge()->sortable(),
                IconColumn::make('is_pinned')->label('Épinglé')->boolean()->trueIcon('heroicon-s-star')->falseIcon('')->trueColor('warning')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')->label('Publication')->dateTime('d/m/Y')->sortable()->placeholder('—'),
                TextColumn::make('author.name')->label('Auteur')->placeholder('—')->toggleable(),
                TextColumn::make('updated_at')->label('Modifié le')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(ArticleStatus::class),
                SelectFilter::make('category')->label('Catégorie')->options(Category::class),
            ])
            ->recordActions([
                // Pas nommée « view » : Filament en ferait l'action du clic sur la ligne.
                Action::make('voir')
                    ->label('Voir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Article $record) => $record->isPubliclyVisible() ? $record->url() : $record->previewUrl())
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
