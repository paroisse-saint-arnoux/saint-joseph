<?php

namespace App\Filament\Resources\OilOrders\Widgets;

use App\Enums\OilOrderStatus;
use App\Models\OilOrder;
use App\Support\OilOrders;
use Filament\Widgets\Widget;

/** En-tête des commandes d'huile : par format, stock restant, bouteilles à retirer et retirées ; montant réservé. */
class OilStock extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.oil-stock';

    /** Rafraîchi après chaque action du tableau (annulation, retrait, stocks…) : OilOrderResource::refreshStock(). */
    protected $listeners = ['oil-orders-updated' => '$refresh'];

    protected function getViewData(): array
    {
        $active = OilOrder::where('status', '!=', OilOrderStatus::Cancelled);

        return [
            'rows' => OilOrders::stockSummary(),
            'open' => OilOrders::isOpen(),
            'orders' => (clone $active)->count(),
            'toCollect' => OilOrder::where('status', OilOrderStatus::Reserved)->count(),
            'amount' => (int) (clone $active)->sum('total_cents'),
        ];
    }
}
