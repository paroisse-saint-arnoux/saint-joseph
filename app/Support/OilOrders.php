<?php

namespace App\Support;

use App\Enums\OilOrderStatus;
use App\Mail\OilOrderMail;
use App\Models\OilOrder;
use App\Models\OilProduct;
use App\Models\Setting;
use App\Services\Brevo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Réservation de bouteilles d'huile (page /commande-huile) : stock, commandes, e-mail de confirmation,
 * liste Brevo, suivi et export Excel dans l'admin. Réglage `commande_huile` : open, pickup, payment.
 */
class OilOrders
{
    public static function settings(): array
    {
        return [
            'open' => (bool) Setting::get('commande_huile.open', true),
            'pickup' => (string) Setting::get('commande_huile.pickup', 'à la kermesse du dimanche 29 novembre 2026, salle Charvet'),
            'payment' => (string) Setting::get('commande_huile.payment', 'Le règlement se fait au moment du retrait.'),
        ];
    }

    public static function isOpen(): bool
    {
        return self::settings()['open'];
    }

    /** 1700 → « 17 € », 1750 → « 17,50 € » (espace insécable). */
    public static function money(int $cents): string
    {
        return number_format($cents / 100, $cents % 100 ? 2 : 0, ',', "\u{202F}")."\u{00A0}€";
    }

    /**
     * Enregistre une commande et retire les bouteilles du stock, d'un seul tenant (deux personnes ne peuvent pas
     * réserver la dernière bouteille en même temps). $quantities : [product_id => nombre].
     *
     * @throws ValidationException si une quantité dépasse le stock restant
     */
    public static function place(array $customer, array $quantities): OilOrder
    {
        $quantities = array_filter(array_map('intval', $quantities), fn (int $q) => $q > 0);

        $order = DB::transaction(function () use ($customer, $quantities) {
            $products = OilProduct::whereIn('id', array_keys($quantities))->lockForUpdate()->get()->keyBy('id');

            $errors = [];
            foreach ($quantities as $id => $quantity) {
                $product = $products->get($id);
                if (! $product || $quantity > $product->stock) {
                    $errors["quantities.$id"] = $product && $product->stock > 0
                        ? 'Il ne reste que '.$product->stock.' bouteille'.($product->stock > 1 ? 's' : '').' de '.$product->label.'.'
                        : 'Il n\'y a plus de bouteille'.($product ? ' de '.$product->label : '').' disponible.';
                }
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }

            $order = new OilOrder([...$customer, 'total_cents' => 0]);
            $order->status = OilOrderStatus::Reserved;
            $order->save();
            $order->forceFill(['number' => 'H-'.str_pad((string) $order->id, 3, '0', STR_PAD_LEFT)]);

            $total = 0;
            foreach ($products->sortBy('position') as $product) {
                $quantity = $quantities[$product->id];
                $order->items()->create([
                    'product_id' => $product->id,
                    'label' => $product->label,
                    'unit_price_cents' => $product->price_cents,
                    'quantity' => $quantity,
                ]);
                $product->decrement('stock', $quantity);
                $total += $quantity * $product->price_cents;
            }

            $order->total_cents = $total;
            $order->save();

            return $order;
        });

        // Après l'envoi de la réponse : l'e-mail et Brevo ne ralentissent pas la page de confirmation.
        dispatch(function () use ($order) {
            self::sendConfirmation($order);
            self::syncBrevo($order);
        })->afterResponse();

        return $order;
    }

    /** E-mail récapitulatif avec le numéro de commande. */
    public static function sendConfirmation(OilOrder $order): bool
    {
        try {
            Mail::to($order->email, $order->fullName())->send(new OilOrderMail($order->load('items')));
            $order->forceFill(['confirmation_sent_at' => now()])->save();

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Ajoute la personne à la liste Brevo des commandes (si Brevo refuse le numéro, nouvel essai sans). */
    public static function syncBrevo(OilOrder $order): bool
    {
        $brevo = app(Brevo::class);
        if (! $brevo->isConfigured()) {
            return false;
        }

        $list = (int) config('services.brevo.huile_order_list_id');
        $attributes = ['PRENOM' => $order->first_name, 'NOM' => $order->last_name];
        $ok = $brevo->addContact($order->email, $list, $attributes + ['SMS' => $order->phone])
            || $brevo->addContact($order->email, $list, $attributes);

        if ($ok) {
            $order->forceFill(['brevo_synced_at' => now()])->save();
        }

        return $ok;
    }

    public static function collect(OilOrder $order): void
    {
        if ($order->status === OilOrderStatus::Cancelled) {
            self::reopen($order);
        }
        $order->status = OilOrderStatus::Collected;
        $order->collected_at = now();
        $order->save();
    }

    /** Annule la commande : ses bouteilles reviennent dans le stock. */
    public static function cancel(OilOrder $order): void
    {
        if ($order->status === OilOrderStatus::Cancelled) {
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                OilProduct::whereKey($item->product_id)->increment('stock', $item->quantity);
            }
            $order->status = OilOrderStatus::Cancelled;
            $order->cancelled_at = now();
            $order->save();
        });
    }

    /**
     * Remet la commande « à retirer ». Une commande annulée reprend ses bouteilles dans le stock, s'il en reste assez.
     *
     * @throws ValidationException si le stock ne suffit plus
     */
    public static function reopen(OilOrder $order): void
    {
        DB::transaction(function () use ($order) {
            if ($order->status === OilOrderStatus::Cancelled) {
                $products = OilProduct::whereIn('id', $order->items->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
                foreach ($order->items as $item) {
                    $product = $products->get($item->product_id);
                    if ($product && $product->stock < $item->quantity) {
                        throw ValidationException::withMessages(['stock' => 'Stock insuffisant : il ne reste que '.$product->stock.' bouteille(s) de '.$product->label.'.']);
                    }
                }
                foreach ($order->items as $item) {
                    $products->get($item->product_id)?->decrement('stock', $item->quantity);
                }
            }
            $order->status = OilOrderStatus::Reserved;
            $order->collected_at = null;
            $order->cancelled_at = null;
            $order->save();
        });
    }

    /**
     * Par format. `stock` (colonne oil_products.stock) = bouteilles encore disponibles à la réservation, c'est-à-dire
     * le prévisionnel une fois toutes les commandes retirées ; `on_hand` = bouteilles réellement là
     * (stock initial − bouteilles retirées) = disponibles + à retirer.
     *
     * @return Collection<int, array{product: OilProduct, stock: int, on_hand: int, reserved: int, collected: int}>
     */
    public static function stockSummary(): Collection
    {
        $sold = DB::table('oil_order_items')
            ->join('oil_orders', 'oil_orders.id', '=', 'oil_order_items.order_id')
            ->selectRaw('oil_order_items.product_id, oil_orders.status, SUM(oil_order_items.quantity) as bottles')
            ->groupBy('oil_order_items.product_id', 'oil_orders.status')
            ->get();

        $count = fn (int $id, OilOrderStatus $status) => (int) $sold->where('product_id', $id)->where('status', $status->value)->sum('bottles');

        return OilProduct::ordered()->get()->map(fn (OilProduct $p) => [
            'product' => $p,
            'stock' => $p->stock,
            'on_hand' => $p->stock + $count($p->id, OilOrderStatus::Reserved),
            'reserved' => $count($p->id, OilOrderStatus::Reserved),
            'collected' => $count($p->id, OilOrderStatus::Collected),
        ]);
    }

    /** Classeur Excel : commandes (une ligne par commande, une colonne par format) et stocks. Renvoie le chemin du fichier temporaire. */
    public static function export(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'huile').'.xlsx';
        $bold = (new Style)->setFontBold();
        $products = OilProduct::ordered()->get();

        $writer = new Writer;
        $writer->openToFile($path);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Commandes');
        $sheet->setSheetView((new SheetView)->setFreezeRow(2));
        $sheet->setColumnWidth(18, 3, 4);
        $sheet->setColumnWidth(32, 6);
        $sheet->setColumnWidth(40, 13);
        $writer->addRow(Row::fromValues([
            'N°', 'Date', 'Prénom', 'Nom', 'Téléphone', 'Email',
            ...$products->map(fn (OilProduct $p) => $p->label),
            'Bouteilles', 'Total (€)', 'Statut', 'Retirée le', 'Note',
        ], $bold));

        OilOrder::with('items')->orderBy('id')->each(function (OilOrder $order) use ($writer, $products) {
            $writer->addRow(Row::fromValues([
                $order->number,
                $order->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                $order->first_name,
                $order->last_name,
                $order->phoneForDisplay(),
                $order->email,
                ...$products->map(fn (OilProduct $p) => (int) $order->items->where('product_id', $p->id)->sum('quantity')),
                $order->bottles(),
                $order->total_cents / 100,
                $order->status->getLabel(),
                $order->collected_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                $order->notes,
            ]));
        });

        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Stocks');
        $sheet->setColumnWidth(14, 1);
        $sheet->setColumnWidth(28, 3, 6);
        $writer->addRow(Row::fromValues(['Format', 'Prix (€)', 'En stock (initial − retirées)', 'À retirer', 'Retirées', 'Après les commandes (disponibles)'], $bold));
        foreach (self::stockSummary() as $row) {
            $writer->addRow(Row::fromValues([
                $row['product']->label, $row['product']->price_cents / 100, $row['on_hand'], $row['reserved'], $row['collected'], $row['stock'],
            ]));
        }

        $writer->close();

        return $path;
    }
}
