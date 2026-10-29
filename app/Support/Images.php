<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Toutes les images envoyées sont redimensionnées et converties en WebP
 * avant d'être stockées sur le disque public.
 */
class Images
{
    public const MAX_WIDTH = 1920;

    public static function store(UploadedFile|string $file, string $directory, int $maxWidth = self::MAX_WIDTH, ?string $name = null): string
    {
        $source = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $name ??= $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);

        $path = trim($directory, '/').'/'.(Str::slug(pathinfo($name, PATHINFO_FILENAME)) ?: 'image').'-'.Str::random(6).'.webp';
        self::write($source, $path, $maxWidth);

        return $path;
    }

    /** Écrit (ou réécrit) à $path la version retraitée de l'image source. */
    public static function write(string $source, string $path, int $maxWidth = self::MAX_WIDTH): void
    {
        $image = (new ImageManager(new Driver))->read($source)->scaleDown(width: $maxWidth);
        Storage::disk('public')->put($path, (string) $image->toWebp(quality: 82));
    }

    /** Vignette JPEG (pour montrer les images à Claude sans lui envoyer les fichiers en pleine taille). */
    public static function thumbnail(string $path, int $width = 400): string
    {
        $image = (new ImageManager(new Driver))->read(Storage::disk('public')->path($path));

        return (string) $image->scaleDown(width: $width)->toJpeg(quality: 70);
    }

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://', '/']) ? $path : Storage::disk('public')->url($path);
    }
}
