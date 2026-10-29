<?php

namespace App\Filament\Resources\Articles\Schemas;

use App\Enums\ArticleFormat;
use App\Enums\ArticleStatus;
use App\Enums\Category;
use App\Filament\Forms\ImageUpload;
use App\Filament\Forms\LibraryActions;
use App\Models\Article;
use Filament\Actions\Action;
use App\Support\Markdown;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)
                    ->columnSpan(['default' => 3, 'lg' => 2])
                    ->schema([
                        Section::make('Article')->schema([
                            TextInput::make('title')
                                ->label('Titre')
                                ->required()
                                ->maxLength(190),
                            Textarea::make('excerpt')
                                ->label('Introduction')
                                ->helperText('2 ou 3 phrases. Affichée en tête d\'article, sur les cartes et lors des partages (Facebook, WhatsApp…).')
                                ->rows(3)
                                ->maxLength(400),
                        ]),

                        Section::make('Corps de l\'article')->schema([
                            Radio::make('format')
                                ->label('Type de contenu')
                                ->options(ArticleFormat::class)
                                ->default(ArticleFormat::Markdown)
                                ->inline()
                                ->live(),

                            Tabs::make('editor')
                                ->visible(fn (Get $get) => ! self::isHtml($get))
                                ->tabs([
                                    Tab::make('Rédaction')->icon('heroicon-o-pencil')->schema([
                                        MarkdownEditor::make('body')
                                            ->hiddenLabel()
                                            ->hintAction(LibraryActions::markdown())
                                            ->helperText("Astuce : collez un lien YouTube seul sur une ligne pour intégrer une vidéo. Vous pouvez modifier la taille de l'image en ajoutant {width=200} à la fin d'un lien d'image.")
                                            ->fileAttachmentsDisk('public')
                                            ->fileAttachmentsDirectory('articles/contenu')
                                            ->saveUploadedFileAttachmentUsing(fn (TemporaryUploadedFile $file) => ImageUpload::save($file, 'articles/contenu'))
                                            ->minHeight('24rem')
                                            ->live(onBlur: true),
                                    ]),
                                    Tab::make('Aperçu')->icon('heroicon-o-eye')->schema([
                                        View::make('filament.markdown-preview')
                                            ->viewData(fn (Get $get) => ['html' => Markdown::render($get('body'))]),
                                    ]),
                                ]),

                            CodeEditor::make('body')
                                ->label('Code HTML')
                                ->language(Language::Html)
                                ->visible(fn (Get $get) => self::isHtml($get))
                                ->helperText('Article mis en page (généré par IA ou via l\'API). Le HTML est affiché tel quel avec les styles du site.'),
                        ]),

                        Section::make('Galerie photos')
                            ->description('Photos affichées en carrousel à la fin de l\'article.')
                            ->collapsible()
                            ->schema([
                                Repeater::make('gallery')
                                    ->hiddenLabel()
                                    ->hintAction(LibraryActions::gallery())
                                    ->schema([
                                        ImageUpload::make('path', 'articles/galerie')->label('Photo')->required(),
                                        TextInput::make('alt')
                                            ->label('Description de la photo')
                                            ->helperText('Lue par les personnes malvoyantes et affichée en légende.')
                                            ->required()
                                            ->maxLength(190),
                                    ])
                                    ->grid(2)
                                    ->reorderableWithDragAndDrop()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state) => $state['alt'] ?? null)
                                    ->addActionLabel('Ajouter une photo')
                                    ->defaultItems(0),
                            ]),
                    ]),

                Grid::make(1)
                    ->columnSpan(['default' => 3, 'lg' => 1])
                    ->schema([
                        Section::make('Publication')->schema([
                            // Statut : interrupteur Brouillon (orange) / Publié (vert), avec à sa droite le lien vers
                            // la page. Champ virtuel converti en ArticleStatus à l'enregistrement (ArticleForm::applyStatus).
                            Flex::make([
                            Toggle::make('is_published')
                                ->label(fn (Get $get) => $get('is_published') ? 'Publié' : 'Brouillon')
                                ->helperText(fn (Get $get) => $get('is_published')
                                    ? 'Visible sur le site (à la date de publication).'
                                    : 'Non visible sur le site : seul le lien de prévisualisation permet de le lire.')
                                ->onColor('success')
                                ->offColor('warning')
                                ->onIcon('heroicon-m-globe-alt')
                                ->offIcon('heroicon-m-pencil')
                                ->inline()
                                ->live()
                                ->default(false)
                                ->afterStateHydrated(fn (Toggle $component, ?Article $record) => $component->state($record?->status === ArticleStatus::Published)),
                            Actions::make([
                                Action::make('view')
                                    ->label(fn (Article $record) => $record->isPubliclyVisible() ? 'Voir sur le site' : 'Prévisualiser')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->color('gray')
                                    ->size('sm')
                                    ->url(fn (Article $record) => $record->isPubliclyVisible() ? $record->url() : $record->previewUrl())
                                    ->openUrlInNewTab(),
                            ])->grow(false)->visibleOn('edit'),
                            ])->verticallyAlignStart(),
                            DateTimePicker::make('published_at')
                                ->label('Date de publication')
                                ->helperText('Remplie automatiquement à la publication. Une date future programme l\'article.')
                                ->seconds(false)
                                ->native(false)
                                ->displayFormat('d/m/Y H:i'),
                            Select::make('category')
                                ->label('Catégorie')
                                ->options(Category::class)
                                ->required()
                                ->native(false),
                            Toggle::make('is_pinned')
                                ->label('Épingler en tête de liste'),
                            TextInput::make('slug')
                                ->label('Adresse (URL)')
                                ->prefix('/le-projet/…/')
                                ->helperText('Laisser vide pour la créer à partir du titre.')
                                ->required(fn (string $operation) => $operation === 'edit')
                                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                                ->validationMessages(['regex' => 'Uniquement des minuscules sans accents, des chiffres et des tirets.'])
                                ->unique(ignoreRecord: true)
                                ->maxLength(190),
                            TextInput::make('preview_link')
                                ->label('Lien de prévisualisation')
                                ->helperText('À partager pour faire relire un brouillon. Il n\'est pas référencé par les moteurs de recherche.')
                                ->formatStateUsing(fn (?Article $record) => $record?->previewUrl())
                                ->readOnly()
                                ->copyable(copyMessage: 'Lien copié')
                                ->dehydrated(false)
                                ->visibleOn('edit'),
                        ]),

                        Section::make('Image de partage')
                            ->description('Image principale de l\'article, utilisée aussi sur Facebook, WhatsApp, LinkedIn… Format paysage conseillé (1200 × 630).')
                            ->schema([
                                ImageUpload::make('og_image', 'articles')->hiddenLabel()
                                    ->hintAction(LibraryActions::cover()),
                                TextInput::make('og_image_alt')
                                    ->label('Description de l\'image')
                                    ->requiredWith('og_image')
                                    ->maxLength(190),
                                Toggle::make('show_cover')
                                    ->label('Afficher l\'image en haut de l\'article')
                                    ->helperText('Si désactivé, l\'image sert seulement aux partages et aux cartes de la liste des articles.')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    /** Données du formulaire → statut de l'article (interrupteur is_published). */
    public static function applyStatus(array $data): array
    {
        $data['status'] = ! empty($data['is_published']) ? ArticleStatus::Published : ArticleStatus::Draft;
        unset($data['is_published']);

        return $data;
    }

    private static function isHtml(Get $get): bool
    {
        $format = $get('format');

        return ($format instanceof ArticleFormat ? $format : ArticleFormat::tryFrom((string) $format)) === ArticleFormat::Html;
    }
}
