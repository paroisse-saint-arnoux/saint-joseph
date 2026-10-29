<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\LibraryImage;

trait PresentsLibraryImages
{
    protected function presentLibraryImage(LibraryImage $image): array
    {
        return array_filter([
            'id' => $image->id,
            'title' => $image->title,
            'alt' => $image->alt,
            'original_name' => $image->original_name,
            'dimensions' => $image->dimensions(),
            'used_in_pages' => $image->usages_count ?? $image->usages()->count(),
            'added_at' => $image->created_at?->toAtomString(),
        ], fn ($value) => $value !== null);
    }

    protected function libraryAdminUrl(): string
    {
        return url('/admin/bibliotheque');
    }
}
