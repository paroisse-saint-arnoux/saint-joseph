<?php

namespace App\Http\Resources;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Article */
class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category' => $this->category->value,
            'excerpt' => $this->excerpt,
            'format' => $this->format->value,
            'body' => $this->when($request->routeIs('api.articles.show', 'api.articles.store', 'api.articles.update'), $this->body),
            'og_image' => $this->og_image,
            'og_image_url' => $this->ogImageUrl(),
            'og_image_alt' => $this->og_image_alt,
            'gallery' => $this->gallery ?? [],
            'status' => $this->status->value,
            'published_at' => $this->published_at?->toAtomString(),
            'is_pinned' => $this->is_pinned,
            'url' => $this->url(),
            'preview_url' => $this->previewUrl(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
