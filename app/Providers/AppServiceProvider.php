<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Consultation : à l'exposition, tout le monde partage souvent la même adresse IP (Wi-Fi, 4G) :
        // limites larges par IP, serrées par email (identification) ou par téléphone (session, enregistrement continu).
        RateLimiter::for('consultation-identify', fn (Request $request) => [
            Limit::perMinutes(10, 300)->by('ip|'.$request->ip()),
            Limit::perMinutes(10, 10)->by('email|'.mb_strtolower(trim((string) $request->input('email')))),
        ]);
        RateLimiter::for('consultation-save', fn (Request $request) => [
            Limit::perMinute(120)->by('session|'.$request->session()->getId()),
            Limit::perMinute(2000)->by('ip|'.$request->ip()),
        ]);

        // Page /croix : partagée par WhatsApp, souvent ouverte depuis le même réseau (paroisse, lycée).
        RateLimiter::for('croix-signup', fn (Request $request) => [
            Limit::perMinutes(10, 200)->by('croix-ip|'.$request->ip()),
            Limit::perMinutes(10, 5)->by('croix-email|'.mb_strtolower(trim((string) $request->input('email')))),
        ]);

        // Page /huile (QR code des bouteilles d'huile d'olive).
        RateLimiter::for('huile-signup', fn (Request $request) => [
            Limit::perMinutes(10, 100)->by('huile-ip|'.$request->ip()),
            Limit::perMinutes(10, 5)->by('huile-email|'.mb_strtolower(trim((string) $request->input('email')))),
        ]);

        // Connecteur Claude (OAuth) : page « Autoriser Claude » aux couleurs du site.
        Passport::authorizationView('mcp.authorize');
        // Jeton d'accès court, renouvelé automatiquement par Claude ; reconnexion nécessaire après un an sans usage.
        Passport::tokensExpireIn(now()->addDays(7));
        Passport::refreshTokensExpireIn(now()->addYear());
        Passport::personalAccessTokensExpireIn(now()->addYears(2));
    }
}
