<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * « SJO2 » est un code interne : il ne doit jamais apparaître sur le site.
 * Remplace les textes de départ qui le contenaient, sans toucher à ceux déjà modifiés dans l'admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        $site = Setting::get('site');
        if (! is_array($site)) {
            return;
        }

        $replacements = [
            'description' => ['Projet SJO2 : la Paroisse', 'La Paroisse'],
            'about' => ['Projet SJO2 — centre paroissial et église porté par', 'Centre paroissial et église Saint Joseph, portés par'],
        ];

        foreach ($replacements as $key => [$old, $new]) {
            if (isset($site[$key]) && str_contains($site[$key], $old)) {
                $site[$key] = str_replace($old, $new, $site[$key]);
            }
        }

        Setting::put('site', $site);
    }
};
