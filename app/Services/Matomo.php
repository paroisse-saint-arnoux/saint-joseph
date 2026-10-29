<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Statistiques de fréquentation (Matomo auto-hébergé) pour le tableau de bord de l'admin.
 * API de rapports, jeton envoyé en POST (recommandé par Matomo). Résultats en cache 30 minutes.
 */
class Matomo
{
    private const CACHE_MINUTES = 30;

    public function isConfigured(): bool
    {
        return filled(config('services.matomo.url')) && filled(config('services.matomo.site_id')) && filled(config('services.matomo.token'));
    }

    /**
     * Visites, visiteurs uniques et pages vues, jour par jour, sur les $days derniers jours (aujourd'hui compris).
     *
     * @return array<string, array{visits: int, visitors: int, pageviews: int}> clé = date AAAA-MM-JJ
     */
    public function dailyVisits(int $days = 30): array
    {
        $data = $this->call('VisitsSummary.get', ['period' => 'day', 'date' => 'last'.$days]);

        return collect($data)->map(fn ($day) => [
            'visits' => (int) ($day['nb_visits'] ?? 0),
            'visitors' => (int) ($day['nb_uniq_visitors'] ?? 0),
            'pageviews' => (int) ($day['nb_actions'] ?? 0),
        ])->all();
    }

    /**
     * Pages les plus vues sur les $days derniers jours.
     *
     * @return array<int, array{label: string, url: ?string, pageviews: int}>
     */
    public function topPages(int $days = 30, int $limit = 5): array
    {
        $data = $this->call('Actions.getPageTitles', [
            'period' => 'range',
            'date' => Carbon::today()->subDays($days - 1)->toDateString().','.Carbon::today()->toDateString(),
            'flat' => 1,
            'filter_limit' => $limit,
            'filter_sort_column' => 'nb_hits',
        ]);

        return collect($data)->map(fn ($row) => [
            'label' => trim((string) ($row['label'] ?? '')),
            'url' => $row['url'] ?? null,
            'pageviews' => (int) ($row['nb_hits'] ?? 0),
        ])->all();
    }

    private function call(string $method, array $params): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $key = 'matomo:'.md5($method.json_encode($params));
        if (is_array($cached = Cache::get($key))) {
            return $cached;
        }

        $response = Http::asForm()->timeout(10)->post(rtrim(config('services.matomo.url'), '/').'/index.php', [
            'module' => 'API',
            'method' => $method,
            'idSite' => config('services.matomo.site_id'),
            'format' => 'JSON',
            'token_auth' => config('services.matomo.token'),
            ...$params,
        ]);

        $data = $response->json();
        if (! $response->successful() || ! is_array($data) || ($data['result'] ?? null) === 'error') {
            // Pas de mise en cache : on réessaiera au prochain affichage.
            report(new \RuntimeException('Matomo : '.($data['message'] ?? 'réponse invalide ('.$response->status().')')));

            return [];
        }

        Cache::put($key, $data, now()->addMinutes(self::CACHE_MINUTES));

        return $data;
    }
}
