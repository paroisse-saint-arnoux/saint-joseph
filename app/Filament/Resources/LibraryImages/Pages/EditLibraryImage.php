<?php

namespace App\Filament\Resources\LibraryImages\Pages;

use App\Filament\Resources\LibraryImages\LibraryImageResource;
use App\Models\LibraryImage;
use App\Models\LibraryUsage;
use App\Support\Library;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fiche d'une image : titre et description, informations, téléchargement de l'original,
 * nouvelle version (met à jour toutes les pages), suppression, et pages qui l'utilisent (relation « usages »).
 *
 * @property LibraryImage $record
 */
class EditLibraryImage extends EditRecord
{
    protected static string $resource = LibraryImageResource::class;

    public function getTitle(): string
    {
        return $this->record->title;
    }

    public function getBreadcrumb(): string
    {
        return 'Fiche';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Télécharger l\'original')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => Storage::disk(LibraryImage::SOURCE_DISK)->temporaryUrl($this->record->source_path, now()->addMinutes(10)))
                ->extraAttributes(fn () => ['download' => Str::slug($this->record->title).'.'.pathinfo($this->record->source_path, PATHINFO_EXTENSION)]),

            Action::make('crop')
                ->label('Recadrer / pivoter')
                ->icon('heroicon-o-scissors')
                ->color('gray')
                ->modalHeading('Recadrer ou pivoter l\'image')
                ->modalDescription('Le résultat devient une nouvelle version de l\'image : les pages qui l\'utilisent passent en « Ancienne version » '
                    .'et vous choisissez ensuite où la remplacer. La retouche peut être annulée.')
                ->modalWidth('7xl')
                ->modalSubmitActionLabel('Appliquer')
                ->schema([
                    ViewField::make('edit')
                        ->hiddenLabel()
                        ->view('filament.library.cropper-field')
                        ->viewData(fn () => ['url' => Storage::disk(LibraryImage::SOURCE_DISK)->temporaryUrl($this->record->source_path, now()->addHour())]),
                ])
                ->action(function (array $data) {
                    try {
                        Library::edit($this->record, (array) ($data['edit'] ?? []));
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Retouche impossible')->body($e->getMessage())->danger()->send();

                        return;
                    }
                    $pages = $this->record->usages()->get()->unique(fn (LibraryUsage $u) => $u->page_type.$u->article_id)->count();
                    Notification::make()->title('Image retouchée')
                        ->body($pages ? "{$pages} page(s) utilisent encore l'ancienne version : cochez-les dans la liste du bas puis « Remplacer »." : null)
                        ->success()->persistent((bool) $pages)->send();
                    $this->redirect(static::getUrl(['record' => $this->record]));
                }),

            Action::make('newVersion')
                ->label('Nouvelle version')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->modalHeading('Charger une nouvelle version de l\'image')
                ->modalDescription('La nouvelle image remplace l\'original ; le titre et la description sont conservés. '
                    .'Vous choisirez ensuite, dans la liste du bas, les pages où la remplacer.')
                ->modalSubmitActionLabel('Remplacer')
                ->schema([
                    FileUpload::make('file')->label('Image')->storeFiles(false)->required()
                        ->maxSize(30 * 1024)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp']),
                ])
                ->action(function (array $data) {
                    try {
                        $count = Library::replaceSource($this->record, $data['file']->getRealPath(), $data['file']->getClientOriginalName());
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Image non remplacée')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()->title('Nouvelle version enregistrée')
                        ->body($count ? "{$count} page(s) utilisent encore l'ancienne version : cochez-les dans la liste du bas puis « Remplacer »." : null)
                        ->success()->persistent((bool) $count)->send();
                    $this->redirect(static::getUrl(['record' => $this->record]));
                }),

            ActionGroup::make([
                Action::make('undoEdit')
                    ->label('Annuler la dernière retouche')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn () => filled($this->record->previous_source_path))
                    ->requiresConfirmation()
                    ->modalDescription('L\'image revient à son état avant le dernier recadrage ou la dernière rotation.')
                    ->action(function () {
                        Library::undoEdit($this->record);
                        Notification::make()->title('Retouche annulée')->success()->send();
                        $this->redirect(static::getUrl(['record' => $this->record]));
                    }),

                Action::make('refreshAll')
                    ->label('Mettre à jour toutes les pages')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->visible(fn () => $this->outdatedUsages()->isNotEmpty())
                    ->requiresConfirmation()
                    ->modalDescription('Les pages qui affichent une ancienne version de cette image utiliseront la version actuelle, à leur taille actuelle.')
                    ->action(function () {
                        $usages = $this->outdatedUsages();
                        $usages->each(fn (LibraryUsage $usage) => Library::replaceInPage($usage));
                        Notification::make()->title('Image mise à jour dans '.$usages->count().' page(s)')->success()->send();
                        $this->redirect(static::getUrl(['record' => $this->record]));
                    }),

                Action::make('delete')
                    ->label('Supprimer')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->disabled(fn () => $this->publicUsages()->isNotEmpty())
                    ->tooltip(fn () => $this->publicUsages()->isNotEmpty()
                        ? 'Utilisée par une page en ligne : '.$this->publicUsages()->map->pageTitle()->unique()->join(', ')
                        : null)
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer cette image ?')
                    ->modalDescription(function () {
                        $drafts = $this->record->usages()->with('article')->get()->map->pageTitle()->unique();

                        return $drafts->isEmpty()
                            ? 'L\'image sera retirée de la bibliothèque.'
                            : 'Elle sera aussi retirée de ces brouillons : '.$drafts->join(', ').'.';
                    })
                    ->action(function () {
                        $drafts = Library::delete($this->record);
                        Notification::make()->title('Image supprimée')
                            ->body($drafts ? 'Retirée de : '.implode(', ', $drafts) : null)->success()->send();
                        $this->redirect(LibraryImageResource::getUrl());
                    }),
            ]),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    private function outdatedUsages()
    {
        return $this->record->usages()->with(['copy.image', 'article'])->get()->filter(fn (LibraryUsage $usage) => $usage->copy->isOutdated());
    }

    private function publicUsages()
    {
        return $this->record->usages()->with('article')->get()->filter(fn (LibraryUsage $usage) => $usage->isPublic());
    }
}
