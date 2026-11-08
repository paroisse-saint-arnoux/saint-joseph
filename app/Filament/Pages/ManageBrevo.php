<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Pages\BrevoListsChart;
use App\Filament\Widgets\Pages\BrevoTransactionalChart;
use App\Services\Brevo;
use App\Support\BrevoStats;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/** Statistiques Brevo : listes de contacts (taille, croissance, historique par semaine) et emails envoyés. */
class ManageBrevo extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static ?string $navigationLabel = 'Emails et listes';

    protected static string|\UnitEnum|null $navigationGroup = 'Stats';

    protected static ?string $title = 'Emails et listes (Brevo)';

    protected static ?string $slug = 'brevo';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.manage-brevo';

    public function getViewData(): array
    {
        $brevo = app(Brevo::class);

        return [
            'configured' => $brevo->isConfigured(),
            'stats' => $brevo->isConfigured() ? BrevoStats::lists() : ['weeks' => [], 'lists' => []],
            'campaigns' => $brevo->campaigns(20),
            'transactional' => BrevoStats::transactionalTotals(30),
            'listIds' => [
                config('services.brevo.list_id') => 'Lettre d\'information du site',
                config('services.brevo.consultation_list_id') => 'Consultation sur les projets',
                config('services.brevo.croix_list_id') => 'Concours de la Croix du Pape',
            ],
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [BrevoListsChart::class, BrevoTransactionalChart::class];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualiser')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    // Les réponses de Brevo sont gardées 30 minutes : on change de génération de cache.
                    app(Brevo::class)->flushStats();
                }),
            Action::make('openBrevo')
                ->label('Ouvrir Brevo')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url('https://app.brevo.com/', shouldOpenInNewTab: true),
        ];
    }
}
