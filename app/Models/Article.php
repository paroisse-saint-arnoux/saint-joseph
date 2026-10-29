<?php

namespace App\Models;

use App\Enums\ArticleFormat;
use App\Enums\ArticleStatus;
use App\Enums\Category;
use App\Support\Images;
use App\Support\Library;
use App\Support\LibraryUsages;
use App\Support\Markdown;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'title', 'slug', 'category', 'excerpt', 'format', 'body', 'og_image', 'og_image_alt',
    'gallery', 'status', 'published_at', 'is_pinned', 'show_cover',
])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    protected $attributes = [
        'format' => 'markdown',
        'status' => 'draft',
        'is_pinned' => false,
        'show_cover' => true,
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
            'show_cover' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Article $article) {
            $article->slug = static::uniqueSlug($article->slug ?: $article->title);
            $article->preview_token ??= Str::random(40);
            $article->author_id ??= auth()->id();
        });

        // Index des images de la bibliothèque utilisées par l'article.
        static::saved(fn (Article $article) => LibraryUsages::syncArticle($article));

        static::saving(function (Article $article) {
            // Images du texte avec une largeur ({width=…}) : fichier redimensionné en conséquence.
            if ($article->isDirty('body') && $article->format === ArticleFormat::Markdown && filled($article->body)) {
                $article->body = Library::resizeBodyImages($article->body);
            }

            if ($article->status === ArticleStatus::Published && ! $article->published_at) {
                $article->published_at = now();
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
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

    /** Page d'édition dans l'admin. */
    public function adminUrl(): string
    {
        return url('/admin/articles/'.$this->getKey().'/modifier');
    }

    /**
     * Retrouve un article à partir de ce qu'un utilisateur peut coller : slug, adresse publique
     * (/le-projet/{categorie}/{slug}), adresse d'aperçu ou adresse d'édition dans l'admin.
     */
    public static function findByReference(string $reference): ?static
    {
        $reference = trim($reference);
        $path = trim((string) parse_url($reference, PHP_URL_PATH), '/');

        if (preg_match('#^admin/articles/(\d+)#', $path, $m)) {
            return static::find($m[1]);
        }
        if (preg_match('#^le-projet/[^/]+/([^/]+)$#', $path, $m)) {
            return static::firstWhere('slug', $m[1]);
        }

        return static::firstWhere('slug', $reference) ?? (ctype_digit($reference) ? static::find($reference) : null);
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
