<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Nouveaux prix des bouteilles d'huile (21/11/2026) : 250 mL 13 €, 500 mL 23 €, 750 mL 30 €
// (au lieu de 17, 30 et 40 €). Les commandes déjà passées gardent le prix copié à la commande.
return new class extends Migration
{
    private const PRICES = ['250 mL' => [1700, 1300], '500 mL' => [3000, 2300], '750 mL' => [4000, 3000]];

    public function up(): void
    {
        foreach (self::PRICES as $label => [, $new]) {
            DB::table('oil_products')->where('label', $label)->update(['price_cents' => $new, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (self::PRICES as $label => [$old]) {
            DB::table('oil_products')->where('label', $label)->update(['price_cents' => $old, 'updated_at' => now()]);
        }
    }
};
