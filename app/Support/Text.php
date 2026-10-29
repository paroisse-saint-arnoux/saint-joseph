<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class Text
{
    /**
     * Mise en forme légère pour les champs courts de l'admin :
     * **gras**, *italique doré* et retours à la ligne. Le reste est échappé.
     */
    public static function inline(?string $text): HtmlString
    {
        $html = e((string) $text);
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<b>$1</b>', $html);
        $html = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $html);

        return new HtmlString(nl2br($html, false));
    }

    /** @return array<int, string> une ligne non vide = un élément */
    public static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text))));
    }
}
