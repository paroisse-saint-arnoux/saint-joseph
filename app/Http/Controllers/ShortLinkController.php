<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use App\Support\ShortLinks;

/**
 * Lien court sj.saint-arnoux.fr/{code} → page du site avec ses balises UTM.
 * Rien n'est enregistré (ni visite, ni session) : seule la page d'arrivée compte dans les statistiques.
 */
class ShortLinkController extends Controller
{
    /** Sur le domaine court : un code inconnu ou toute autre adresse renvoie vers la même adresse du site. */
    public function short(?string $path = null)
    {
        $link = $path ? ShortLink::firstWhere('code', strtolower($path)) : null;

        return $link
            ? redirect()->away($link->url)
            : redirect()->away(rtrim(config('app.url'), '/').'/'.ltrim((string) $path, '/'), 301);
    }

    /** Sur le domaine principal (si sj.saint-arnoux.fr y redirige en gardant le chemin) : adresse inconnue du site. */
    public function fallback(string $fallbackPlaceholder)
    {
        $code = $fallbackPlaceholder;
        $link = preg_match('/^'.ShortLinks::CODE_PATTERN.'$/i', $code) ? ShortLink::firstWhere('code', strtolower($code)) : null;
        abort_unless($link, 404);

        return redirect()->away($link->url);
    }
}
