<?php

namespace App\Support;

use App\Models\LibraryImage;

/** Résultat d'un ajout dans la bibliothèque. */
final class LibraryResult
{
    public const ADDED = 'added';

    public const REPLACED = 'replaced';

    public const DUPLICATE = 'duplicate';

    public function __construct(public readonly LibraryImage $image, public readonly string $status) {}

    public function label(): string
    {
        return match ($this->status) {
            self::ADDED => 'ajoutée',
            self::REPLACED => 'remplacée (meilleure qualité)',
            self::DUPLICATE => 'déjà présente',
        };
    }

    /** Résumé d'un lot d'ajouts, ex. « 3 ajoutées, 1 remplacée (meilleure qualité), 2 déjà présentes ». */
    public static function summary(array $results): string
    {
        $counts = array_count_values(array_map(fn (self $r) => $r->status, $results));
        $parts = [];
        foreach ([self::ADDED => 'ajoutée', self::REPLACED => 'remplacée (meilleure qualité)', self::DUPLICATE => 'déjà présente'] as $status => $label) {
            if ($n = $counts[$status] ?? 0) {
                $parts[] = $n.' '.($n > 1 ? str_replace(['ajoutée', 'remplacée', 'présente'], ['ajoutées', 'remplacées', 'présentes'], $label) : $label);
            }
        }

        return $parts ? implode(', ', $parts) : 'aucune image';
    }
}
