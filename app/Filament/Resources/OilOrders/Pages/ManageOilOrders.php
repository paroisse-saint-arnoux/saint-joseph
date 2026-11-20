<?php

namespace App\Filament\Resources\OilOrders\Pages;

use App\Filament\Resources\OilOrders\OilOrderResource;
use App\Filament\Resources\OilOrders\Widgets\OilStock;
use App\Models\OilProduct;
use App\Models\Setting;
use App\Support\OilOrders;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Utilities\Get;

/** Suivi des réservations d'huile : stocks en haut, commandes en dessous ; stocks, réglages et export dans l'en-tête. */
class ManageOilOrders extends ManageRecords
{
    protected static string $resource = OilOrderResource::class;

    protected ?string $heading = 'Commandes d\'huile d\'olive';

    protected function getHeaderWidgets(): array
    {
        return [OilStock::class];
    }

    protected function getHeaderActions(): array
    {
        return [
            // On saisit le nombre de bouteilles ajoutées (+) ou enlevées (−) ; le nouveau stock est calculé et affiché au fil de la saisie.
            Action::make('stocks')
                ->label('Modifier les stocks')
                ->icon('heroicon-o-archive-box')
                ->modalDescription('Indiquez le nombre de bouteilles à ajouter au stock (par exemple 12) ou à enlever (par exemple -3, pour une bouteille cassée ou donnée). Laissez 0 pour ne rien changer.')
                ->fillForm(fn () => OilOrders::stockSummary()->mapWithKeys(fn (array $row) => ['add_'.$row['product']->id => 0])->all())
                ->schema(fn () => OilOrders::stockSummary()->map(fn (array $row) => TextInput::make('add_'.$row['product']->id)
                    ->label($row['product']->label.' · '.$row['on_hand'].' en stock')
                    ->numeric()->integer()->required()->default(0)
                    ->minValue(-$row['stock'])->maxValue(10000)
                    ->prefix('+ / −')->suffix('bouteilles')
                    ->live(debounce: 400)
                    ->helperText(function (Get $get) use ($row) {
                        $add = (int) $get('add_'.$row['product']->id);

                        return 'Nouveau stock : '.($row['on_hand'] + $add)
                            .' · après les commandes à retirer : '.($row['stock'] + $add);
                    })
                    ->validationMessages(['min' => 'On ne peut pas enlever plus de '.$row['stock'].' bouteilles : les autres sont réservées.']))->all())
                ->modalSubmitActionLabel('Enregistrer')
                ->action(function (array $data) {
                    foreach (OilOrders::stockSummary() as $row) {
                        $add = (int) ($data['add_'.$row['product']->id] ?? 0);
                        if ($add !== 0) {
                            OilProduct::whereKey($row['product']->id)->update(['stock' => max(0, $row['stock'] + $add)]);
                        }
                    }
                    Notification::make()->title('Stocks enregistrés')->success()->send();
                    $this->dispatch('oil-orders-updated');
                }),
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => response()->download(OilOrders::export(), 'commandes-huile-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend()),
            Action::make('settings')
                ->label('Réglages')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->fillForm(fn () => OilOrders::settings())
                ->schema([
                    Toggle::make('open')->label('Réservations ouvertes')
                        ->helperText('Fermées : la page affiche « Les réservations sont closes » et /huile ne propose plus de réserver.'),
                    TextInput::make('pickup')->label('Retrait des bouteilles')->required()->maxLength(190)
                        ->prefix('À retirer')
                        ->helperText('Repris sur la page, la confirmation et l\'e-mail.'),
                    TextInput::make('payment')->label('Règlement')->required()->maxLength(190),
                ])
                ->modalSubmitActionLabel('Enregistrer')
                ->action(function (array $data) {
                    Setting::put('commande_huile', ['open' => (bool) $data['open'], 'pickup' => trim($data['pickup']), 'payment' => trim($data['payment'])]);
                    Notification::make()->title('Réglages enregistrés')->success()->send();
                    $this->dispatch('oil-orders-updated');
                }),
            Action::make('openPage')
                ->label('Ouvrir la page')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(route('huile.order'), shouldOpenInNewTab: true),
        ];
    }
}
