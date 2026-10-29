<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Images;
use App\Support\Library;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    /** Envoi d'une image : l'original rejoint la bibliothèque, l'article utilise une copie (redimensionnée + WebP). Renvoie le chemin à utiliser dans og_image / gallery, et l'URL publique. */
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'image', 'max:20480'],
            'directory' => ['sometimes', 'string', 'regex:/^[a-z0-9\/-]+$/'],
            'max_width' => ['sometimes', 'integer', 'min:100', 'max:3000'],
        ]);

        $file = $request->file('file');
        $maxWidth = (int) $request->input('max_width', Images::MAX_WIDTH);
        $result = Library::addQuietly($file->getRealPath(), $file->getClientOriginalName());
        $path = Images::store($file, $request->input('directory', 'articles'), $maxWidth);
        if ($result) {
            Library::registerCopy($result->image, $path, $maxWidth, hash_file('sha256', $file->getRealPath()));
        }

        return response()->json(['path' => $path, 'url' => Images::url($path)], 201);
    }
}
