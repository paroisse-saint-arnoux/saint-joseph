<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\Rgive;
use App\Support\Markdown;

/**
 * Page /don : formulaire de don RGIVE du diocèse intégré dans la page, jauge de la collecte (RGIVE)
 * et textes modifiables dans Admin > Contenu > Page de don (réglage `don`).
 */
class DonController extends Controller
{
    public function __invoke(Rgive $rgive)
    {
        $don = Setting::get('don', []);
        $md = fn (?string $text) => filled($text) ? Markdown::render($text) : null;

        return view('don', [
            'don' => $don,
            'html' => [
                'why' => $md($don['why']['text'] ?? null),
                'projet' => $md($don['projet']['text'] ?? null),
                'individuals' => $md($don['fiscal']['individuals'] ?? null),
                'companies' => $md($don['fiscal']['companies'] ?? null),
            ],
            'raised' => $rgive->raised(),
            'goal' => (int) Setting::get('home.collecte.goal', 6000000),
        ]);
    }
}
