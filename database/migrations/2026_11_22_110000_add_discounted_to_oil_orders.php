<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// « Tarif réduit » d'une commande d'huile (coché dans l'admin) : −50 % sur toute la commande.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oil_orders', function (Blueprint $table) {
            $table->boolean('discounted')->default(false)->after('total_cents');
        });
    }

    public function down(): void
    {
        Schema::table('oil_orders', function (Blueprint $table) {
            $table->dropColumn('discounted');
        });
    }
};
