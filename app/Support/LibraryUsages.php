<?php

namespace App\Support;

use App\Models\Article;
use App\Models\LibraryCopy;
use App\Models\LibraryUsage;
use Illuminate\Support\Arr;

/**
 * Index des pages qui utilisent les images de la bibliothèque (table library_usages).
 * Recalculé à l'enregistrement d'un article ou de la page d'accueil, en conservant la date
 * de première utilisation des lignes déjà présentes.
 */
class LibraryUsages
{
    public static function syncArticle(Article $article): void
    {
        $fields = [
            'og_image' => array_filter([$article->og_image]),
            'gallery' => array_column($article->gallery ?? [], 'path'),
            'body' => self::bodyPaths((string) $article->body),
        ];

        self::sync('article', $article->id, $fields);
    }

    public static function syncHome(array $home): void
    {
        $strings = array_filter(Arr::flatten($home), fn ($value) => is_string($value) && str_ends_with(strtolower($value), '.webp'));

        self::sync('home', null, ['home' => array_values($strings)]);
    }

    /** Chemins (disque public) des images insérées dans un corps Markdown ou HTML : …/storage/{chemin}. */
    public static function bodyPaths(string $body): array
    {
        preg_match_all('#/storage/([^"\'()\s?<>]+\.(?:webp|jpe?g|png|gif))#i', $body, $matches);

        return array_values(array_unique(array_map('urldecode', $matches[1])));
    }

    /** @param  array<string, array<int, string>>  $pathsByField */
    private static function sync(string $type, ?int $articleId, array $pathsByField): void
    {
        $copies = LibraryCopy::whereIn('path', array_merge(...array_values($pathsByField)) ?: ['-'])->get()->keyBy('path');

        $wanted = [];
        foreach ($pathsByField as $field => $paths) {
            foreach ($paths as $path) {
                if ($copy = $copies->get($path)) {
                    $wanted[$copy->id.'|'.$field] = ['library_copy_id' => $copy->id, 'library_image_id' => $copy->library_image_id, 'field' => $field];
                }
            }
        }

        $existing = LibraryUsage::where('page_type', $type)->where('article_id', $articleId)->get();
        foreach ($existing as $usage) {
            if (! isset($wanted[$usage->library_copy_id.'|'.$usage->field])) {
                $usage->delete();
            }
            unset($wanted[$usage->library_copy_id.'|'.$usage->field]);
        }

        foreach ($wanted as $row) {
            LibraryUsage::create([...$row, 'page_type' => $type, 'article_id' => $articleId]);
        }
    }
}
