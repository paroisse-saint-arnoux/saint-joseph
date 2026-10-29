<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Auteur d'un article : affiché uniquement dans l'admin (pas sur le site). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('author_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        // Articles existants (contenu de départ) : attribués au premier administrateur.
        if ($adminId = User::where('role', 'admin')->orderBy('id')->value('id')) {
            DB::table('articles')->whereNull('author_id')->update(['author_id' => $adminId]);
        }
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
    }
};
