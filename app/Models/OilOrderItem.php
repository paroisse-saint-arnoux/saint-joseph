<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ligne d'une commande d'huile : libellé et prix copiés au moment de la commande. */
#[Fillable(['product_id', 'label', 'unit_price_cents', 'quantity'])]
class OilOrderItem extends Model
{
    protected function casts(): array
    {
        return ['unit_price_cents' => 'integer', 'quantity' => 'integer'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OilOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(OilProduct::class, 'product_id');
    }

    public function totalCents(): int
    {
        return $this->unit_price_cents * $this->quantity;
    }
}
