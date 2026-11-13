<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Setting;
use App\Services\Rgive;

class HomeController extends Controller
{
    public function __invoke(Rgive $rgive)
    {
        $home = Setting::get('home', []);
        // Montant collecté et derniers donateurs : campagne RGIVE (mise à jour toutes les 5 minutes).
        $home['collecte']['raised'] = $rgive->raised();
        $home['collecte']['donors'] = $rgive->latestDonors();

        return view('home', [
            'home' => $home,
            'latest' => Article::published()->ordered()->limit(3)->get(),
        ]);
    }
}
