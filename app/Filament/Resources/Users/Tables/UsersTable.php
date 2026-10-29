<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Support\MagicLink;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                TextColumn::make('role')->label('Rôle')->badge()->color('gray'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                TextColumn::make('tokens_count')->label('Jetons API')->counts('tokens')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('sendLink')
                        ->label('Envoyer un lien de connexion')
                        ->icon('heroicon-o-envelope')
                        ->visible(fn (User $record) => $record->is_active)
                        ->requiresConfirmation()
                        ->action(function (User $record) {
                            MagicLink::send($record);
                            Notification::make()->title('Lien envoyé à '.$record->email)->success()->send();
                        }),
                    Action::make('createToken')
                        ->label('Créer un jeton API')
                        ->icon('heroicon-o-key')
                        ->modalDescription('Le jeton permet à un outil (ex. Claude Code) de lire et d\'écrire des articles au nom de cet utilisateur. Il ne sera affiché qu\'une seule fois.')
                        ->requiresConfirmation()
                        ->action(function (User $record) {
                            $token = $record->createToken('api-'.now()->format('Y-m-d'))->accessToken;
                            Notification::make()
                                ->title('Jeton API créé')
                                ->body("Copiez-le maintenant, il ne sera plus affiché :\n\n".$token)
                                ->persistent()
                                ->success()
                                ->send();
                        }),
                    Action::make('revokeTokens')
                        ->label('Révoquer les jetons et les connexions Claude')
                        ->modalDescription('Les jetons API et les connexions à Claude de cet utilisateur cesseront de fonctionner.')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (User $record) {
                            $record->tokens()->update(['revoked' => true]);
                            Notification::make()->title('Jetons révoqués')->success()->send();
                        }),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
