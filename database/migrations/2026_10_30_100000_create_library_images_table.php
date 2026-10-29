<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bibliothèque d'images : les sources (originaux, privés) dans lesquelles on pioche pour les articles.
 * Les articles n'utilisent jamais une source directement mais une copie retraitée (WebP).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_images', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('alt')->nullable();
            $table->string('original_name')->nullable();
            $table->string('source_path');
            $table->string('thumb_path');
            $table->string('mime', 40);
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64)->index();
            // Empreintes perceptuelles (64 bits sous forme de 0/1) pour reconnaître une même image dans un autre format.
            $table->char('dhash', 64);
            $table->char('phash', 64);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Copies retraitées (WebP) d'une source, utilisées par les pages. source_sha256 = version de la source
        // ayant servi à la copie : si elle diffère de la source actuelle, la page affiche une ancienne version.
        Schema::create('library_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_image_id')->constrained()->cascadeOnDelete();
            $table->string('path')->unique();
            $table->unsignedInteger('max_width');
            $table->char('source_sha256', 64);
            $table->timestamps();
        });

        // Index des pages qui utilisent chaque copie (recalculé à chaque enregistrement d'un article ou de l'accueil).
        Schema::create('library_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_copy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('library_image_id')->constrained()->cascadeOnDelete();
            $table->string('page_type', 20); // article | home
            $table->foreignId('article_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('field', 20); // og_image | gallery | body | home
            $table->timestamps();
            $table->index(['page_type', 'article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_usages');
        Schema::dropIfExists('library_copies');
        Schema::dropIfExists('library_images');
    }
};
