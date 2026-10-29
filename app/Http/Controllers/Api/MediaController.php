<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Images;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    /** Envoi d'une image (redimensionnée + WebP). Renvoie le chemin à utiliser dans og_image / gallery, et l'URL publique. */
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'image', 'max:20480'],
            'directory' => ['sometimes', 'string', 'regex:/^[a-z0-9\/-]+$/'],
            'max_width' => ['sometimes', 'integer', 'min:100', 'max:3000'],
        ]);

        $path = Images::store($request->file('file'), $request->input('directory', 'articles'), (int) $request->input('max_width', Images::MAX_WIDTH));

        return response()->json(['path' => $path, 'url' => Images::url($path)], 201);
    }
}
