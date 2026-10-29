<?php

namespace App\Filament\Resources\LibraryImages\Pages;

use App\Filament\Resources\LibraryImages\LibraryImageResource;
use App\Models\LibraryImage;
use App\Support\Library;
use App\Support\LibraryResult;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Components\Attributes\Exposed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Url;

/**
 * Grille de la bibliothèque : défilement infini, recherche (y compris par titre des articles qui utilisent
 * l'image), tris, et glisser-déposer de fichiers directement sur la grille.
 */
class ListLibraryImages extends Page
{
    protected static string $resource = LibraryImageResource::class;

    protected static ?string $title = 'Bibliothèque d\'images';

    protected string $view = 'filament.library.index';

    private const PER_PAGE = 24;

    public const SORTS = [
        'added' => 'Ajout le plus récent',
        'used' => 'Utilisation la plus récente',
        'count' => 'Les plus utilisées',
    ];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'tri')]
    public string $sort = 'added';

    public int $limit = self::PER_PAGE;

    /** Fichiers glissés sur la grille (envoyés par Livewire). */
    public array $dropped = [];

    /** Identifiants des images sélectionnées (synchronisés avec Alpine). */
    public array $selected = [];

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getSubheading(): ?string
    {
        return 'Les photos sources du site. Une image déjà présente (même dans un autre format) n\'est pas ajoutée deux fois ; '
            .'si la nouvelle est de meilleure qualité, elle remplace l\'ancienne. Les pages utilisent leur propre copie des images.';
    }

    public function getViewData(): array
    {
        $images = $this->query()->limit($this->limit + 1)->get();

        return [
            'images' => $images->take($this->limit),
            'hasMore' => $images->count() > $this->limit,
            'total' => $this->query()->count(),
            'sorts' => self::SORTS,
        ];
    }

    #[Exposed]
    public function loadMore(): void
    {
        $this->limit += self::PER_PAGE;
    }

    public function updatedSearch(): void
    {
        $this->limit = self::PER_PAGE;
    }

    public function updatedSort(): void
    {
        $this->limit = self::PER_PAGE;
    }

    /** Appelé quand les fichiers glissés sur la grille ont fini d'être envoyés. */
    public function updatedDropped(): void
    {
        $this->validate(['dropped.*' => ['file', 'max:30720', 'extensions:'.implode(',', [...Library::IMAGE_EXTENSIONS, 'docx'])]], [
            'dropped.*.extensions' => 'Formats acceptés : JPG, PNG, WebP, GIF ou Word (.docx).',
        ]);

        $this->import($this->dropped);
        $this->dropped = [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Ajouter des images')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('Ajouter des images à la bibliothèque')
                ->modalDescription('Astuce : vous pouvez aussi glisser vos fichiers directement sur la grille des images.')
                ->modalSubmitActionLabel('Ajouter')
                ->schema([
                    FileUpload::make('files')
                        ->label('Photos ou document Word')
                        ->helperText('JPG, PNG, WebP ou GIF, ou un document Word (.docx) : ses images sont extraites. 30 Mo maximum par fichier.')
                        ->multiple()
                        ->storeFiles(false)
                        ->maxSize(30 * 1024)
                        ->maxParallelUploads(3)
                        ->acceptedFileTypes([
                            'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->required(),
                ])
                ->action(fn (array $data) => $this->import($data['files'])),
        ];
    }

    /** Suppression groupée : mêmes règles que pour une image seule (les images utilisées par une page en ligne sont ignorées). */
    public function deleteSelectedAction(): Action
    {
        return Action::make('deleteSelected')
            ->label('Supprimer')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading(fn () => 'Supprimer '.count($this->selected).' image(s) ?')
            ->modalDescription(function () {
                $images = $this->selectedImages();
                $blocked = $images->filter(fn (LibraryImage $i) => $i->usages->contains->isPublic());
                $drafts = $images->diff($blocked)->flatMap(fn (LibraryImage $i) => $i->usages->map->pageTitle())->unique();

                return collect([
                    $blocked->isNotEmpty() ? $blocked->count().' image(s) utilisée(s) par une page en ligne seront conservées : '.$blocked->pluck('title')->join(', ').'.' : null,
                    $drafts->isNotEmpty() ? 'Les autres seront retirées de ces brouillons : '.$drafts->join(', ').'.' : null,
                ])->filter()->join(' ') ?: 'Les images seront retirées de la bibliothèque.';
            })
            ->modalSubmitActionLabel('Supprimer')
            ->action(function () {
                $deleted = 0;
                $kept = [];
                foreach ($this->selectedImages() as $image) {
                    try {
                        Library::delete($image);
                        $deleted++;
                    } catch (\RuntimeException) {
                        $kept[] = $image->title;
                    }
                }
                $this->selected = [];

                Notification::make()
                    ->title($deleted.' image(s) supprimée(s)')
                    ->body($kept ? 'Conservées car utilisées par une page en ligne : '.implode(', ', $kept) : null)
                    ->{$kept ? 'warning' : 'success'}()
                    ->send();
            });
    }

    private function selectedImages()
    {
        return LibraryImage::whereIn('id', $this->selected)->with('usages.article')->get();
    }

    /** @param  array<int, UploadedFile>  $files */
    private function import(array $files): void
    {
        $results = [];
        $errors = [];
        foreach ($files as $file) {
            try {
                array_push($results, ...Library::addUpload($file));
            } catch (\InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        $this->sort = 'added';
        $this->limit = self::PER_PAGE;

        Notification::make()
            ->title('Images : '.LibraryResult::summary($results))
            ->body($errors ? implode("\n", $errors) : null)
            ->{$errors ? 'warning' : 'success'}()
            ->persistent((bool) $errors)
            ->send();
    }

    private function query(): Builder
    {
        $query = LibraryImage::query()
            ->search($this->search)
            ->withCount('usages')
            ->withMax('usages', 'created_at');

        return match ($this->sort) {
            'used' => $query->orderByRaw('usages_max_created_at IS NULL')->orderByDesc('usages_max_created_at')->orderByDesc('id'),
            'count' => $query->orderByDesc('usages_count')->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };
    }
}
