<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Réservation de bouteilles d'huile d'olive (page /commande-huile) : produits avec leur stock restant,
// commandes et lignes de commande. Les bouteilles sont retirées (et réglées) à la kermesse.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oil_products', function (Blueprint $table) {
            $table->id();
            $table->string('label', 60);                  // « 250 mL »
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('stock')->default(0); // bouteilles encore disponibles à la réservation
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('oil_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->nullable()->unique(); // H-001, posé après l'insertion
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email', 190)->index();
            $table->string('phone', 30);                   // format international E.164
            $table->unsignedInteger('total_cents');
            $table->string('status', 20)->default('reserved')->index();
            $table->text('notes')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('confirmation_sent_at')->nullable();
            $table->timestamp('brevo_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('oil_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('oil_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('oil_products')->nullOnDelete();
            $table->string('label', 60);                   // copie au moment de la commande
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();
        });

        $now = now();
        DB::table('oil_products')->insert([
            ['label' => '250 mL', 'price_cents' => 1700, 'stock' => 50, 'position' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['label' => '500 mL', 'price_cents' => 3000, 'stock' => 100, 'position' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['label' => '750 mL', 'price_cents' => 4000, 'stock' => 20, 'position' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Setting::put('commande_huile', [
            'open' => true,
            'pickup' => 'à la kermesse du dimanche 29 novembre 2026, salle Charvet',
            'payment' => 'Le règlement se fait au moment du retrait.',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('oil_order_items');
        Schema::dropIfExists('oil_orders');
        Schema::dropIfExists('oil_products');
        DB::table('settings')->where('key', 'commande_huile')->delete();
    }
};
