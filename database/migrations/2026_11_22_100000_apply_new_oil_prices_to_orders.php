<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Les commandes passées avant la baisse des prix du 21/11/2026 (migration lower_oil_prices)
// passent aussi aux nouveaux prix : lignes au prix 17 / 30 / 40 € → 13 / 23 / 30 €, total recalculé.
return new class extends Migration
{
    private const PRICES = ['250 mL' => [1700, 1300], '500 mL' => [3000, 2300], '750 mL' => [4000, 3000]];

    public function up(): void
    {
        DB::transaction(function () {
            $orderIds = collect();

            foreach (self::PRICES as $label => [$old, $new]) {
                $items = DB::table('oil_order_items')->where('label', $label)->where('unit_price_cents', $old);
                $orderIds = $orderIds->merge((clone $items)->pluck('order_id'));
                $items->update(['unit_price_cents' => $new, 'updated_at' => now()]);
            }

            foreach ($orderIds->unique() as $orderId) {
                $total = DB::table('oil_order_items')->where('order_id', $orderId)
                    ->sum(DB::raw('unit_price_cents * quantity'));
                DB::table('oil_orders')->where('id', $orderId)->update(['total_cents' => (int) $total]);
            }
        });
    }

    public function down(): void
    {
        // Pas de retour en arrière : les anciens prix ne sont plus pratiqués.
    }
};
