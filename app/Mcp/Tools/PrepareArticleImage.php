<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\JsonResponse;
use App\Models\LibraryImage;
use App\Support\Images;
use App\Support\Library;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description(<<<'TXT'
Prépare une image de la bibliothèque pour un article : crée une copie redimensionnée et optimisée (WebP), propre à l'article.
Renvoie « path » (pour og_image ou gallery) et « markdown » (à placer tel quel dans le corps, sur sa propre ligne).
Pour une image du corps affichée en petit, indiquer display_width (largeur d'affichage en pixels, ex. 250, 400, 600) :
le markdown contient alors {width=…} et le fichier est dimensionné en conséquence. Sans display_width : pleine largeur du texte.
Appeler une fois par image utilisée. La description (alt) de la bibliothèque est reprise si elle existe.
TXT)]
class PrepareArticleImage extends Tool
{
    use JsonResponse;

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'display_width' => ['nullable', 'integer', 'min:50', 'max:1200'],
            'alt' => ['nullable', 'string', 'max:190'],
        ]);

        $image = LibraryImage::find($data['id']);
        if (! $image) {
            return Response::error('Image introuvable dans la bibliothèque : utiliser search-library.');
        }

        $width = $data['display_width'] ?? null;
        $path = $width
            // Fichier à 2 × la largeur affichée (écrans haute densité), sans dépasser l'original.
            ? Library::copyAtWidth($image, 'articles', min(2 * $width, $image->width, Images::MAX_WIDTH))->path
            : Library::copyForArticle($image, 'articles');
        $alt = $data['alt'] ?? $image->alt ?? $image->title;

        return $this->json('Copie prête pour l\'article.', [
            'path' => $path,
            'alt' => $alt,
            'markdown' => '!['.str_replace(['[', ']'], '', $alt).'](/storage/'.$path.')'.($width ? '{width='.$width.'}' : ''),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Identifiant de l\'image dans la bibliothèque.')->required(),
            'display_width' => $schema->integer()->description('Largeur d\'affichage dans le texte, en pixels (facultatif ; pleine largeur sinon).'),
            'alt' => $schema->string()->description('Description à utiliser (sinon celle de la bibliothèque).'),
        ];
    }
}
