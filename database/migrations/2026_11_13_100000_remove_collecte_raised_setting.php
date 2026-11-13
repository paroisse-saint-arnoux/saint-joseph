<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

// Le montant collecté vient uniquement de la campagne RGIVE (App\Services\Rgive) :
// le montant saisi à la main (home.collecte.raised) ne sert plus.
return new class extends Migration
{
    public function up(): void
    {
        $setting = Setting::find('home');
        if (! $setting || ! array_key_exists('raised', $setting->value['collecte'] ?? [])) {
            return;
        }

        $value = $setting->value;
        unset($value['collecte']['raised']);
        Setting::put('home', $value);
    }

    public function down(): void
    {
        // Rien à restaurer : le montant affiché vient de RGIVE.
    }
};
