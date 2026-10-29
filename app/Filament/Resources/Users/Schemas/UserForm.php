<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label('Nom')->required()->maxLength(120),
                TextInput::make('email')
                    ->label('Adresse email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (string $state) => mb_strtolower(trim($state))),
                Select::make('role')
                    ->label('Rôle')
                    ->options(Role::class)
                    ->default(Role::Editor)
                    ->required()
                    ->native(false)
                    ->helperText('Éditeur : gère le contenu. Administrateur : gère aussi les utilisateurs.'),
                Toggle::make('is_active')
                    ->label('Peut se connecter')
                    ->default(true)
                    ->inline(false),
            ]),
        ]);
    }
}
