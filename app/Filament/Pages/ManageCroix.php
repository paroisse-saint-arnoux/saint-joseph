<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Pages\CroixDailyChart;
use App\Models\CroixSignup;
use App\Models\Setting;
use App\Support\Croix;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/** Concours S.O.S Calvaires « Que va devenir la Croix du Pape ? » : suivi de la page /croix (visites → coordonnées → vote). */
class ManageCroix extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Croix du Pape';

    protected static string|\UnitEnum|null $navigationGroup = 'Stats';

    protected static ?string $title = 'Concours de la Croix du Pape';

    protected static ?string $slug = 'croix';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.manage-croix';

    public function getViewData(): array
    {
        return [
            'state' => Croix::state(),
            'opensAt' => Croix::opensAt(),
            'closesAt' => Croix::closesAt(),
            'funnel' => Croix::funnel(),
            'signups' => CroixSignup::latest()->limit(100)->get(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [CroixDailyChart::class];
    }

    protected function getHeaderActions(): array
    {
        $unsynced = CroixSignup::whereNull('brevo_synced_at')->count();

        return [
            Action::make('openPage')
                ->label('Ouvrir la page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(route('croix'), shouldOpenInNewTab: true),
            Action::make('sync')
                ->label('Renvoyer vers Brevo ('.$unsynced.')')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible($unsynced > 0)
                ->action(function () {
                    $ok = CroixSignup::whereNull('brevo_synced_at')->get()->filter(fn ($s) => Croix::syncToBrevo($s))->count();
                    Notification::make()->title($ok.' contact(s) envoyé(s) à Brevo')->{$ok ? 'success' : 'danger'}()->send();
                }),
            Action::make('settings')
                ->label('Dates et réglages')
                ->icon('heroicon-o-cog-6-tooth')
                ->fillForm(fn () => Croix::settings())
                ->schema([
                    DateTimePicker::make('opens_at')->label('Ouverture du vote')->seconds(false)->required()
                        ->helperText('À partir de ce moment : bouton « Je vote maintenant », le formulaire renvoie vers le site de vote, bandeau sur l\'accueil.'),
                    DateTimePicker::make('closes_at')->label('Clôture du vote')->seconds(false)->required()->after('opens_at'),
                    TextInput::make('vote_url')->label('Adresse du site de vote')->url()->required(),
                    Toggle::make('home_banner')->label('Bandeau « La Croix du Pape au Rouret ? Votez » sur l\'accueil pendant le vote'),
                ])
                ->action(function (array $data) {
                    Setting::put('croix', [
                        ...$data,
                        'opens_at' => Carbon::parse($data['opens_at'])->format('Y-m-d H:i'),
                        'closes_at' => Carbon::parse($data['closes_at'])->format('Y-m-d H:i'),
                        'home_banner' => (bool) $data['home_banner'],
                    ]);
                    Notification::make()->title('Réglages enregistrés')->success()->send();
                }),
        ];
    }
}
