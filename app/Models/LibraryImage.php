<?php

namespace App\Models;

use App\Support\Images;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Image source de la bibliothèque. L'original est privé (disque local : il peut contenir des données
 * EXIF, ex. position GPS) ; seule la vignette est publique. Voir App\Support\Library.
 */
#[Fillable([
    'title', 'alt', 'original_name', 'source_path', 'previous_source_path', 'thumb_path', 'mime', 'width', 'height', 'size',
    'sha256', 'dhash', 'phash', 'user_id',
])]
class LibraryImage extends Model
{
    public const SOURCE_DISK = 'local';

    protected static function booted(): void
    {
        static::deleted(function (LibraryImage $image) {
            Storage::disk(self::SOURCE_DISK)->delete(array_filter([$image->source_path, $image->previous_source_path]));
            Storage::disk('public')->delete($image->thumb_path);
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<LibraryCopy, $this> */
    public function copies(): HasMany
    {
        return $this->hasMany(LibraryCopy::class);
    }

    /** @return HasMany<LibraryUsage, $this> */
    public function usages(): HasMany
    {
        return $this->hasMany(LibraryUsage::class);
    }

    /**
     * Recherche : chaque mot doit se trouver dans le titre, la description, le nom du fichier d'origine
     * ou le titre d'un article qui utilise l'image.
     */
    public function scopeSearch(Builder $query, ?string $terms): void
    {
        foreach (preg_split('/\s+/', trim((string) $terms), -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $like = '%'.$term.'%';
            $query->where(fn (Builder $q) => $q
                ->where('title', 'like', $like)
                ->orWhere('alt', 'like', $like)
                ->orWhere('original_name', 'like', $like)
                ->orWhereHas('usages.article', fn (Builder $a) => $a->where('title', 'like', $like)));
        }
    }

    public function sourceAbsolutePath(): string
    {
        return Storage::disk(self::SOURCE_DISK)->path($this->source_path);
    }

    public function thumbUrl(): ?string
    {
        return Images::url($this->thumb_path);
    }

    public function pixels(): int
    {
        return $this->width * $this->height;
    }

    public function humanSize(): string
    {
        return $this->size >= 1048576
            ? number_format($this->size / 1048576, 1, ',', ' ').' Mo'
            : max(1, (int) round($this->size / 1024)).' Ko';
    }

    public function dimensions(): string
    {
        return $this->width.' × '.$this->height;
    }
}
