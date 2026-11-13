<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Chiffres de la campagne de dons RGIVE du diocèse (don-nice.catholique.fr).
 *
 * Source : l'adresse publique (sans clé) que la page de la campagne utilise pour son compteur,
 * `/api/campaign-widgets-data/{uuid}/` — non documentée par RGIVE, elle peut changer : le site garde
 * alors le dernier chiffre connu.
 * Mise à jour au plus toutes les 5 minutes (cache) ; un échec n'est retenté qu'après 1 minute.
 */
class Rgive
{
    private const CACHE_KEY = 'rgive.campaign';

    private const LAST_KEY = 'rgive.campaign.last'; // dernier résultat valide, sans expiration

    private const CACHE_SECONDS = 300;

    private const RETRY_SECONDS = 60;

    /**
     * @return array{raised: int, contributors: int, donors: list<array{name: string, city: ?string, amount: int}>}|null
     */
    public function campaign(): ?array
    {
        $url = config('services.rgive.widget_url');
        if (blank($url)) {
            return null;
        }

        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            return $cached ?: null; // [] = échec récent, on ne réessaie pas tout de suite
        }

        $data = $this->fetch($url);
        if ($data !== null) {
            Cache::put(self::CACHE_KEY, $data, self::CACHE_SECONDS);
            Cache::forever(self::LAST_KEY, $data);

            return $data;
        }

        $last = Cache::get(self::LAST_KEY);
        Cache::put(self::CACHE_KEY, $last ?? [], self::RETRY_SECONDS);

        return $last;
    }

    /** Montant collecté affiché par le site : total RGIVE (0 si RGIVE n'a encore jamais répondu). */
    public function raised(): int
    {
        return $this->campaign()['raised'] ?? 0;
    }

    /** @return list<array{name: string, city: ?string, amount: int}> */
    public function latestDonors(): array
    {
        return $this->campaign()['donors'] ?? [];
    }

    private function fetch(string $url): ?array
    {
        try {
            $response = Http::timeout(4)->acceptJson()->get($url);
            $counter = $response->json('live_counter');
            if (! $response->successful() || ! is_array($counter) || ! is_numeric($counter['total_amount'] ?? null)) {
                Log::warning('RGIVE : réponse inattendue', ['status' => $response->status()]);

                return null;
            }
        } catch (Throwable $e) {
            Log::warning('RGIVE injoignable : '.$e->getMessage());

            return null;
        }

        $donors = collect($response->json('latest_donors.items') ?? [])
            ->filter(fn ($d) => is_array($d) && filled($d['firstname'] ?? null) && is_numeric($d['amount'] ?? null))
            ->map(fn ($d) => [
                'name' => $this->tidy($d['firstname']),
                'city' => filled($d['city'] ?? null) ? $this->tidy($d['city']) : null,
                'amount' => (int) round($d['amount']),
            ])
            ->values()->all();

        return [
            'raised' => (int) round($counter['total_amount']),
            'contributors' => (int) ($counter['total_contributors'] ?? 0),
            'donors' => $donors,
        ];
    }

    /** « MICHEL » / « simone » / « LA GAUDE » → « Michel » / « Simone » / « La Gaude ». */
    private function tidy(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return mb_substr(mb_convert_case(mb_strtolower($text), MB_CASE_TITLE), 0, 60);
    }
}
