<?php

namespace App\Support;

use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Rendu Markdown des articles. Les éditeurs sont des personnes de confiance :
 * le HTML brut est autorisé. Un lien YouTube seul sur sa ligne devient une vidéo intégrée.
 * Largeur d'affichage d'une image : ![description](adresse){width=400} (seul attribut autorisé ;
 * le fichier est redimensionné à l'enregistrement, voir Library::resizeBodyImages).
 */
class Markdown
{
    private const YOUTUBE = '~^[ \t]*https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:\S*&)?v=|shorts/|embed/)|youtu\.be/)([\w-]{11})\S*[ \t]*$~m';

    public static function render(?string $markdown): string
    {
        if (blank($markdown)) {
            return '';
        }

        $markdown = preg_replace_callback(self::YOUTUBE, fn (array $m) => "\n".self::youtubeEmbed($m[1])."\n", $markdown);

        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            // GFM échappe <iframe> par défaut : on l'autorise pour les vidéos intégrées.
            'disallowed_raw_html' => ['disallowed_tags' => []],
            'attributes' => ['allow' => ['width']],
        ]);
        $converter->getEnvironment()->addExtension(new AttributesExtension);

        return (string) $converter->convert($markdown);
    }

    public static function youtubeEmbed(string $id): string
    {
        return '<div class="video-embed ratio ratio-16x9"><iframe src="https://www.youtube-nocookie.com/embed/'.e($id).'" title="Vidéo YouTube" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe></div>';
    }
}
