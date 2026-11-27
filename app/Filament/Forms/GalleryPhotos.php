<?php

namespace App\Filament\Forms;

use App\Models\LibraryCopy;
use App\Models\LibraryImage;
use App\Support\Images;
use App\Support\Library;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Ajout de photos à la galerie d'un article (état du Repeater « gallery » : uuid => [path => [uuid => chemin], alt]).
 * Une photo déjà présente dans la galerie n'est pas ajoutée une deuxième fois : on compare les images de la
 * bibliothèque, qui reconnaît le même fichier comme la même photo dans un autre format ou une autre taille.
 * La légende reprend la description de l'image dans la bibliothèque si elle en a une, jamais le nom du fichier.
 */
class GalleryPhotos
{
    public const DIRECTORY = 'articles/galerie';

    /**
     * @param  array<string, array<string, mixed>>  $items
     * @param  iterable<UploadedFile>  $files
     * @return array{0: array<string, array<string, mixed>>, 1: int} galerie complétée, nombre de doublons écartés
     */
    public static function addUploads(array $items, iterable $files): array
    {
        $seen = self::imageIds($items);
        $skipped = 0;

        foreach ($files as $file) {
            $image = Library::addQuietly($file->getRealPath(), $file->getClientOriginalName())?->image;
            if ($image && in_array($image->id, $seen, true)) {
                $skipped++;

                continue;
            }

            $path = Images::store($file, self::DIRECTORY);
            if ($image) {
                Library::registerCopy($image, $path, Images::MAX_WIDTH, hash_file('sha256', $file->getRealPath()));
                $seen[] = $image->id;
            }
            $items[(string) Str::uuid()] = self::item($path, $image?->alt);
        }

        return [$items, $skipped];
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     * @param  iterable<LibraryImage>  $images
     * @return array{0: array<string, array<string, mixed>>, 1: int} galerie complétée, nombre de doublons écartés
     */
    public static function addLibraryImages(array $items, iterable $images): array
    {
        $seen = self::imageIds($items);
        $skipped = 0;

        foreach ($images as $image) {
            if (in_array($image->id, $seen, true)) {
                $skipped++;

                continue;
            }
            $seen[] = $image->id;
            $items[(string) Str::uuid()] = self::item(Library::copyForArticle($image, self::DIRECTORY), $image->alt);
        }

        return [$items, $skipped];
    }

    public static function skippedMessage(int $skipped): string
    {
        return $skipped === 1
            ? '1 photo était déjà dans la galerie : elle n\'a pas été ajoutée une deuxième fois.'
            : "{$skipped} photos étaient déjà dans la galerie : elles n'ont pas été ajoutées une deuxième fois.";
    }

    /** @return array<string, mixed> */
    private static function item(string $path, ?string $alt): array
    {
        return ['path' => [(string) Str::uuid() => $path], 'alt' => $alt];
    }

    /**
     * Images de la bibliothèque déjà présentes dans la galerie (d'après les copies enregistrées).
     *
     * @param  array<string, array<string, mixed>>  $items
     * @return list<int>
     */
    private static function imageIds(array $items): array
    {
        $paths = collect($items)
            ->flatMap(fn ($item) => (array) ($item['path'] ?? []))
            ->filter(fn ($path) => is_string($path))
            ->values();

        return $paths->isEmpty() ? [] : LibraryCopy::whereIn('path', $paths)->pluck('library_image_id')->unique()->values()->all();
    }
}
