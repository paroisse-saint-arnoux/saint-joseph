<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\JsonResponse;
use App\Mcp\Tools\Concerns\PresentsLibraryImages;
use App\Models\LibraryImage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
#[Description('Renomme une image de la bibliothèque ou complète sa description (alt), pour la retrouver et la réutiliser plus facilement.')]
class UpdateLibraryImage extends Tool
{
    use JsonResponse, PresentsLibraryImages;

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'title' => ['sometimes', 'string', 'max:190'],
            'alt' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $image = LibraryImage::find($data['id']);
        if (! $image) {
            return Response::error('Image introuvable dans la bibliothèque.');
        }
        unset($data['id']);
        $image->update($data);

        return $this->json('Image mise à jour.', $this->presentLibraryImage($image));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Identifiant de l\'image.')->required(),
            'title' => $schema->string()->description('Titre court (ex. « Bénédiction du terrain – la foule »).'),
            'alt' => $schema->string()->description('Description de l\'image pour les personnes malvoyantes (une phrase).'),
        ];
    }
}
