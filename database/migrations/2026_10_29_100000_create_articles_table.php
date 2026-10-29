<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category');
            $table->text('excerpt')->nullable();
            // markdown : écrit dans l'admin ; html : généré (IA) et collé tel quel.
            $table->string('format')->default('markdown');
            $table->longText('body')->nullable();
            $table->string('og_image')->nullable();
            $table->string('og_image_alt')->nullable();
            // [{path, alt}] — carrousel affiché en fin d'article.
            $table->json('gallery')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            // Jeton du lien de prévisualisation des brouillons.
            $table->string('preview_token', 40)->unique();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
