<?php

namespace App\Filament\Forms;

use App\Support\Images;
use App\Support\Library;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Champ d'envoi d'image : l'original rejoint la bibliothèque d'images (dédoublonnée),
 * et la page utilise une copie redimensionnée et convertie en WebP (voir App\Support\Images).
 */
class ImageUpload
{
    public static function make(string $name, string $directory, int $maxWidth = Images::MAX_WIDTH): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->maxSize(20 * 1024)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => self::save($file, $directory, $maxWidth));
    }

    public static function save(TemporaryUploadedFile $file, string $directory, int $maxWidth = Images::MAX_WIDTH): string
    {
        $result = Library::addQuietly($file->getRealPath(), $file->getClientOriginalName());
        $path = Images::store($file, $directory, $maxWidth);

        // La copie vient du fichier envoyé : si la bibliothèque a déjà une meilleure version, la page le signalera.
        if ($result) {
            Library::registerCopy($result->image, $path, $maxWidth, hash_file('sha256', $file->getRealPath()));
        }

        return $path;
    }
}
