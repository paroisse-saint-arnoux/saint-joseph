<?php

namespace App\Http\Controllers;

use App\Models\LibraryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/** Téléchargement groupé des originaux sélectionnés dans la bibliothèque (archive ZIP). */
class LibraryArchiveController extends Controller
{
    private const MAX_IMAGES = 200;

    public function __invoke(Request $request)
    {
        $ids = array_slice(array_filter(array_map('intval', explode(',', (string) $request->query('ids')))), 0, self::MAX_IMAGES);
        $images = LibraryImage::whereIn('id', $ids)->get()->sortBy(fn (LibraryImage $image) => array_search($image->id, $ids));
        abort_if($images->isEmpty(), 404);

        $path = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);

        $names = [];
        foreach ($images as $image) {
            $source = Storage::disk(LibraryImage::SOURCE_DISK)->path($image->source_path);
            if (! is_file($source)) {
                continue;
            }
            // Nom lisible et unique dans l'archive : titre de l'image + extension de l'original.
            $base = Str::slug($image->title) ?: 'image-'.$image->id;
            $name = $base.'.'.pathinfo($image->source_path, PATHINFO_EXTENSION);
            for ($n = 2; isset($names[$name]); $n++) {
                $name = $base.'-'.$n.'.'.pathinfo($image->source_path, PATHINFO_EXTENSION);
            }
            $names[$name] = true;
            // Les photos sont déjà compressées : on les range sans recompresser (plus rapide).
            $zip->addFile($source, $name);
            $zip->setCompressionName($name, ZipArchive::CM_STORE);
        }
        $zip->close();

        return response()->download($path, 'images-saint-joseph-'.now()->format('Y-m-d').'.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }
}
