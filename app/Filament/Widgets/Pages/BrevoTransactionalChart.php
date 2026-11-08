<?php

namespace App\Filament\Widgets\Pages;

use App\Services\Brevo;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/** Page « Emails et listes » : emails transactionnels (liens de connexion, formulaire de contact…) jour par jour. */
class BrevoTransactionalChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Emails transactionnels (30 derniers jours)';

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $days = app(Brevo::class)->transactionalDaily(30);
        $dataset = fn (string $label, string $key, string $color) => [
            'label' => $label, 'data' => array_values(array_column($days, $key)), 'borderColor' => $color, 'backgroundColor' => 'transparent', 'tension' => .3,
        ];

        return [
            'datasets' => [
                $dataset('Envoyés', 'requests', '#00204E'),
                $dataset('Délivrés', 'delivered', '#6E7A5A'),
                $dataset('Ouverts', 'opens', '#C5A24B'),
                $dataset('Rebonds', 'bounces', '#A8432F'),
            ],
            'labels' => array_map(fn (string $date) => Carbon::parse($date)->translatedFormat('j M'), array_keys($days)),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
