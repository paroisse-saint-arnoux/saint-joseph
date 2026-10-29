<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Support\MagicLink;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Components\Attributes\Exposed;
use Filament\Support\Components\Attributes\Unexposed;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Connexion sans mot de passe : on saisit son email et on reçoit un lien magique.
 * Seuls les utilisateurs actifs de la liste blanche (ressource Utilisateurs) le reçoivent.
 */
class Login extends BaseLogin
{
    #[Unexposed]
    public function mount(): void
    {
        parent::mount();

        if (session('magic-link-expired')) {
            Notification::make()->title('Ce lien de connexion n\'est plus valable. Demandez-en un nouveau.')->warning()->send();
        }
    }

    #[Exposed]
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $email = mb_strtolower(trim($this->form->getState()['email']));
        $user = User::where('email', $email)->where('is_active', true)->first();

        if ($user) {
            MagicLink::send($user);
        }

        // Même message dans tous les cas, pour ne pas révéler quelles adresses sont autorisées.
        Notification::make()
            ->title('Vérifiez votre boîte mail')
            ->body('Si cette adresse est autorisée, un lien de connexion vient de lui être envoyé. Il est valable '.MagicLink::MINUTES.' minutes.')
            ->success()
            ->persistent()
            ->send();

        $this->form->fill();

        return null;
    }

    #[Unexposed]
    public function form(Schema $schema): Schema
    {
        return $schema->components([$this->getEmailFormComponent()]);
    }

    #[Unexposed]
    public function getSubheading(): string|Htmlable|null
    {
        return 'Saisissez votre adresse email : vous recevrez un lien de connexion.';
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()->label('Recevoir mon lien de connexion');
    }
}
