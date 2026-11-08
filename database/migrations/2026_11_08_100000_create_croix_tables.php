<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Concours S.O.S Calvaires « Que va devenir la Croix du Pape ? » : page /croix,
 * suivi des visiteurs (lien → coordonnées → clic vers le vote) et inscriptions envoyées à Brevo (liste 7).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Un navigateur = une ligne (cookie « croix »). Les trois dates forment l'entonnoir suivi dans l'admin.
        Schema::create('croix_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('source', 32)->nullable(); // ?SOURCE=affiche|messe|whatsapp|mail|… (première arrivée)
            $table->unsignedInteger('visits')->default(0);
            $table->timestamp('visited_at')->nullable();      // première visite de la page /croix
            $table->timestamp('signed_up_at')->nullable();    // a laissé ses coordonnées
            $table->timestamp('vote_clicked_at')->nullable(); // a cliqué vers le site de vote
            $table->timestamps();
        });

        Schema::create('croix_signups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->nullable()->constrained('croix_visitors')->nullOnDelete();
            $table->string('email')->unique();
            $table->string('first_name', 80)->nullable();
            $table->string('last_name', 80)->nullable();
            $table->string('phone', 20)->nullable(); // format international (+33…)
            $table->boolean('consent_sms')->default(false);
            $table->string('source', 32)->nullable();
            $table->timestamp('brevo_synced_at')->nullable();
            $table->timestamps();
        });

        // Lundi 9 novembre 0 h : le vote ouvre (bouton « Je vote maintenant », formulaire → site de vote, bandeau sur l'accueil).
        Setting::put('croix', [
            'vote_url' => 'https://vote.soscalvaires.org/',
            'opens_at' => '2026-11-09 00:00',
            'closes_at' => '2026-11-30 23:59',
            'home_banner' => true,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('croix_signups');
        Schema::dropIfExists('croix_visitors');
        Setting::where('key', 'croix')->delete();
    }
};
