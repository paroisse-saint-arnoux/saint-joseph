<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Tarif réduit des commandes d'huile : −20 % au lieu de −50 % (22/11/2026). Les commandes déjà au tarif réduit
// sont recalculées (prix plein des lignes × 0,8, arrondi au centime).
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('oil_orders')->where('discounted', true)->pluck('id') as $id) {
            $full = (int) DB::table('oil_order_items')->where('order_id', $id)->sum(DB::raw('unit_price_cents * quantity'));
            DB::table('oil_orders')->where('id', $id)->update(['total_cents' => (int) round($full * 0.8)]);
        }
    }

    public function down(): void
    {
        // Pas de retour au tarif à −50 %.
    }
};
