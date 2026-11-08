<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Brevo
{
    /** Ajoute (ou met à jour) un contact dans la liste newsletter. Pas de double opt-in (choix du projet). */
    public function subscribe(string $email, string $firstName, string $lastName): bool
    {
        return $this->addContact($email, config('services.brevo.list_id'), ['PRENOM' => $firstName, 'NOM' => $lastName]);
    }

    /** Ajoute (ou met à jour) un contact dans une liste. Les listes dont il fait déjà partie sont conservées. */
    public function addContact(string $email, int $listId, array $attributes = []): bool
    {
        $response = Http::withHeaders(['api-key' => config('services.brevo.key')])
            ->acceptJson()
            ->timeout(10)
            ->post('https://api.brevo.com/v3/contacts', array_filter([
                'email' => $email,
                'attributes' => $attributes ?: null,
                'listIds' => [$listId],
                'updateEnabled' => true,
            ]));

        if ($response->failed()) {
            Log::warning('Inscription Brevo échouée', ['list' => $listId, 'status' => $response->status(), 'body' => $response->body()]);
        }

        return $response->successful();
    }
}
