<?php

namespace App\Filament\Pages;

use App\Filament\Forms\ImageUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ManageHome extends SettingsPage
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Page d\'accueil';

    protected static ?string $title = 'Page d\'accueil';

    protected static ?string $slug = 'page-accueil';

    protected static ?int $navigationSort = 2;

    protected array $settingKeys = ['home'];

    protected function formComponents(): array
    {
        $emphasis = 'Mettez un mot entre *astérisques* pour l\'écrire en italique doré, entre **doubles astérisques** pour le mettre en gras.';

        return [
            Tabs::make()->persistTabInQueryString()->tabs([
                Tab::make('Bandeau')->icon(Heroicon::OutlinedPhoto)->schema([
                    Textarea::make('home.hero.title')->label('Grand titre')->rows(2)->required()->helperText($emphasis),
                    Repeater::make('home.hero.facts')
                        ->label('Chiffres sous le titre')
                        ->simple(TextInput::make('text')->required())
                        ->helperText($emphasis)
                        ->reorderableWithDragAndDrop()
                        ->addActionLabel('Ajouter un chiffre'),
                    ImageUpload::make('home.hero.image', 'accueil')->label('Photo de fond'),
                ]),

                Tab::make('Découvrir')->icon(Heroicon::OutlinedSparkles)->schema([
                    TextInput::make('home.decouvrir.eyebrow')->label('Surtitre'),
                    TextInput::make('home.decouvrir.title')->label('Titre')->required(),
                    MarkdownEditor::make('home.decouvrir.text')
                        ->label('Texte de présentation')
                        ->toolbarButtons([['bold', 'italic', 'link'], ['bulletList'], ['undo', 'redo']]),
                    Grid::make(2)->schema([
                        ImageUpload::make('home.decouvrir.image', 'accueil')->label('Illustration (facultative)'),
                        TextInput::make('home.decouvrir.image_alt')->label('Description de l\'illustration')->requiredWith('home.decouvrir.image'),
                    ]),
                ]),

                Tab::make('Le projet')->icon(Heroicon::OutlinedBuildingLibrary)->schema([
                    TextInput::make('home.projet.eyebrow')->label('Surtitre'),
                    TextInput::make('home.projet.title')->label('Titre')->required(),
                    Textarea::make('home.projet.intro')->label('Introduction')->rows(2),
                    Repeater::make('home.projet.stats')
                        ->label('Chiffres clés')
                        ->schema([
                            TextInput::make('value')->label('Valeur')->required()->helperText('Un nombre seul est animé (compteur).'),
                            TextInput::make('unit')->label('Unité'),
                            TextInput::make('label')->label('Libellé')->required(),
                        ])
                        ->columns(3)
                        ->reorderableWithDragAndDrop()
                        ->addActionLabel('Ajouter un chiffre clé'),
                    Repeater::make('home.projet.buildings')
                        ->label('Cahier des charges (bâtiments)')
                        ->schema([
                            TextInput::make('title')->label('Bâtiment')->required(),
                            TextInput::make('badge')->label('Pastille (ex. capacité)'),
                            Textarea::make('items')->label('Contenu')->rows(6)->helperText('Un élément par ligne.')->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['title'] ?? null)
                        ->reorderableWithDragAndDrop()
                        ->addActionLabel('Ajouter un bâtiment'),
                    Repeater::make('home.projet.images')
                        ->label('Visuels (esquisses de l\'architecte…)')
                        ->schema([
                            ImageUpload::make('path', 'accueil')->label('Image')->required(),
                            TextInput::make('alt')->label('Description de l\'image')->required(),
                            TextInput::make('caption')->label('Légende'),
                        ])
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['alt'] ?? null)
                        ->reorderableWithDragAndDrop()
                        ->addActionLabel('Ajouter un visuel'),
                ]),

                Tab::make('Avancement')->icon(Heroicon::OutlinedFlag)->schema([
                    TextInput::make('home.avancement.eyebrow')->label('Surtitre'),
                    TextInput::make('home.avancement.title')->label('Titre')->required(),
                    Textarea::make('home.avancement.text')->label('Texte')->rows(3),
                    Repeater::make('home.avancement.steps')
                        ->label('Étapes de la frise')
                        ->schema([
                            TextInput::make('date')->label('Date')->required()->placeholder('2026 ou 2026–2027'),
                            TextInput::make('title')->label('Étape')->required(),
                            Select::make('status')->label('Statut')->required()->native(false)->default('todo')
                                ->options(['done' => 'Réalisé', 'now' => 'En cours', 'todo' => 'À venir']),
                            Textarea::make('description')->label('Description')->rows(2)->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->collapsible()
                        ->itemLabel(fn (array $state) => trim(($state['date'] ?? '').' · '.($state['title'] ?? ''), ' ·'))
                        ->reorderableWithDragAndDrop()
                        ->addActionLabel('Ajouter une étape'),
                ]),

                Tab::make('Collecte')->icon(Heroicon::OutlinedCurrencyEuro)->schema([
                    Grid::make(2)->schema([
                        TextInput::make('home.collecte.raised')
                            ->label('Montant collecté (€)')
                            ->numeric()->minValue(0)->required()
                            ->helperText('Le clocher, les pierres et les murs se recalculent automatiquement (10 € = 1 pierre).'),
                        TextInput::make('home.collecte.goal')->label('Objectif (€)')->numeric()->minValue(1)->required(),
                    ]),
                    TextInput::make('home.collecte.eyebrow')->label('Surtitre'),
                    TextInput::make('home.collecte.title')->label('Titre')->required(),
                    Textarea::make('home.collecte.text')->label('Texte')->rows(2),
                    TextInput::make('home.collecte.goal_label')->label('Objectif en mots')->placeholder('les cloches en 2030'),
                    Textarea::make('home.collecte.quote')->label('Citation')->rows(2),
                ]),
            ])->columnSpanFull(),
        ];
    }
}
