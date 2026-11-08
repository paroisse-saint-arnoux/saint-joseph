<?php

namespace App\Filament\Widgets\Pages;

use App\Support\Croix;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/** Page « Croix du Pape » : visiteurs, coordonnées et clics vers le vote, jour par jour. */
class CroixDailyChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Jour par jour';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $days = Croix::daily(30);
        $dataset = fn (string $label, string $key, string $color) => [
            'label' => $label, 'data' => array_values(array_column($days, $key)), 'backgroundColor' => $color, 'borderColor' => $color,
        ];

        return [
            'datasets' => [
                $dataset('Visiteurs de la page', 'visitors', '#00204E'),
                $dataset('Coordonnées laissées', 'signups', '#C5A24B'),
                $dataset('Clics vers le vote', 'votes', '#6E7A5A'),
            ],
            'labels' => array_map(fn (string $date) => Carbon::parse($date)->translatedFormat('D j M'), array_keys($days)),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
