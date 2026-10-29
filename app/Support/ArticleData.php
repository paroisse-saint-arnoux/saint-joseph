<?php

namespace App\Support;

use App\Enums\ArticleFormat;
use App\Enums\ArticleStatus;
use App\Enums\Category;
use App\Models\Article;
use Illuminate\Validation\Rule;

/** Règles de validation et représentation d'un article, communes à l'API REST et au connecteur Claude (MCP). */
class ArticleData
{
    public static function rules(?Article $article = null): array
    {
        $required = $article ? 'sometimes' : 'required';

        return [
            'title' => [$required, 'string', 'max:190'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('articles')->ignore($article)],
            'category' => [$required, Rule::enum(Category::class)],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:400'],
            'format' => ['sometimes', Rule::enum(ArticleFormat::class)],
            'body' => ['sometimes', 'nullable', 'string'],
            'og_image' => ['sometimes', 'nullable', 'string', 'max:255'],
            'og_image_alt' => ['sometimes', 'nullable', 'string', 'max:190'],
            'gallery' => ['sometimes', 'nullable', 'array'],
            'gallery.*.path' => ['required', 'string', 'max:255'],
            'gallery.*.alt' => ['required', 'string', 'max:190'],
            'status' => ['sometimes', Rule::enum(ArticleStatus::class)],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'is_pinned' => ['sometimes', 'boolean'],
            'show_cover' => ['sometimes', 'boolean'],
        ];
    }

    public static function present(Article $article, bool $withBody = false): array
    {
        return array_filter([
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'category' => $article->category->value,
            'excerpt' => $article->excerpt,
            'format' => $article->format->value,
            'body' => $withBody ? (string) $article->body : null,
            'og_image' => $article->og_image,
            'og_image_url' => $article->ogImageUrl(),
            'og_image_alt' => $article->og_image_alt,
            'gallery' => $article->gallery ?? [],
            'status' => $article->status->value,
            'published_at' => $article->published_at?->toAtomString(),
            'is_pinned' => $article->is_pinned,
            'show_cover' => $article->show_cover,
            'url' => $article->url(),
            'preview_url' => $article->previewUrl(),
            'admin_url' => $article->adminUrl(),
            'updated_at' => $article->updated_at?->toAtomString(),
        ], fn ($value) => $value !== null);
    }
}
