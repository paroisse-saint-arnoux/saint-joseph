<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Réglages généraux (pied de page, réseaux, lien de don…) disponibles dans toutes les vues publiques.
        View::composer(['layouts.app', 'partials.*'], function ($view) {
            $view->with('site', Setting::get('site', []));
        });

        // Connecteur Claude (OAuth) : page « Autoriser Claude » aux couleurs du site.
        Passport::authorizationView('mcp.authorize');
        // Jeton d'accès court, renouvelé automatiquement par Claude ; reconnexion nécessaire après un an sans usage.
        Passport::tokensExpireIn(now()->addDays(7));
        Passport::refreshTokensExpireIn(now()->addYear());
        Passport::personalAccessTokensExpireIn(now()->addYears(2));
    }
}
