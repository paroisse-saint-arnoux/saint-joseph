<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\PresentsLibraryImages;
use App\Models\LibraryImage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description(<<<'TXT'
Cherche dans la bibliothèque d'images du site (les plus récentes d'abord), avec une vignette de chaque image.
Chaque mot est cherché dans le titre, la description, le nom du fichier d'origine et le titre des articles qui utilisent l'image. Sans « query » : dernières images ajoutées
(utile juste après que l'utilisateur a ajouté des images). Pour utiliser une image dans un article : prepare-article-image.
TXT)]
class SearchLibrary extends Tool
{
    use PresentsLibraryImages;

    /** @return array<int, Response> */
    public function handle(Request $request): array
    {
        $data = $request->validate([
            'query' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);
        $perPage = $data['per_page'] ?? 12;

        $images = LibraryImage::query()
            ->search($data['query'] ?? null)
            ->withCount('usages')
            ->latest('id')
            ->paginate($perPage, page: $data['page'] ?? 1);

        if ($images->isEmpty()) {
            return [Response::text("Aucune image trouvée. L'utilisateur peut ajouter des images (photos ou document Word) dans la bibliothèque : ".$this->libraryAdminUrl())];
        }

        $responses = [Response::text(
            "{$images->total()} image(s) trouvée(s), page {$images->currentPage()}/{$images->lastPage()}. Vignettes ci-dessous, dans le même ordre.\n\n"
            .json_encode($images->getCollection()->map(fn ($i) => $this->presentLibraryImage($i))->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        )];

        foreach ($images as $image) {
            $responses[] = Response::text("Image #{$image->id} — {$image->title}");
            $responses[] = Response::image(Storage::disk('public')->get($image->thumb_path), 'image/webp');
        }

        return $responses;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Mot(s) à chercher (titre, description, nom de fichier).'),
            'page' => $schema->integer()->description('Page de résultats (1 par défaut).'),
            'per_page' => $schema->integer()->description('Images par page (12 par défaut, 20 max).'),
        ];
    }
}
