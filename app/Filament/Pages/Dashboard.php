<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/** Tableau de bord (statistiques Matomo, derniers articles), rangé dans le menu « Stats » sous le nom « Visites ». */
class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Visites';

    protected static string|\UnitEnum|null $navigationGroup = 'Stats';

    protected static ?int $navigationSort = 1;
}
