<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\PresentsLibraryImages;
use App\Models\LibraryImage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Affiche une image de la bibliothèque en plus grand (1024 px), pour bien la décrire ou la choisir.')]
class ViewLibraryImage extends Tool
{
    use PresentsLibraryImages;

    /** @return array<int, Response>|Response */
    public function handle(Request $request): array|Response
    {
        $image = LibraryImage::find($request->validate(['id' => ['required', 'integer']])['id']);
        if (! $image) {
            return Response::error('Image introuvable dans la bibliothèque : utiliser search-library.');
        }

        $preview = (new ImageManager(new Driver))->read($image->sourceAbsolutePath())->scaleDown(width: 1024)->toJpeg(quality: 80);

        return [
            Response::text(json_encode($this->presentLibraryImage($image), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            Response::image((string) $preview, 'image/jpeg'),
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Identifiant de l\'image (search-library).')->required(),
        ];
    }
}
