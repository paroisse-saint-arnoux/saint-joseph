<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Un utilisateur désactivé dans l'admin perd aussi l'accès par jeton (API, connecteur Claude). */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_active, 403, 'Compte désactivé.');

        // Connecteur Claude : date de dernière utilisation (liste des utilisateurs).
        if ($request->is('mcp')) {
            $request->user()->recordActivity('last_mcp_at');
        }

        return $next($request);
    }
}
