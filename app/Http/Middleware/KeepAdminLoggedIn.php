<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Connexion admin sans expiration : à chaque visite de l'admin, le cookie « se souvenir de moi »
 * est renvoyé pour la durée maximale (400 jours, plafond des navigateurs). Tant qu'on revient
 * au moins une fois par an, on reste connecté. Se déconnecter invalide le cookie sur tous les appareils.
 */
class KeepAdminLoggedIn
{
    /** 400 jours : durée maximale d'un cookie dans les navigateurs (et durée par défaut de Laravel). */
    private const MINUTES = 400 * 24 * 60;

    public function handle(Request $request, Closure $next): Response
    {
        /** @var SessionGuard $guard */
        $guard = Auth::guard();

        if ($guard->check()) {
            $guard->user()->recordActivity('last_seen_at');

            $name = $guard->getRecallerName();

            if ($value = $request->cookies->get($name)) {
                Cookie::queue($name, $value, self::MINUTES);
            } else {
                // Session ouverte sans cookie « se souvenir de moi » : on le crée.
                $guard->login($guard->user(), remember: true);
            }
        }

        return $next($request);
    }
}
