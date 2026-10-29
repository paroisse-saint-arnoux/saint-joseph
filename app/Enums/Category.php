<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Liste fixe des catégories d'articles. La valeur sert de segment d'URL :
 * /le-projet/{category}/{slug}.
 */
enum Category: string implements HasLabel
{
    case Agenda = 'agenda';
    case Architecture = 'architecture';
    case Budget = 'budget';
    case Construction = 'construction';
    case Projet = 'projet';
    case Presentation = 'presentation';
    case Equipe = 'equipe';

    public function getLabel(): string
    {
        return match ($this) {
            self::Agenda => 'Agenda',
            self::Architecture => 'Architecture',
            self::Budget => 'Budget',
            self::Construction => 'Construction',
            self::Projet => 'Projet',
            self::Presentation => 'Présentation',
            self::Equipe => 'Équipe',
        };
    }
}
