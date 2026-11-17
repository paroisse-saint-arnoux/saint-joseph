<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

// Montant ajouté au total de la campagne RGIVE (dons reçus hors RGIVE) : réglage site.raised_offset,
// modifiable dans Admin > Contenu > Autres textes du site > Don. 850 000 € au 17/11/2026.
return new class extends Migration
{
    public function up(): void
    {
        $setting = Setting::find('site');
        if (! $setting || array_key_exists('raised_offset', $setting->value)) {
            return; // tests (contenu initial non posé) ou déjà renseigné
        }

        Setting::put('site', [...$setting->value, 'raised_offset' => 850000]);
    }

    public function down(): void
    {
        $setting = Setting::find('site');
        if ($setting && array_key_exists('raised_offset', $setting->value)) {
            $value = $setting->value;
            unset($value['raised_offset']);
            Setting::put('site', $value);
        }
    }
};
