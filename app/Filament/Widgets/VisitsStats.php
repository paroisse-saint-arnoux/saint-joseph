<?php

namespace App\Filament\Widgets;

use App\Services\Matomo;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Tableau de bord : chiffres clés de fréquentation des 30 derniers jours (Matomo), comparés aux 30 jours précédents. */
class VisitsStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Fréquentation du site (30 derniers jours)';

    protected function getStats(): array
    {
        $matomo = app(Matomo::class);
        if (! $matomo->isConfigured()) {
            return [
                Stat::make('Statistiques', 'Non configurées')
                    ->description('Ajoutez MATOMO_TOKEN (clé d\'API Matomo) dans le fichier .env du serveur.')
                    ->color('gray'),
            ];
        }

        $days = $matomo->dailyVisits(60);
        if ($days === []) {
            return [Stat::make('Statistiques', 'Indisponibles')->description('Matomo ne répond pas pour le moment.')->color('gray')];
        }

        [$previous, $current] = array_chunk($days, 30, preserve_keys: true) + [[], []];

        return [
            $this->stat('Visites', $current, $previous, 'visits'),
            $this->stat('Visiteurs', $current, $previous, 'visitors'),
            $this->stat('Pages vues', $current, $previous, 'pageviews'),
        ];
    }

    private function stat(string $label, array $current, array $previous, string $key): Stat
    {
        $now = array_sum(array_column($current, $key));
        $before = array_sum(array_column($previous, $key));
        $change = $before > 0 ? (int) round(($now - $before) / $before * 100) : null;

        return Stat::make($label, number_format($now, 0, ',', ' '))
            ->description($change === null ? 'Pas de comparaison possible' : ($change >= 0 ? '+' : '').$change.' % par rapport aux 30 jours précédents')
            ->descriptionIcon($change === null ? null : ($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'))
            ->color($change === null ? 'gray' : ($change >= 0 ? 'success' : 'warning'))
            ->chart(array_values(array_column($current, $key)));
    }
}
