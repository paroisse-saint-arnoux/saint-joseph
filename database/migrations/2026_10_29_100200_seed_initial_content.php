<?php

use Database\Seeders\InitialContentSeeder;
use Illuminate\Database\Migrations\Migration;

/*
 * Pas d'accès SSH en production : le contenu initial (admin, réglages, articles
 * migrés de l'ancien site) est posé par une migration, qui ne s'exécute qu'une fois.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        (new InitialContentSeeder)->run();
    }
};
