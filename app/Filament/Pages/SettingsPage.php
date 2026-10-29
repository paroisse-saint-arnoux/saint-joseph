<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasUnsavedDataChangesAlert;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Exposed;
use Filament\Support\Components\Attributes\Unexposed;
use Filament\Support\Enums\Alignment;

/**
 * Page d'édition d'un ou plusieurs blocs de la table settings.
 * Les champs sont nommés « clé.chemin » (ex. home.hero.title).
 */
abstract class SettingsPage extends Page
{
    // Empreinte des données enregistrées : alerte en cas de départ sans enregistrer, barre fixe si modifié.
    use HasUnsavedDataChangesAlert;

    /** @var array<int, string> clés de la table settings éditées par la page */
    protected array $settingKeys = [];

    public ?array $data = [];

    /** @return array<int, Component> */
    abstract protected function formComponents(): array;

    #[Unexposed]
    public function mount(): void
    {
        $this->fillFromSettings();
    }

    /** Remplit le formulaire depuis la base et mémorise l'empreinte des données (modifications non enregistrées). */
    protected function fillFromSettings(): void
    {
        $this->form->fill(collect($this->settingKeys)->mapWithKeys(fn (string $key) => [$key => Setting::get($key, [])])->all());
        $this->rememberData();
    }

    #[Unexposed]
    public function form(Schema $schema): Schema
    {
        return $schema->components($this->formComponents())->statePath('data');
    }

    #[Unexposed]
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Sauvegarder les modifications')->submit('save')->keyBindings(['mod+s']),
                        // Revenir au contenu enregistré (abandonne les modifications en cours).
                        Action::make('cancel')->label('Annuler')->color('gray')->action(fn () => $this->fillFromSettings()),
                    ])->sticky()->alignment(Alignment::End),
                ]),
        ]);
    }

    #[Exposed]
    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($this->settingKeys as $key) {
            Setting::put($key, array_replace(Setting::get($key, []), $state[$key] ?? []));
        }

        $this->fillFromSettings();

        Notification::make()->title('Modifications enregistrées')->success()->send();
    }
}
