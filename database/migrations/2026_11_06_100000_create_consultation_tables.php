<?php

use App\Models\Setting;
use App\Support\Images;
use App\Support\Library;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consultation des paroissiens sur les 5 projets finalistes du concours d'architecture
 * (application mobile ouverte par QR code dans l'exposition des maquettes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('name');                       // nom du projet (affiché en titre)
            $table->string('architect');                  // nom de l'architecte
            $table->string('agency')->nullable();         // agence / groupement
            $table->string('architect_title')->nullable(); // ex. « Architecte DPLG, gérant »
            $table->string('city')->nullable();
            $table->string('architect_photo')->nullable(); // disque public
            $table->string('model_photo')->nullable();     // photo de la maquette, disque public
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('consultation_participants', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('token', 64)->unique(); // cookie qui garde la personne identifiée sur son téléphone
            $table->timestamp('brevo_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('consultation_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('consultation_participants')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('consultation_projects')->cascadeOnDelete();
            // Toutes les échelles vont de -2 à +2.
            $table->tinyInteger('overall')->nullable();
            $table->tinyInteger('aesthetic')->nullable();
            $table->tinyInteger('spiritual')->nullable();
            $table->tinyInteger('convivial')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['participant_id', 'project_id']);
        });

        Setting::put('consultation', ['open' => true]);

        $this->seedFinalists();
    }

    /** Les 5 équipes finalistes (planche du 5 novembre 2026), par ordre alphabétique des noms. */
    private function seedFinalists(): void
    {
        $finalists = [
            ['benoit-andrier', 'Benoît Andrier', 'Sterenn Architectes', 'Architecte DPLG, gérant', 'Paris · Caen'],
            ['jean-baptiste-duthilleul', 'Jean-Baptiste Duthilleul', 'Duthilleul — Artemis', 'Architecte, ingénieur et designer', 'Paris · Le Rouret'],
            ['fabien-gauret', 'Fabien Gauret', 'Combas Architectes', 'Architecte HMONP', 'Nice'],
            ['helene-houpert', 'Hélène Houpert', 'Architecture Écologique', 'Architecte mandataire', 'Nort-sur-Erdre'],
            ['antoine-pelissier', 'Antoine Pélissier', 'APAW', 'Architecte fondateur', 'Paris'],
        ];

        foreach ($finalists as $i => [$file, $architect, $agency, $title, $city]) {
            $photo = null;
            if (! app()->runningUnitTests()) {
                $source = database_path("seeders/assets/finalistes/{$file}.jpg");
                Library::addQuietly($source, "{$file}.jpg", $architect, "Portrait de {$architect}");
                $photo = Images::store($source, 'consultation', 800);
            }

            DB::table('consultation_projects')->insert([
                'position' => $i + 1,
                'name' => $agency,
                'architect' => $architect,
                'agency' => $agency,
                'architect_title' => $title,
                'city' => $city,
                'architect_photo' => $photo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_votes');
        Schema::dropIfExists('consultation_participants');
        Schema::dropIfExists('consultation_projects');
        DB::table('settings')->where('key', 'consultation')->delete();
    }
};
