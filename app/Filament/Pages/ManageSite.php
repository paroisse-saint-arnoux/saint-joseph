<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ManageSite extends SettingsPage
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Réglages du site';

    protected static ?string $title = 'Réglages du site';

    protected static ?string $slug = 'reglages';

    protected static ?int $navigationSort = 3;

    protected array $settingKeys = ['site', 'legal'];

    protected function formComponents(): array
    {
        return [
            Tabs::make()->persistTabInQueryString()->tabs([
                Tab::make('Général')->icon(Heroicon::OutlinedGlobeAlt)->schema([
                    Textarea::make('site.description')
                        ->label('Description pour les moteurs de recherche')
                        ->helperText('Utilisée par Google et lors des partages des pages sans description propre.')
                        ->rows(3)->maxLength(300),
                    Textarea::make('site.about')->label('Présentation (pied de page)')->rows(2),
                    TextInput::make('site.address')->label('Adresse'),
                    TextInput::make('site.don_url')->label('Lien de la page de don')->url()->required(),
                ]),

                Tab::make('Réseaux & liens')->icon(Heroicon::OutlinedLink)->schema([
                    TextInput::make('site.facebook_url')->label('Page Facebook')->url(),
                    TextInput::make('site.linkedin_url')->label('Page LinkedIn')->url(),
                    Repeater::make('site.useful_links')
                        ->label('Liens utiles (pied de page)')
                        ->schema([
                            TextInput::make('label')->label('Libellé')->required(),
                            TextInput::make('url')->label('Adresse')->url()->required(),
                        ])
                        ->columns(2)
                        ->reorderableWithDragAndDrop()
                        ->addActionLabel('Ajouter un lien'),
                ]),

                Tab::make('Newsletter & don')->icon(Heroicon::OutlinedEnvelope)->schema([
                    TextInput::make('site.newsletter_title')->label('Titre du bloc newsletter'),
                    Textarea::make('site.newsletter_text')->label('Texte du bloc newsletter')->rows(2),
                    TextInput::make('site.don_title')->label('Titre du bloc don'),
                    Textarea::make('site.don_text')->label('Texte du bloc don')->rows(2),
                ]),

                Tab::make('Pages légales')->icon(Heroicon::OutlinedScale)->schema([
                    MarkdownEditor::make('legal.mentions')->label('Mentions légales'),
                    MarkdownEditor::make('legal.privacy')->label('Politique de confidentialité'),
                ]),
            ])->columnSpanFull(),
        ];
    }
}
