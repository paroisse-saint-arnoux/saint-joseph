<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ArticleFormat: string implements HasLabel
{
    case Markdown = 'markdown';
    case Html = 'html';

    public function getLabel(): string
    {
        return match ($this) {
            self::Markdown => 'Markdown (article simple)',
            self::Html => 'HTML (article mis en page, généré par IA)',
        };
    }
}
