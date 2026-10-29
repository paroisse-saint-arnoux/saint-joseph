<?php

namespace App\Mcp\Tools\Concerns;

use App\Enums\ArticleFormat;
use App\Enums\ArticleStatus;
use App\Enums\Category;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/** Champs d'un article communs à create-article et update-article. */
trait ArticleSchema
{
    protected function articleFields(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Titre de l\'article.'),
            'category' => $schema->string()->enum(array_column(Category::cases(), 'value'))
                ->description('Catégorie (une seule) : agenda, architecture, budget, construction, projet, presentation, equipe.'),
            'excerpt' => $schema->string()->description('Chapô : 1 à 2 phrases (400 caractères max), affiché dans les listes et en partage.'),
            'format' => $schema->string()->enum(array_column(ArticleFormat::cases(), 'value'))
                ->description('markdown (par défaut, à privilégier) ou html (mise en page riche).'),
            'body' => $schema->string()->description('Corps de l\'article, en Markdown ou en HTML selon « format ». Ne pas répéter le titre ni le chapô.'),
            'og_image' => $schema->string()->description('Image de couverture et de partage : chemin (path) renvoyé par prepare-article-image.'),
            'og_image_alt' => $schema->string()->description('Description de l\'image de couverture (obligatoire si og_image).'),
            'gallery' => $schema->array()->items($schema->object([
                'path' => $schema->string()->required(),
                'alt' => $schema->string()->required(),
            ]))->description('Carrousel de photos en fin d\'article : liste de {path, alt} (path renvoyé par prepare-article-image). Remplace la galerie existante.'),
            'status' => $schema->string()->enum(array_column(ArticleStatus::cases(), 'value'))
                ->description('draft (brouillon, par défaut) ou published. Ne publier que si l\'utilisateur le demande explicitement.'),
            'published_at' => $schema->string()->description('Date de publication (ISO 8601). Une date future programme la publication.'),
            'is_pinned' => $schema->boolean()->description('Épingler en tête de liste.'),
            'show_cover' => $schema->boolean()->description('Afficher l\'image de couverture en haut de l\'article (oui par défaut). Elle sert de toute façon aux partages et aux cartes.'),
            'slug' => $schema->string()->description('Fin de l\'adresse (minuscules, tirets). Généré depuis le titre si absent : à éviter sauf demande.'),
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function checkImageAlt(array $data): ?string
    {
        if (! empty($data['og_image']) && empty($data['og_image_alt'])) {
            return 'og_image_alt est obligatoire quand og_image est fourni : décrire l\'image en une phrase.';
        }

        return null;
    }
}
