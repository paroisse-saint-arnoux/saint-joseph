<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\Field;

/**
 * Sélecteur d'images de la bibliothèque (grille avec recherche et chargement progressif).
 * État : liste des identifiants choisis, dans l'ordre de sélection.
 */
class LibraryPicker extends Field
{
    protected string $view = 'filament.forms.library-picker';

    protected bool $isMultiple = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
    }

    public function multiple(bool $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->isMultiple;
    }
}
