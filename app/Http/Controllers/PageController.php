<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Markdown;

class PageController extends Controller
{
    public function legal()
    {
        return $this->page('Mentions légales', 'legal.mentions');
    }

    public function privacy()
    {
        return $this->page('Politique de confidentialité', 'legal.privacy');
    }

    private function page(string $title, string $setting)
    {
        return view('page', ['title' => $title, 'html' => Markdown::render(Setting::get($setting))]);
    }
}
