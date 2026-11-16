<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Lien court sj.saint-arnoux.fr/{code} → page du site avec balises UTM (App\Support\ShortLinks). */
#[Fillable(['code', 'url', 'url_hash', 'target', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content'])]
class ShortLink extends Model
{
    public function shortUrl(): string
    {
        return rtrim(config('app.short_url'), '/').'/'.$this->code;
    }
}
