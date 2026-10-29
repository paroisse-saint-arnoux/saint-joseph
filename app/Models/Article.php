<?php

namespace App\Models;

use App\Enums\ArticleFormat;
use App\Enums\ArticleStatus;
use App\Enums\Category;
use App\Support\Images;
use App\Support\Markdown;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'title', 'slug', 'category', 'excerpt', 'format', 'body', 'og_image', 'og_image_alt',
    'gallery', 'status', 'published_at', 'is_pinned',
])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    protected $attributes = [
        'format' => 'markdown',
        'status' => 'draft',
        'is_pinned' => false,
    ];

    protected function casts(): array
    {
        return [
            'category' => Category::class,
            'format' => ArticleFormat::class,
            'status' => ArticleStatus::class,
            'gallery' => 'array',
            'published_at' => 'datetime',
            'is_pinned' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Article $article) {
            $article->slug = static::uniqueSlug($article->slug ?: $article->title);
            $article->preview_token ??= Str::random(40);
        });

        static::saving(function (Article $article) {
            if ($article->status === ArticleStatus::Published && ! $article->published_at) {
                $article->published_at = now();
            }
        });
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'article';
        $slug = $base;

        for ($i = 2; static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /** Articles visibles publiquement : publiés et dont la date de publication est passée. */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ArticleStatus::Published)->where('published_at', '<=', now());
    }

    /** Épinglés d'abord, puis du plus récent au plus ancien. */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderByDesc('is_pinned')->orderByDesc('published_at')->orderByDesc('id');
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === ArticleStatus::Published && $this->published_at?->isPast();
    }

    public function url(): string
    {
        return route('articles.show', [$this->category, $this->slug]);
    }

    public function previewUrl(): string
    {
        return route('articles.show', [$this->category, $this->slug, 'apercu' => $this->preview_token]);
    }

    public function bodyHtml(): string
    {
        return $this->format === ArticleFormat::Html ? (string) $this->body : Markdown::render($this->body);
    }

    public function ogImageUrl(): ?string
    {
        return Images::url($this->og_image);
    }

    /** @return array<int, array{url: string, alt: string}> */
    public function galleryImages(): array
    {
        return collect($this->gallery ?? [])
            ->filter(fn ($item) => filled($item['path'] ?? null))
            ->map(fn ($item) => ['url' => Images::url($item['path']), 'alt' => $item['alt'] ?? ''])
            ->values()
            ->all();
    }
}
