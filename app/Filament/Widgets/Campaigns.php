<?php

namespace App\Filament\Widgets;

use App\Services\Brevo;
use App\Services\Matomo;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;

/**
 * Tableau de bord : visites venues des campagnes (Matomo), rapprochées des campagnes email Brevo de même nom
 * (Brevo ajoute utm_campaign = nom de la campagne quand le suivi Google Analytics est activé dans la campagne).
 */
class Campaigns extends Widget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.campaigns';

    protected function getViewData(): array
    {
        $matomo = app(Matomo::class);
        ['campaigns' => $campaigns, 'sources_available' => $sourcesAvailable] = $matomo->campaigns(30, 10);

        $brevo = collect(app(Brevo::class)->isConfigured() ? app(Brevo::class)->campaigns(50) : [])
            ->keyBy(fn ($c) => Str::slug($c['name']));

        return [
            'configured' => $matomo->isConfigured(),
            'sourcesAvailable' => $sourcesAvailable,
            'campaigns' => array_map(fn ($c) => $c + ['brevo' => $brevo->get(Str::slug($c['label']))], $campaigns),
        ];
    }
}
