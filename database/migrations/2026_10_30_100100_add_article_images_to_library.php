<?php

use App\Models\Article;
use App\Models\Setting;
use App\Support\Images;
use App\Support\Library;
use App\Support\LibraryUsages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * Verse dans la bibliothèque les images déjà utilisées par les articles (couverture, galerie, corps)
 * et la page d'accueil. Les fichiers en place deviennent les copies de ces images, puis l'index
 * des utilisations est calculé. Les doublons sont détectés comme pour tout ajout.
 */
return new class extends Migration
{
    public function up(): void
    {
        Article::query()->orderBy('id')->each(function (Article $article) {
            $images = [];
            if ($article->og_image) {
                $images[$article->og_image] = $article->og_image_alt;
            }
            foreach ($article->gallery ?? [] as $item) {
                $images[$item['path']] ??= $item['alt'] ?? null;
            }

            // Corps : <img src="/storage/…" alt="…"> (HTML) ou ![…](/storage/…) (Markdown), avec leur description.
            $body = (string) $article->body;
            preg_match_all('#<img\b[^>]*>#i', $body, $tags);
            foreach ($tags[0] as $tag) {
                if (preg_match('#src="[^"]*/storage/([^"?]+)"#i', $tag, $src)) {
                    $images[urldecode($src[1])] ??= preg_match('#alt="([^"]*)"#i', $tag, $alt) ? html_entity_decode($alt[1]) : null;
                }
            }
            preg_match_all('#!\[([^\]]*)\]\([^)\s]*/storage/([^)\s?]+)#', $body, $markdown, PREG_SET_ORDER);
            foreach ($markdown as [, $alt, $path]) {
                $images[urldecode($path)] ??= $alt ?: null;
            }

            foreach ($images as $path => $alt) {
                $this->import($path, $alt ?: $article->title, $alt);
            }
            LibraryUsages::syncArticle($article);
        });

        $home = Setting::get('home', []);
        $this->importHome($home);
        LibraryUsages::syncHome($home);
    }

    /** Images de l'accueil : la description voisine (« alt ») sert de titre quand elle existe. */
    private function importHome(array $values): void
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                $this->importHome($value);
            } elseif (is_string($value) && str_ends_with(strtolower($value), '.webp')) {
                $alt = is_string($values['alt'] ?? null) ? $values['alt'] : null;
                $this->import($value, $alt ?: 'Page d\'accueil', $alt);
            }
        }
    }

    private function import(string $path, string $title, ?string $alt): void
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return;
        }

        if ($result = Library::addQuietly($disk->path($path), basename($path), $title, $alt)) {
            Library::registerCopy($result->image, $path, Images::MAX_WIDTH, hash_file('sha256', $disk->path($path)));
        }
    }
};
