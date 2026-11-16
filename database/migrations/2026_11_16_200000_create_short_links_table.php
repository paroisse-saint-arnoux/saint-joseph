<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Liens courts (sj.saint-arnoux.fr/xxxxx) créés par la page /qrcodes : chacun renvoie vers une page du site
// avec ses balises UTM. Aucune visite n'est enregistrée ici (Matomo compte la page d'arrivée).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->text('url');
            $table->char('url_hash', 64)->unique(); // même lien complet = même code
            $table->string('target');
            $table->string('utm_source', 60);
            $table->string('utm_medium', 60);
            $table->string('utm_campaign', 60);
            $table->string('utm_content', 60)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_links');
    }
};
