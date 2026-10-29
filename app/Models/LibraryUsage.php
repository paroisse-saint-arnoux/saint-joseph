<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une page (article ou accueil) qui utilise une copie d'une image de la bibliothèque. Voir App\Support\LibraryUsages. */
#[Fillable(['library_copy_id', 'library_image_id', 'page_type', 'article_id', 'field'])]
class LibraryUsage extends Model
{
    public const FIELDS = [
        'og_image' => 'Couverture',
        'gallery' => 'Galerie',
        'body' => 'Texte',
        'home' => 'Page d\'accueil',
    ];

    /** @return BelongsTo<LibraryCopy, $this> */
    public function copy(): BelongsTo
    {
        return $this->belongsTo(LibraryCopy::class, 'library_copy_id');
    }

    /** @return BelongsTo<LibraryImage, $this> */
    public function image(): BelongsTo
    {
        return $this->belongsTo(LibraryImage::class, 'library_image_id');
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function pageTitle(): string
    {
        return $this->page_type === 'home' ? 'Page d\'accueil' : ($this->article?->title ?? 'Article supprimé');
    }

    /** Page en ligne : l'accueil, ou un article publié (même programmé). */
    public function isPublic(): bool
    {
        return $this->page_type === 'home' || $this->article?->status === \App\Enums\ArticleStatus::Published;
    }
}
