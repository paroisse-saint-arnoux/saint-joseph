<?php

namespace App\Filament\Resources\Articles\Pages\Concerns;

use App\Filament\Forms\GalleryPhotos;
use App\Support\Library;
use Filament\Notifications\Notification;

/**
 * Photos glissées (ou choisies) dans la zone de la galerie : Livewire envoie tous les fichiers d'un coup dans
 * $galleryDrop (vue filament.forms.gallery-drop), puis on les ajoute ensemble à la galerie.
 * On n'utilise pas un champ FileUpload de Filament : vider sa zone au fil des envois fait abandonner à FilePond
 * les photos encore en attente (4 photos ajoutées sur 21, constaté le 27/11/2026).
 */
trait DropsGalleryPhotos
{
    public array $galleryDrop = [];

    public function updatedGalleryDrop(): void
    {
        $this->validate(
            ['galleryDrop.*' => ['file', 'max:30720', 'extensions:'.implode(',', Library::IMAGE_EXTENSIONS)]],
            ['galleryDrop.*.extensions' => 'Formats acceptés : JPG, PNG, WebP ou GIF.', 'galleryDrop.*.max' => '30 Mo maximum par photo.'],
        );

        [$items, $skipped] = GalleryPhotos::addUploads($this->data['gallery'] ?? [], $this->galleryDrop);
        $this->data['gallery'] = $items;
        $this->galleryDrop = [];

        if ($skipped) {
            Notification::make()->title(GalleryPhotos::skippedMessage($skipped))->warning()->send();
        }
    }
}
