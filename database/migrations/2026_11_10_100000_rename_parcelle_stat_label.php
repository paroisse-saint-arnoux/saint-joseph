<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

// Chiffres clés de l'accueil (repris sur /huile) : « Parcelle acquise » devient « Parcelle au Rouret ».
return new class extends Migration
{
    public function up(): void
    {
        $this->rename('Parcelle acquise', 'Parcelle au Rouret');
    }

    public function down(): void
    {
        $this->rename('Parcelle au Rouret', 'Parcelle acquise');
    }

    private function rename(string $from, string $to): void
    {
        $setting = Setting::find('home');
        if (! $setting || ! is_array($stats = $setting->value['projet']['stats'] ?? null)) {
            return;
        }

        $value = $setting->value;
        $value['projet']['stats'] = array_map(fn ($stat) => ($stat['label'] ?? null) === $from ? ['label' => $to] + $stat : $stat, $stats);
        if ($value !== $setting->value) {
            Setting::put('home', $value);
        }
    }
};
