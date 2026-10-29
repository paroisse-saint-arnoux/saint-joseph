<?php

namespace App\Http\Controllers;

use App\Models\LibraryImage;
use Illuminate\Http\Request;

/** Recherche dans la bibliothèque pour le sélecteur d'images de l'admin (formulaire d'article). */
class LibrarySearchController extends Controller
{
    private const PER_PAGE = 24;

    public function __invoke(Request $request)
    {
        $page = $request->integer('page', 1);
        $images = LibraryImage::query()
            ->search($request->string('q')->toString())
            ->latest('id')
            ->paginate(self::PER_PAGE, page: max(1, $page));

        return response()->json([
            'items' => $images->getCollection()->map(fn (LibraryImage $image) => [
                'id' => $image->id,
                'title' => $image->title,
                'alt' => $image->alt,
                'thumb' => $image->thumbUrl(),
                'dimensions' => $image->dimensions(),
            ]),
            'hasMore' => $images->hasMorePages(),
        ]);
    }
}
