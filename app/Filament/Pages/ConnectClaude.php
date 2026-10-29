<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Components\Attributes\Exposed;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

/**
 * Mode d'emploi pour connecter Claude (claude.ai) au site, à destination d'utilisateurs non techniques,
 * et liste des connexions de l'utilisateur avec possibilité de les couper.
 */
class ConnectClaude extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Connecter Claude';

    protected static ?string $title = 'Rédiger avec Claude';

    protected static ?string $slug = 'connecter-claude';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.connect-claude';

    public function getViewData(): array
    {
        return [
            'mcpUrl' => url('/mcp'),
            'libraryUrl' => url('/admin/bibliotheque'),
            'connections' => $this->connections(),
        ];
    }

    /** Connexions OAuth actives de l'utilisateur (une par application : Claude web, bureau…). */
    private function connections(): Collection
    {
        return Token::query()
            ->with(['client', 'refreshToken'])
            ->where('user_id', auth()->id())
            ->where('revoked', false)
            ->whereHas('client', fn ($q) => $q->whereJsonContains('grant_types', 'authorization_code'))
            ->get()
            // Toujours valable, ou renouvelable grâce à son jeton de rafraîchissement.
            ->filter(fn (Token $token) => $token->expires_at?->isFuture()
                || ($token->refreshToken && ! $token->refreshToken->revoked && $token->refreshToken->expires_at?->isFuture()))
            ->groupBy('client_id')
            ->map(fn (Collection $tokens) => [
                'client_id' => $tokens->first()->client_id,
                'name' => $tokens->first()->client?->name ?? 'Application',
                'since' => $tokens->min('created_at'),
                'last' => $tokens->max('created_at'),
            ])
            ->values();
    }

    #[Exposed]
    public function disconnect(string $clientId): void
    {
        $ids = Token::where('user_id', auth()->id())->where('client_id', $clientId)->pluck('id');
        Token::whereIn('id', $ids)->update(['revoked' => true]);
        RefreshToken::whereIn('access_token_id', $ids)->update(['revoked' => true]);

        Notification::make()->title('Connexion coupée')->body('Claude n\'a plus accès au site depuis cette application.')->success()->send();
    }
}
