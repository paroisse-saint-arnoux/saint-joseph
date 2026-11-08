<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Brevo
{
    private const API = 'https://api.brevo.com/v3/';

    private const CACHE_MINUTES = 30;

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
            ->post(self::API.'contacts', array_filter([
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

    // --- Statistiques (page « Emails & listes » de l'admin) — résultats en cache 30 minutes, erreurs non mises en cache.

    public function isConfigured(): bool
    {
        return filled(config('services.brevo.key'));
    }

    /**
     * Listes de contacts.
     *
     * @return array<int, array{id: int, name: string, subscribers: int, blacklisted: int}>
     */
    public function lists(): array
    {
        $lists = [];
        for ($offset = 0; $offset < 500; $offset += 50) {
            $page = $this->get('contacts/lists', ['limit' => 50, 'offset' => $offset, 'sort' => 'asc'])['lists'] ?? [];
            $lists = [...$lists, ...$page];
            if (count($page) < 50) {
                break;
            }
        }

        return collect($lists)->map(fn ($list) => [
            'id' => (int) $list['id'],
            'name' => (string) ($list['name'] ?? ''),
            'subscribers' => (int) ($list['uniqueSubscribers'] ?? $list['totalSubscribers'] ?? 0),
            'blacklisted' => (int) ($list['totalBlacklisted'] ?? 0),
        ])->sortBy('id')->values()->all();
    }

    /**
     * Contacts créés depuis une date, avec leurs listes (pour l'historique des inscriptions par semaine).
     *
     * @return array<int, array{created_at: Carbon, lists: array<int>}>
     */
    public function contactsCreatedSince(Carbon $since): array
    {
        $contacts = [];
        for ($offset = 0; $offset < 20000; $offset += 1000) {
            $page = $this->get('contacts', [
                'limit' => 1000, 'offset' => $offset, 'sort' => 'desc',
                'createdSince' => $since->copy()->utc()->format('Y-m-d\TH:i:s.v\Z'),
            ])['contacts'] ?? [];
            foreach ($page as $contact) {
                $contacts[] = ['created_at' => Carbon::parse($contact['createdAt'])->setTimezone(config('app.timezone')), 'lists' => array_map('intval', $contact['listIds'] ?? [])];
            }
            if (count($page) < 1000) {
                break;
            }
        }

        return $contacts;
    }

    /**
     * Dernières campagnes envoyées, avec leurs statistiques globales.
     *
     * @return array<int, array{id: int, name: string, subject: string, sent_at: ?Carbon, sent: int, delivered: int, opens: int, clicks: int, unsubscriptions: int, bounces: int, complaints: int}>
     */
    public function campaigns(int $limit = 20): array
    {
        $data = $this->get('emailCampaigns', [
            'status' => 'sent', 'statistics' => 'globalStats', 'limit' => $limit, 'offset' => 0, 'sort' => 'desc', 'excludeHtmlContent' => 'true',
        ]);

        return collect($data['campaigns'] ?? [])->map(function ($c) {
            $s = $c['statistics']['globalStats'] ?? [];

            return [
                'id' => (int) $c['id'],
                'name' => (string) ($c['name'] ?? ''),
                'subject' => (string) ($c['subject'] ?? ''),
                'sent_at' => filled($c['sentDate'] ?? null) ? Carbon::parse($c['sentDate'])->setTimezone(config('app.timezone')) : null,
                'sent' => (int) ($s['sent'] ?? 0),
                'delivered' => (int) ($s['delivered'] ?? 0),
                'opens' => (int) ($s['uniqueViews'] ?? 0),
                'clicks' => (int) ($s['uniqueClicks'] ?? 0),
                'unsubscriptions' => (int) ($s['unsubscriptions'] ?? 0),
                'bounces' => (int) ($s['hardBounces'] ?? 0) + (int) ($s['softBounces'] ?? 0),
                'complaints' => (int) ($s['complaints'] ?? 0),
            ];
        })->all();
    }

    /**
     * Emails transactionnels (liens de connexion, formulaire de contact…) sur les $days derniers jours, jour par jour.
     *
     * @return array<string, array{requests: int, delivered: int, opens: int, clicks: int, bounces: int, blocked: int, spam: int}>
     */
    public function transactionalDaily(int $days = 30): array
    {
        $data = $this->get('smtp/statistics/reports', ['days' => $days, 'sort' => 'asc']);

        return collect($data['reports'] ?? [])->mapWithKeys(fn ($r) => [(string) $r['date'] => [
            'requests' => (int) ($r['requests'] ?? 0),
            'delivered' => (int) ($r['delivered'] ?? 0),
            'opens' => (int) ($r['uniqueOpens'] ?? 0),
            'clicks' => (int) ($r['uniqueClicks'] ?? 0),
            'bounces' => (int) ($r['hardBounces'] ?? 0) + (int) ($r['softBounces'] ?? 0),
            'blocked' => (int) ($r['blocked'] ?? 0),
            'spam' => (int) ($r['spamReports'] ?? 0),
        ]])->all();
    }

    /** Oublie les statistiques en cache (bouton « Actualiser »). */
    public function flushStats(): void
    {
        Cache::forever('brevo:generation', (int) Cache::get('brevo:generation', 0) + 1);
    }

    private function get(string $path, array $query = []): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $key = 'brevo:'.Cache::get('brevo:generation', 0).':'.md5($path.json_encode($query));
        if (is_array($cached = Cache::get($key))) {
            return $cached;
        }

        $response = Http::withHeaders(['api-key' => config('services.brevo.key')])->acceptJson()->timeout(15)->get(self::API.$path, $query);
        $data = $response->json();
        if (! $response->successful() || ! is_array($data)) {
            report(new \RuntimeException('Brevo '.$path.' : '.($data['message'] ?? 'réponse invalide ('.$response->status().')')));

            return [];
        }

        Cache::put($key, $data, now()->addMinutes(self::CACHE_MINUTES));

        return $data;
    }
}
