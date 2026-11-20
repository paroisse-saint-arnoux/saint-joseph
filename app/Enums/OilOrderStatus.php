<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Suivi d'une réservation d'huile : à retirer à la kermesse, retirée, ou annulée (bouteilles remises en stock). */
enum OilOrderStatus: string implements HasColor, HasLabel
{
    case Reserved = 'reserved';
    case Collected = 'collected';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Reserved => 'À retirer',
            self::Collected => 'Retirée',
            self::Cancelled => 'Annulée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Reserved => 'warning',
            self::Collected => 'success',
            self::Cancelled => 'gray',
        };
    }
}
