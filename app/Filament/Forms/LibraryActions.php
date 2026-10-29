<?php

namespace App\Filament\Forms;

use App\Models\LibraryImage;
use App\Support\Images;
use App\Support\Library;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Actions « Choisir dans la bibliothèque » du formulaire d'article. Chaque image choisie devient une copie
 * propre à l'article (Library::copyForArticle), comme pour une image envoyée directement.
 */
class LibraryActions
{
    /** Tailles proposées pour une image du texte (largeur d'affichage en pixels). */
    private const SIZES = [
        'full' => 'Pleine largeur du texte',
        '600' => 'Grande (600 px)',
        '400' => 'Moyenne (400 px)',
        '250' => 'Petite (250 px)',
        'custom' => 'Autre largeur…',
    ];

    /** Image de partage (champ FileUpload og_image + description og_image_alt). */
    public static function cover(): Action
    {
        return self::base('coverFromLibrary', 'Choisir l\'image de partage', multiple: false)
            ->modalSubmitActionLabel('Utiliser cette image')
            ->action(function (array $data, Get $get, Set $set) {
                $image = self::images($data)->first();
                $set('og_image', [(string) Str::uuid() => Library::copyForArticle($image, 'articles')]);
                if (blank($get('og_image_alt'))) {
                    $set('og_image_alt', $image->alt ?: $image->title);
                }
            });
    }

    /** Galerie (Repeater gallery : path + alt). */
    public static function gallery(): Action
    {
        return self::base('galleryFromLibrary', 'Ajouter des photos à la galerie', multiple: true)
            ->modalSubmitActionLabel('Ajouter à la galerie')
            ->action(function (array $data, Get $get, Set $set) {
                $items = $get('gallery') ?? [];
                foreach (self::images($data) as $image) {
                    $items[(string) Str::uuid()] = [
                        'path' => [(string) Str::uuid() => Library::copyForArticle($image, 'articles/galerie')],
                        'alt' => $image->alt ?: $image->title,
                    ];
                }
                $set('gallery', $items);
            });
    }

    /** Corps Markdown : insertion de ![description](adresse) à l'endroit du curseur (voir sjInsertMarkdown). */
    public static function markdown(): Action
    {
        return self::base('markdownFromLibrary', 'Insérer des images dans le texte', multiple: true, extra: [
            Grid::make(2)->schema([
                Select::make('size')
                    ->label('Taille d\'affichage')
                    ->options(self::SIZES)
                    ->default('full')
                    ->selectablePlaceholder(false)
                    ->live(),
                TextInput::make('width')
                    ->label('Largeur (pixels)')
                    ->numeric()->minValue(50)->maxValue(1200)
                    ->visible(fn (Get $get) => $get('size') === 'custom')
                    ->required(fn (Get $get) => $get('size') === 'custom'),
            ]),
        ])
            ->modalDescription('Les images sont insérées à l\'endroit du curseur, dans l\'ordre des numéros. '
                .'La taille reste modifiable ensuite dans le texte : {width=400} après l\'image.')
            ->modalSubmitActionLabel('Insérer')
            ->action(function (array $data, Component $livewire) {
                $width = match ($data['size'] ?? 'full') {
                    'full' => null,
                    'custom' => (int) $data['width'],
                    default => (int) $data['size'],
                };

                $markdown = self::images($data)
                    ->map(function (LibraryImage $image) use ($width) {
                        // Fichier à 2 × la largeur affichée (écrans haute densité), sans dépasser l'original.
                        $path = $width
                            ? Library::copyAtWidth($image, 'articles/contenu', min(2 * $width, $image->width, Images::MAX_WIDTH))->path
                            : Library::copyForArticle($image, 'articles/contenu');

                        return '!['.str_replace(['[', ']'], '', $image->alt ?: $image->title).'](/storage/'.$path.')'
                            .($width ? '{width='.$width.'}' : '');
                    })
                    ->join("\n\n");

                $livewire->js('window.sjInsertMarkdown('.json_encode("\n\n".$markdown."\n\n").')');
            });
    }

    private static function base(string $name, string $heading, bool $multiple, array $extra = []): Action
    {
        return Action::make($name)
            ->label(match ($name) {
                'galleryFromLibrary' => 'Ajouter depuis la bibliothèque',
                'markdownFromLibrary' => 'Insérer une image de la bibliothèque',
                default => 'Choisir dans la bibliothèque',
            })
            ->icon('heroicon-o-photo')
            ->modalHeading($heading)
            ->modalWidth('5xl')
            ->schema([
                LibraryPicker::make('images')->hiddenLabel()->multiple($multiple)->required()
                    ->validationMessages(['required' => 'Choisissez au moins une image.']),
                ...$extra,
            ]);
    }

    /** @return Collection<int, LibraryImage> images choisies, dans l'ordre de sélection */
    private static function images(array $data): Collection
    {
        $ids = array_map('intval', $data['images'] ?? []);

        return LibraryImage::whereIn('id', $ids)->get()->sortBy(fn (LibraryImage $i) => array_search($i->id, $ids))->values();
    }
}
