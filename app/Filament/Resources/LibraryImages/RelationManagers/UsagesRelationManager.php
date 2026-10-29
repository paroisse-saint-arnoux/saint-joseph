<?php

namespace App\Filament\Resources\LibraryImages\RelationManagers;

use App\Models\LibraryUsage;
use App\Support\Images;
use App\Support\Library;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/** Pages qui utilisent l'image, avec l'état de leur copie (à jour ou ancienne version). */
class UsagesRelationManager extends RelationManager
{
    protected static string $relationship = 'usages';

    protected static ?string $title = 'Pages qui utilisent cette image';

    protected static bool $isLazy = false;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['article', 'copy.image']))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Aucune page n\'utilise cette image')
            ->emptyStateDescription('Elle peut être supprimée sans conséquence.')
            ->columns([
                ImageColumn::make('copy.path')->label('Copie')->disk('public')->imageHeight(48),
                TextColumn::make('page')->label('Page')->weight('bold')
                    ->state(fn (LibraryUsage $record) => $record->pageTitle())
                    ->description(fn (LibraryUsage $record) => $record->page_type === 'home' ? null : (LibraryUsage::FIELDS[$record->field] ?? $record->field)),
                TextColumn::make('status')->label('Statut')->badge()
                    ->state(fn (LibraryUsage $record) => $record->page_type === 'home' ? 'En ligne' : $record->article?->status->getLabel())
                    ->color(fn (LibraryUsage $record) => $record->isPublic() ? 'success' : 'gray'),
                TextColumn::make('version')->label('Version')->badge()
                    ->state(fn (LibraryUsage $record) => $record->copy->isOutdated() ? 'Ancienne version' : 'À jour')
                    ->color(fn (LibraryUsage $record) => $record->copy->isOutdated() ? 'warning' : 'success'),
                TextColumn::make('created_at')->label('Utilisée depuis')->date('d/m/Y')->sortable(),
            ])
            ->recordActions([
                Action::make('replace')
                    ->label(fn (LibraryUsage $record) => $record->copy->isOutdated() ? 'Mettre à jour' : 'Remplacer')
                    ->icon('heroicon-o-arrow-path')
                    ->color(fn (LibraryUsage $record) => $record->copy->isOutdated() ? 'warning' : 'gray')
                    ->modalHeading(fn (LibraryUsage $record) => 'Remplacer l\'image dans « '.$record->pageTitle().' »')
                    ->modalDescription('La page utilisera la version actuelle de l\'image, recalculée à la taille choisie.')
                    ->modalSubmitActionLabel('Remplacer')
                    ->schema(fn (LibraryUsage $record) => [self::widthField(Library::currentWidth($record->copy))])
                    ->action(function (LibraryUsage $record, array $data) {
                        Library::replaceInPage($record, self::width($data));
                        Notification::make()->title('Image remplacée dans « '.$record->pageTitle().' »')->success()->send();
                    }),
                Action::make('edit')
                    ->label('Modifier la page')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->url(fn (LibraryUsage $record) => $record->page_type === 'home' ? url('/admin/page-accueil') : $record->article?->adminUrl()),
                Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (LibraryUsage $record) => $record->page_type === 'home' ? url('/') : ($record->article?->isPubliclyVisible() ? $record->article->url() : $record->article?->previewUrl()))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkAction::make('replaceSelected')
                    ->label('Remplacer dans les pages sélectionnées')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->modalHeading('Remplacer l\'image dans les pages sélectionnées')
                    ->modalDescription('Chaque page utilisera la version actuelle de l\'image.')
                    ->modalSubmitActionLabel('Remplacer')
                    ->schema([self::widthField()])
                    ->action(function (Collection $records, array $data) {
                        $records->each(fn (LibraryUsage $usage) => Library::replaceInPage($usage, self::width($data)));
                        Notification::make()->title('Image remplacée dans '.$records->count().' page(s)')->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    /** Taille de la nouvelle copie : celle qu'a l'image dans la page (par défaut), ou une largeur maximale. */
    private static function widthField(?int $current = null): Select
    {
        return Select::make('width')
            ->label('Taille dans la page')
            ->options([
                'current' => $current ? "Garder la taille actuelle ({$current} px de large)" : 'Garder la taille actuelle de chaque page',
                '480' => 'Vignette : 480 px de large maximum (portrait, logo)',
                '800' => 'Petite : 800 px de large maximum',
                '1200' => 'Moyenne : 1 200 px de large maximum',
                (string) Images::MAX_WIDTH => 'Grande : 1 920 px de large maximum',
            ])
            ->default('current')
            ->selectablePlaceholder(false)
            ->required()
            ->helperText('L\'image n\'est jamais agrandie au-delà de sa taille d\'origine.');
    }

    private static function width(array $data): ?int
    {
        return $data['width'] === 'current' ? null : (int) $data['width'];
    }
}
