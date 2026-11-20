<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Format de bouteille d'huile proposé à la réservation, avec son stock restant. */
#[Fillable(['label', 'price_cents', 'stock', 'position'])]
class OilProduct extends Model
{
    /** Nombre maximum de bouteilles d'un même format par commande. */
    public const MAX_PER_ORDER = 5;

    protected function casts(): array
    {
        return ['price_cents' => 'integer', 'stock' => 'integer', 'position' => 'integer'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OilOrderItem::class, 'product_id');
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /** Nombre de bouteilles que l'on peut encore choisir dans une commande. */
    public function maxPerOrder(): int
    {
        return max(0, min(self::MAX_PER_ORDER, $this->stock));
    }
}
