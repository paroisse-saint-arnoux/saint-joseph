<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

/** Textes de la page /don (formulaire de don RGIVE intégré). Réglage `don`. */
class ManageDon extends SettingsPage
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $navigationLabel = 'Page de don';

    protected static ?string $title = 'Page de don';

    protected static ?string $slug = 'page-de-don';

    protected static string|\UnitEnum|null $navigationGroup = 'Contenu';

    protected static ?int $navigationSort = 3;

    protected array $settingKeys = ['don'];

    protected function formComponents(): array
    {
        $markdown = fn (string $name, string $label) => MarkdownEditor::make($name)->label($label)
            ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'undo', 'redo']);

        return [
            Tabs::make()->persistTabInQueryString()->tabs([
                Tab::make('En-tête')->icon(Heroicon::OutlinedHeart)->schema([
                    TextInput::make('don.hero.eyebrow')->label('Surtitre'),
                    TextInput::make('don.hero.title')->label('Titre')->required(),
                    Textarea::make('don.hero.lead')->label('Introduction')->rows(2),
                    Textarea::make('don.gauge_text')->label('Texte sous la jauge de la collecte')->rows(2),
                ]),

                Tab::make('Le projet')->icon(Heroicon::OutlinedBuildingLibrary)->schema([
                    Section::make('Pourquoi ce projet')->schema([
                        TextInput::make('don.why.eyebrow')->label('Surtitre'),
                        TextInput::make('don.why.title')->label('Titre'),
                        $markdown('don.why.text', 'Texte'),
                    ]),
                    Section::make('Le projet')->schema([
                        TextInput::make('don.projet.eyebrow')->label('Surtitre'),
                        TextInput::make('don.projet.title')->label('Titre'),
                        $markdown('don.projet.text', 'Texte'),
                    ]),
                    Section::make('Témoignage')->schema([
                        Textarea::make('don.testimony.quote')->label('Citation')->rows(3),
                        TextInput::make('don.testimony.author')->label('Auteur'),
                    ]),
                ]),

                Tab::make('Fiscalité')->icon(Heroicon::OutlinedReceiptPercent)->schema([
                    TextInput::make('don.fiscal.eyebrow')->label('Surtitre'),
                    TextInput::make('don.fiscal.title')->label('Titre'),
                    Grid::make(2)->schema([
                        $markdown('don.fiscal.individuals', 'Particuliers'),
                        $markdown('don.fiscal.companies', 'Entreprises'),
                    ]),
                    Textarea::make('don.fiscal.note')->label('Note')->rows(2),
                ]),

                Tab::make('Questions')->icon(Heroicon::OutlinedQuestionMarkCircle)->schema([
                    Repeater::make('don.faq')
                        ->label('Foire aux questions')
                        ->schema([
                            TextInput::make('question')->label('Question')->required(),
                            Textarea::make('answer')->label('Réponse')->rows(3)->required(),
                        ])
                        ->itemLabel(fn (array $state) => $state['question'] ?? null)
                        ->collapsible()
                        ->reorderableWithDragAndDrop()
                        ->addActionLabel('Ajouter une question'),
                ]),
            ])->columnSpanFull(),
        ];
    }
}
