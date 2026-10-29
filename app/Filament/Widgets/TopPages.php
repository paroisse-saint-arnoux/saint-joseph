<?php

namespace App\Filament\Widgets;

use App\Services\Matomo;
use Filament\Widgets\Widget;

/** Tableau de bord : pages les plus vues sur 30 jours (Matomo). */
class TopPages extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.top-pages';

    protected function getViewData(): array
    {
        $matomo = app(Matomo::class);

        return ['configured' => $matomo->isConfigured(), 'pages' => $matomo->topPages(30, 8)];
    }
}
