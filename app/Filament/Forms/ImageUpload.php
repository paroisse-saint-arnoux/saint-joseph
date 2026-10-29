<?php

namespace App\Filament\Forms;

use App\Support\Images;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Champ d'envoi d'image : l'image est redimensionnée et convertie en WebP
 * côté serveur (voir App\Support\Images).
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
            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => Images::store($file, $directory, $maxWidth));
    }
}
