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

    public static function store(UploadedFile|string $file, string $directory, int $maxWidth = self::MAX_WIDTH): string
    {
        $source = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);

        $image = (new ImageManager(new Driver))->read($source);
        $image->scaleDown(width: $maxWidth);

        $path = trim($directory, '/').'/'.Str::slug(pathinfo($name, PATHINFO_FILENAME)).'-'.Str::random(6).'.webp';
        Storage::disk('public')->put($path, (string) $image->toWebp(quality: 82));

        return $path;
    }

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://', '/']) ? $path : Storage::disk('public')->url($path);
    }
}
