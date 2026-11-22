<?php

namespace App\Models;

use App\Enums\OilOrderStatus;
use App\Support\OilOrders;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/** Réservation de bouteilles d'huile (page /commande-huile), retirée et réglée à la kermesse. */
#[Fillable(['first_name', 'last_name', 'email', 'phone', 'total_cents', 'discounted', 'notes'])]
class OilOrder extends Model
{
    protected function casts(): array
    {
        return [
            'status' => OilOrderStatus::class,
            'total_cents' => 'integer',
            'discounted' => 'boolean',
            'collected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
            'brevo_synced_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OilOrderItem::class, 'order_id');
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** Téléphone lisible : 06 12 34 56 78 pour la France, +32 470 12 34 56 sinon. */
    public function phoneForDisplay(): string
    {
        try {
            $util = PhoneNumberUtil::getInstance();
            $number = $util->parse($this->phone, 'FR');

            return $util->format($number, $util->getRegionCodeForNumber($number) === 'FR' ? PhoneNumberFormat::NATIONAL : PhoneNumberFormat::INTERNATIONAL);
        } catch (NumberParseException) {
            return (string) $this->phone;
        }
    }

    /** « 2 × 500 mL, 1 × 750 mL » */
    public function summary(): string
    {
        return $this->items->map(fn (OilOrderItem $item) => $item->quantity.' × '.$item->label)->implode(', ');
    }

    public function bottles(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /** Total des bouteilles au prix plein, avant le tarif réduit. */
    public function fullPriceCents(): int
    {
        return (int) $this->items->sum(fn (OilOrderItem $item) => $item->totalCents());
    }

    public function total(): string
    {
        return OilOrders::money($this->total_cents);
    }
}
