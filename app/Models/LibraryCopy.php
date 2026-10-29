<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Copie retraitée d'une image de la bibliothèque, utilisée par une ou plusieurs pages. */
#[Fillable(['library_image_id', 'path', 'max_width', 'source_sha256'])]
class LibraryCopy extends Model
{
    /** @return BelongsTo<LibraryImage, $this> */
    public function image(): BelongsTo
    {
        return $this->belongsTo(LibraryImage::class, 'library_image_id');
    }

    /** @return HasMany<LibraryUsage, $this> */
    public function usages(): HasMany
    {
        return $this->hasMany(LibraryUsage::class);
    }

    /** La copie a été faite à partir d'une version antérieure de la source. */
    public function isOutdated(): bool
    {
        return $this->source_sha256 !== $this->image->sha256;
    }
}
