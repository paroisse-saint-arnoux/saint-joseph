<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\JsonResponse;
use App\Mcp\Tools\Concerns\PresentsLibraryImages;
use App\Support\Library;
use App\Support\RemoteImage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
#[Description('Ajoute à la bibliothèque une image publiée sur le web (adresse directe de l\'image). Si elle y est déjà, l\'image existante est renvoyée (et remplacée si la nouvelle est de meilleure qualité). Utiliser ensuite prepare-article-image avec l\'id renvoyé.')]
class ImportImageFromUrl extends Tool
{
    use JsonResponse, PresentsLibraryImages;

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'url' => ['required', 'url:http,https', 'max:2000'],
            'title' => ['nullable', 'string', 'max:190'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            [$tmp, $name] = RemoteImage::download($data['url']);
            try {
                $result = Library::add($tmp, $name, $data['title'] ?? null, $data['alt'] ?? null);
            } finally {
                @unlink($tmp);
            }
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return Response::error("Cette adresse n'a pas pu être importée comme image.");
        }

        return $this->json("Image {$result->label()} dans la bibliothèque.", $this->presentLibraryImage($result->image));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->description('Adresse de l\'image (https://…).')->required(),
            'title' => $schema->string()->description('Titre court de l\'image.'),
            'alt' => $schema->string()->description('Description de l\'image (une phrase).'),
        ];
    }
}
