<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;

/**
 * Passport remplace Sanctum : il gère à la fois l'OAuth du connecteur Claude (MCP)
 * et les jetons API personnels (Claude Code). Les anciens jetons Sanctum sont supprimés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('personal_access_tokens');

        // Client « jetons personnels » requis par $user->createToken().
        $clients = app(ClientRepository::class);
        try {
            $clients->personalAccessClient('users');
        } catch (RuntimeException) {
            $clients->createPersonalAccessGrantClient('Jetons API', 'users');
        }
    }
};
