<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Original avant la dernière retouche (recadrage, rotation), pour pouvoir l'annuler. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_images', function (Blueprint $table) {
            $table->string('previous_source_path')->nullable()->after('source_path');
        });
    }

    public function down(): void
    {
        Schema::table('library_images', function (Blueprint $table) {
            $table->dropColumn('previous_source_path');
        });
    }
};
