<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
    }
}
