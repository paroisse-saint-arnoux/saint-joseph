<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Exposed;
use Filament\Support\Components\Attributes\Unexposed;

/**
 * Page d'édition d'un ou plusieurs blocs de la table settings.
 * Les champs sont nommés « clé.chemin » (ex. home.hero.title).
 */
abstract class SettingsPage extends Page
{
    /** @var array<int, string> clés de la table settings éditées par la page */
    protected array $settingKeys = [];

    public ?array $data = [];

    /** @return array<int, Component> */
    abstract protected function formComponents(): array;

    #[Unexposed]
    public function mount(): void
    {
        $this->form->fill(collect($this->settingKeys)->mapWithKeys(fn (string $key) => [$key => Setting::get($key, [])])->all());
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
                        Action::make('save')->label('Enregistrer')->submit('save')->keyBindings(['mod+s']),
                    ])->sticky(),
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

        Notification::make()->title('Modifications enregistrées')->success()->send();
    }
}
