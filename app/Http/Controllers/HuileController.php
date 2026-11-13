<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\Brevo;
use App\Services\Rgive;
use App\Support\SpamGuard;
use Illuminate\Http\Request;

/**
 * Page /huile : ouverte par le QR code des étiquettes de l'huile d'olive vendue au profit du projet.
 * Publique mais non référencée (noindex, hors sitemap). Remerciement, projet, inscription (Brevo, liste 8), don.
 */
class HuileController extends Controller
{
    public function show(Rgive $rgive)
    {
        return response()->view('huile', [
            'stats' => Setting::get('home.projet.stats', []), // chiffres clés de l'accueil
            'raised' => $rgive->raised(), // campagne RGIVE
            'goal' => (int) Setting::get('home.collecte.goal', 0),
            'heroImage' => Setting::get('home.hero.image'),
            'projectImage' => Setting::get('home.projet.images.0'),
            'donUrl' => Setting::get('site.don_url') ?: 'https://don-nice.catholique.fr/centre-paroissial-st-arnoux-rouret/',
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function store(Request $request, Brevo $brevo)
    {
        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190'],
            'consent' => ['accepted'],
        ], [
            'email.required' => 'Merci d\'indiquer votre adresse e-mail.',
            'email.email' => 'Cette adresse e-mail ne semble pas complète (exemple : prenom.nom@gmail.com).',
            'consent.accepted' => 'Merci de cocher la case pour recevoir les nouvelles du projet.',
        ]);

        if (! SpamGuard::passes($request, withTurnstile: false)) {
            return back()->withInput()->withErrors(['email' => 'Merci de réessayer dans quelques secondes.'])->withFragment('nouvelles');
        }

        $clean = fn (?string $v) => filled($v = trim((string) $v)) ? mb_substr($v, 0, 80) : null;
        $ok = $brevo->addContact(
            mb_strtolower(trim($data['email'])),
            (int) config('services.brevo.huile_list_id'),
            array_filter(['PRENOM' => $clean($data['first_name'] ?? null), 'NOM' => $clean($data['last_name'] ?? null)]),
        );

        if (! $ok) {
            return back()->withInput()->withErrors(['email' => 'L\'inscription n\'a pas pu aboutir. Merci de réessayer dans quelques minutes.'])->withFragment('nouvelles');
        }

        return redirect()->to(route('huile').'#nouvelles')->with('huile_saved', true);
    }
}
