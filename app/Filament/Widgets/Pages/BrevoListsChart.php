<?php

namespace App\Filament\Widgets\Pages;

use App\Services\Brevo;
use App\Support\BrevoStats;
use Filament\Widgets\ChartWidget;

/** Page « Emails et listes » : taille des listes Brevo, ou nouveaux contacts, semaine par semaine. */
class BrevoListsChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Listes, semaine par semaine';

    protected ?string $maxHeight = '320px';

    public ?string $filter = 'size';

    private const COLORS = ['#00204E', '#C5A24B', '#6E7A5A', '#A8432F', '#5B8BD0', '#8A8578', '#D4876A', '#2F5C9E'];

    protected function getFilters(): ?array
    {
        return ['size' => 'Nombre de contacts', 'new' => 'Nouveaux contacts'];
    }

    public function getDescription(): ?string
    {
        return 'Reconstitué d\'après la date de création des contacts dans Brevo (les désinscriptions passées n\'apparaissent pas).';
    }

    protected function getData(): array
    {
        if (! app(Brevo::class)->isConfigured()) {
            return ['datasets' => [], 'labels' => []];
        }

        $stats = BrevoStats::lists();
        $datasets = [];
        foreach (array_values($stats['lists']) as $i => $list) {
            $color = self::COLORS[$i % count(self::COLORS)];
            $datasets[] = [
                'label' => $list['name'],
                'data' => $list[$this->filter === 'new' ? 'new' : 'size'],
                'borderColor' => $color,
                'backgroundColor' => $color,
                'tension' => .3,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => array_map(fn ($week) => 'sem. du '.$week->translatedFormat('j M'), $stats['weeks']),
        ];
    }

    protected function getType(): string
    {
        return $this->filter === 'new' ? 'bar' : 'line';
    }
}
