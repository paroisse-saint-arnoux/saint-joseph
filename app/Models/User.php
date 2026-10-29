<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Token;

#[Fillable(['name', 'email', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    // Valeurs par défaut de la base, aussi sur un modèle tout juste créé.
    protected $attributes = [
        'role' => 'editor',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'last_mcp_at' => 'datetime',
        ];
    }

    /**
     * Enregistre une date d'activité (last_seen_at, last_mcp_at) au plus toutes les 5 minutes,
     * sans toucher à updated_at.
     */
    public function recordActivity(string $column): void
    {
        if ($this->{$column} && $this->{$column}->gt(now()->subMinutes(5))) {
            return;
        }

        DB::table($this->getTable())->where('id', $this->id)->update([$column => now()]);
        $this->{$column} = now();
    }

    /** Le connecteur Claude a un accès en cours (jeton OAuth valide ou renouvelable, non révoqué). */
    public function hasClaudeConnection(): bool
    {
        return Token::where('user_id', $this->id)
            ->where('revoked', false)
            ->whereHas('client', fn ($q) => $q->whereJsonContains('grant_types', 'authorization_code'))
            ->where(fn ($q) => $q->where('expires_at', '>', now())
                ->orWhereHas('refreshToken', fn ($r) => $r->where('revoked', false)->where('expires_at', '>', now())))
            ->exists();
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }
}
