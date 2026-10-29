<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Brevo
{
    /** Ajoute (ou met à jour) un contact dans la liste newsletter. Pas de double opt-in (choix du projet). */
    public function subscribe(string $email, string $firstName, string $lastName): bool
    {
        $response = Http::withHeaders(['api-key' => config('services.brevo.key')])
            ->acceptJson()
            ->timeout(10)
            ->post('https://api.brevo.com/v3/contacts', [
                'email' => $email,
                'attributes' => ['PRENOM' => $firstName, 'NOM' => $lastName],
                'listIds' => [config('services.brevo.list_id')],
                'updateEnabled' => true,
            ]);

        if ($response->failed()) {
            Log::warning('Inscription Brevo échouée', ['status' => $response->status(), 'body' => $response->body()]);
        }

        return $response->successful();
    }
}
