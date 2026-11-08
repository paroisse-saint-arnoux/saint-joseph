<?php

namespace App\Support;

use App\Services\Brevo;
use Illuminate\Support\Carbon;

/**
 * Historique des listes Brevo, semaine par semaine.
 *
 * Brevo ne donne pas l'historique de la taille d'une liste : on le reconstitue à partir de la date de création
 * des contacts (taille à la fin d'une semaine = taille actuelle − contacts créés depuis). C'est une approximation :
 * un contact déjà connu de Brevo et ajouté plus tard à une liste compte à sa date de création, et les désinscriptions
 * passées ne sont pas visibles.
 */
class BrevoStats
{
    public const WEEKS = 12;

    /**
     * @return array{weeks: array<int, Carbon>, lists: array<int, array{id: int, name: string, subscribers: int, blacklisted: int, new_7: int, new_30: int, new: array<int, int>, size: array<int, int>}>}
     */
    public static function lists(int $weeks = self::WEEKS): array
    {
        $brevo = app(Brevo::class);
        $lists = $brevo->lists();
        $start = now()->startOfWeek()->subWeeks($weeks - 1);
        $contacts = $lists ? $brevo->contactsCreatedSince($start) : [];

        $weekStarts = [];
        for ($i = 0; $i < $weeks; $i++) {
            $weekStarts[] = $start->copy()->addWeeks($i);
        }

        $rows = [];
        foreach ($lists as $list) {
            $mine = array_filter($contacts, fn ($c) => in_array($list['id'], $c['lists'], true));
            $new = array_map(fn (Carbon $week) => count(array_filter($mine, fn ($c) => $c['created_at']->between($week, $week->copy()->endOfWeek()))), $weekStarts);

            // Taille à la fin de chaque semaine, en remontant depuis la taille actuelle.
            $size = [];
            $running = $list['subscribers'];
            for ($i = $weeks - 1; $i >= 0; $i--) {
                $size[$i] = max(0, $running);
                $running -= $new[$i];
            }
            ksort($size);

            $rows[] = $list + [
                'new_7' => count(array_filter($mine, fn ($c) => $c['created_at']->gte(now()->subDays(7)))),
                'new_30' => count(array_filter($mine, fn ($c) => $c['created_at']->gte(now()->subDays(30)))),
                'new' => $new,
                'size' => array_values($size),
            ];
        }

        return ['weeks' => $weekStarts, 'lists' => $rows];
    }

    /** Totaux des emails transactionnels sur la période. */
    public static function transactionalTotals(int $days = 30): array
    {
        $daily = app(Brevo::class)->transactionalDaily($days);
        $sum = fn (string $k) => array_sum(array_column($daily, $k));

        return [
            'requests' => $sum('requests'), 'delivered' => $sum('delivered'), 'opens' => $sum('opens'),
            'clicks' => $sum('clicks'), 'bounces' => $sum('bounces'), 'blocked' => $sum('blocked'), 'spam' => $sum('spam'),
        ];
    }
}
