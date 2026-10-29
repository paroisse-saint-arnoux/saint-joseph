<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Support\MagicLink;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** Le nouvel utilisateur reçoit tout de suite un email avec son lien de connexion. */
    protected function afterCreate(): void
    {
        if (! $this->record->is_active) {
            return;
        }

        try {
            MagicLink::send($this->record, invitation: true);
            Notification::make()->title('Lien de connexion envoyé à '.$this->record->email)->success()->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()
                ->title('L\'email n\'a pas pu être envoyé')
                ->body('Utilisez « Envoyer un lien de connexion » dans la liste des utilisateurs.')
                ->warning()->persistent()->send();
        }
    }
}
