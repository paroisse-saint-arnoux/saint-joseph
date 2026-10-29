<?php

use App\Models\LibraryCopy;
use App\Models\User;
use App\Support\MagicLink;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

// Développement : lien de connexion à l'admin sans passer par l'email (tests de rendu, captures d'écran).
// php artisan admin:lien [email]  (par défaut : le premier administrateur)
Artisan::command('admin:lien {email?}', function () {
    if (app()->isProduction()) {
        $this->error('Commande désactivée en production.');

        return 1;
    }

    $user = $this->argument('email')
        ? User::firstWhere('email', $this->argument('email'))
        : User::where('is_active', true)->where('role', 'admin')->orderBy('id')->first();
    if (! $user) {
        $this->error('Utilisateur introuvable.');

        return 1;
    }

    $this->line(MagicLink::url($user, now()->addMinutes(MagicLink::MINUTES)));
})->purpose('Affiche un lien de connexion à l\'admin (hors production)');

// Copies d'images qu'aucune page n'utilise (image choisie puis article non enregistré, article supprimé…) :
// effacées après un jour. Lancée par deploy.sh.
Artisan::command('bibliotheque:nettoyer', function () {
    $orphans = LibraryCopy::doesntHave('usages')->where('updated_at', '<', now()->subDay())->get();
    foreach ($orphans as $copy) {
        Storage::disk('public')->delete($copy->path);
        $copy->delete();
    }
    $this->info($orphans->count().' copie(s) inutilisée(s) supprimée(s).');
})->purpose('Supprime les copies d\'images que plus aucune page n\'utilise');
