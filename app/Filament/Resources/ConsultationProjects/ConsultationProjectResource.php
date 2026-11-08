<?php

namespace App\Filament\Resources\ConsultationProjects;

use App\Filament\Resources\ConsultationProjects\Pages\CreateConsultationProject;
use App\Filament\Resources\ConsultationProjects\Pages\EditConsultationProject;
use App\Filament\Resources\ConsultationProjects\Pages\ListConsultationProjects;
use App\Models\ConsultationProject;
use App\Support\Images;
use App\Support\Library;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/** Projets finalistes soumis à la consultation (photo de la maquette, architecte). */
class ConsultationProjectResource extends Resource
{
    protected static ?string $model = ConsultationProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $modelLabel = 'projet';

    protected static ?string $pluralModelLabel = 'projets de la consultation';

    protected static ?string $navigationLabel = 'Admin de la consultation';

    protected static ?string $breadcrumb = 'Admin de la consultation';

    protected static string|\UnitEnum|null $navigationGroup = 'Consultation';

    protected static ?string $slug = 'consultation/projets';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label('Nom du projet')->required()->maxLength(190)
                    ->helperText('Titre affiché en grand sur la page du projet.'),
                TextInput::make('position')->label('Numéro')->numeric()->minValue(1)->required()
                    ->helperText('Ordre d\'affichage : idéalement celui des maquettes dans l\'exposition.'),
                self::photo('model_photo', 1920)->label('Photo de la maquette')->columnSpanFull()
                    ->helperText('Affichée en haut de la page, pour que chacun vote sur le bon projet.'),
            ]),
            Section::make('Architecte')->columns(2)->schema([
                TextInput::make('architect')->label('Nom de l\'architecte')->required()->maxLength(190),
                TextInput::make('agency')->label('Agence')->maxLength(190),
                TextInput::make('architect_title')->label('Titre')->maxLength(190),
                TextInput::make('city')->label('Ville(s)')->maxLength(190),
                self::photo('architect_photo', 800)->label('Portrait'),
                Toggle::make('is_active')->label('Proposé au vote')->default(true)->inline(false),
            ]),
        ]);
    }

    /** L'original rejoint la bibliothèque d'images ; la page utilise une copie WebP. */
    private static function photo(string $name, int $maxWidth): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->disk('public')
            ->directory('consultation')
            ->visibility('public')
            ->maxSize(20 * 1024)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) use ($maxWidth) {
                Library::addQuietly($file->getRealPath(), $file->getClientOriginalName());

                return Images::store($file, 'consultation', $maxWidth);
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->recordUrl(fn (ConsultationProject $record) => static::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('position')->label('N°'),
                ImageColumn::make('model_photo')->label('Maquette')->disk('public')->imageHeight(56),
                ImageColumn::make('architect_photo')->label('Portrait')->disk('public')->imageHeight(56),
                TextColumn::make('name')->label('Projet')->weight('bold')->description(fn (ConsultationProject $r) => $r->architect),
                TextColumn::make('votes_count')->label('Avis')->counts(['votes' => fn ($q) => $q->whereNotNull('overall')]),
                IconColumn::make('is_active')->label('Au vote')->boolean(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Voir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (ConsultationProject $record) => route('consultation.project', $record), shouldOpenInNewTab: true),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsultationProjects::route('/'),
            'create' => CreateConsultationProject::route('/nouveau'),
            'edit' => EditConsultationProject::route('/{record}/modifier'),
        ];
    }
}
