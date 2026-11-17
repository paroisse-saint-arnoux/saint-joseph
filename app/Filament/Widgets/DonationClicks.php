<?php

namespace App\Filament\Widgets;

use App\Services\Matomo;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * Tableau de bord : clics sur les boutons « Faire un don » (événements Matomo de catégorie « Don »,
 * tous emplacements confondus) et dons effectués dans le formulaire intégré à /don (catégorie « Don effectué »),
 * jour par jour, semaine par semaine ou mois par mois.
 */
class DonationClicks extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Clics sur « Faire un don » et dons effectués';

    protected ?string $maxHeight = '260px';

    public ?string $filter = '30';

    /** Filtre => [période Matomo, nombre de périodes]. */
    private const RANGES = ['30' => ['day', 30], '90' => ['week', 13], '365' => ['month', 12]];

    protected function getFilters(): ?array
    {
        return ['30' => '30 jours', '90' => '3 mois (par semaine)', '365' => '12 mois (par mois)'];
    }

    public function getDescription(): ?string
    {
        $matomo = app(Matomo::class);
        if (! $matomo->isConfigured()) {
            return 'Statistiques non configurées : ajoutez MATOMO_TOKEN dans le fichier .env du serveur.';
        }

        $data = $this->clicks();
        $clicks = array_sum(array_column($data, 'clicks'));
        $visits = array_sum(array_column($data, 'visits'));
        $donations = array_sum(array_column($data, 'donations'));
        $fmt = fn (int $n) => number_format($n, 0, ',', ' ');

        $text = $clicks === 0
            ? 'Aucun clic sur la période (suivi en place depuis le 10/11/2026).'
            : sprintf('%s clic%s sur la période, au cours de %s visite%s.', $fmt($clicks), $clicks > 1 ? 's' : '', $fmt($visits), $visits > 1 ? 's' : '');

        return $text.' '.($donations === 0
            ? 'Aucun don effectué dans le formulaire de la page de don (suivi en place depuis le 17/11/2026).'
            : sprintf('%s don%s effectué%s dans le formulaire de la page de don.', $fmt($donations), $donations > 1 ? 's' : '', $donations > 1 ? 's' : ''))
            .' Les dons faits directement sur le site du diocèse ne sont pas comptés.';
    }

    protected function getData(): array
    {
        $data = $this->clicks();

        return [
            'datasets' => [[
                'label' => 'Clics',
                'data' => array_values(array_column($data, 'clicks')),
                'backgroundColor' => '#C5A24B',
                'borderRadius' => 3,
            ], [
                'label' => 'Dons effectués',
                'data' => array_values(array_column($data, 'donations')),
                'backgroundColor' => '#00204E',
                'borderRadius' => 3,
            ]],
            'labels' => array_map(fn ($key) => $this->label((string) $key), array_keys($data)),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => true, 'position' => 'bottom']],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /** @return array<string, array{clicks: int, visits: int, donations: int}> */
    private function clicks(): array
    {
        [$period, $count] = self::RANGES[$this->filter] ?? self::RANGES['30'];

        return app(Matomo::class)->donationClicks($period, $count);
    }

    /** Libellé d'une période Matomo : « 10 nov. », « sem. du 2 nov. », « nov. 2026 ». */
    private function label(string $key): string
    {
        return match (true) {
            str_contains($key, ',') => 'sem. du '.Carbon::parse(explode(',', $key)[0])->translatedFormat('j M'),
            strlen($key) === 7 => Carbon::parse($key.'-01')->translatedFormat('M Y'),
            default => Carbon::parse($key)->translatedFormat('j M'),
        };
    }
}
