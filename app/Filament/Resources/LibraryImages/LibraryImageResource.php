<?php

namespace App\Filament\Resources\LibraryImages;

use App\Filament\Resources\LibraryImages\Pages\EditLibraryImage;
use App\Filament\Resources\LibraryImages\Pages\ListLibraryImages;
use App\Filament\Resources\LibraryImages\RelationManagers\UsagesRelationManager;
use App\Models\LibraryImage;
use App\Support\ImageInfo;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

/**
 * Bibliothèque d'images : les sources dans lesquelles on pioche pour les pages (voir App\Support\Library).
 * Grille avec recherche et glisser-déposer (ListLibraryImages), fiche de chaque image (EditLibraryImage).
 */
class LibraryImageResource extends Resource
{
    protected static ?string $model = LibraryImage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Bibliothèque d\'images';

    protected static ?string $modelLabel = 'image';

    protected static ?string $pluralModelLabel = 'bibliothèque d\'images';

    protected static ?string $slug = 'bibliotheque';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    public static function getBreadcrumb(): string
    {
        return 'Bibliothèque d\'images';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 5])->columnSpanFull()->schema([
                Section::make()->columnSpan(['lg' => 3])->schema([
                    View::make('filament.library.preview'),
                ]),
                Grid::make(1)->columnSpan(['lg' => 2])->schema([
                    Section::make('Titre et description')->schema([
                        TextInput::make('title')->label('Titre')->required()->maxLength(190),
                        Textarea::make('alt')->label('Description')->rows(3)->maxLength(255)
                            ->helperText('Une phrase qui décrit l\'image, pour les personnes malvoyantes. Reprise quand l\'image est utilisée dans un article.'),
                    ]),
                    Section::make('Informations')->columns(2)->schema([
                        TextEntry::make('format')->label('Format')
                            ->state(fn (LibraryImage $record) => ImageInfo::format($record->mime)),
                        TextEntry::make('compression')->label('Compression')
                            ->state(fn (LibraryImage $record) => ImageInfo::compression(
                                (string) Storage::disk(LibraryImage::SOURCE_DISK)->get($record->source_path), $record->mime)),
                        TextEntry::make('dimensions')->label('Taille')
                            ->state(fn (LibraryImage $record) => $record->dimensions().' px'),
                        TextEntry::make('weight')->label('Poids')
                            ->state(fn (LibraryImage $record) => $record->humanSize()),
                        TextEntry::make('original_name')->label('Fichier d\'origine')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('added')->label('Ajoutée')->columnSpanFull()
                            ->state(fn (LibraryImage $record) => 'le '.$record->created_at->format('d/m/Y à H:i').($record->user ? ' par '.$record->user->name : '')),
                    ]),
                ]),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            UsagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLibraryImages::route('/'),
            'edit' => EditLibraryImage::route('/{record}'),
        ];
    }
}
