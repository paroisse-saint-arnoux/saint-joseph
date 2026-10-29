<?php

namespace App\Filament\Widgets;

use App\Services\Matomo;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/** Tableau de bord : visites et pages vues jour par jour (Matomo). */
class VisitsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Visites des derniers jours';

    protected ?string $maxHeight = '300px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return ['7' => '7 jours', '30' => '30 jours', '90' => '3 mois'];
    }

    public function getDescription(): ?string
    {
        return app(Matomo::class)->isConfigured() ? null : 'Statistiques non configurées : ajoutez MATOMO_TOKEN dans le fichier .env du serveur.';
    }

    protected function getData(): array
    {
        $days = app(Matomo::class)->dailyVisits((int) $this->filter);

        return [
            'datasets' => [
                [
                    'label' => 'Visites',
                    'data' => array_values(array_column($days, 'visits')),
                    'borderColor' => '#00204E',
                    'backgroundColor' => 'rgba(0, 32, 78, .08)',
                    'fill' => true,
                    'tension' => .3,
                ],
                [
                    'label' => 'Pages vues',
                    'data' => array_values(array_column($days, 'pageviews')),
                    'borderColor' => '#C5A24B',
                    'backgroundColor' => 'transparent',
                    'tension' => .3,
                ],
            ],
            'labels' => array_map(fn (string $date) => Carbon::parse($date)->translatedFormat('j M'), array_keys($days)),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
