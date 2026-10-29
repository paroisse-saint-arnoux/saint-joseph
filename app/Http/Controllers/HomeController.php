<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Setting;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'home' => Setting::get('home', []),
            'latest' => Article::published()->ordered()->limit(3)->get(),
        ]);
    }
}
