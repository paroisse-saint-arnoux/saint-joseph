<?php

namespace App\Support;

use App\Mail\MagicLinkMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Lien de connexion à usage unique, valable MINUTES minutes
 * (INVITATION_DAYS jours pour l'email d'accueil d'un nouvel utilisateur, qui peut l'ouvrir plus tard).
 */
class MagicLink
{
    public const MINUTES = 30;

    public const INVITATION_DAYS = 7;

    public static function send(User $user, bool $invitation = false): void
    {
        $expires = $invitation ? now()->addDays(self::INVITATION_DAYS) : now()->addMinutes(self::MINUTES);

        Mail::to($user->email)->send(new MagicLinkMail($user, self::url($user, $expires), $invitation));
    }

    /** Crée un lien à usage unique valable jusqu'à $expires (sans l'envoyer). */
    public static function url(User $user, \DateTimeInterface $expires): string
    {
        $token = Str::random(48);
        Cache::put(self::cacheKey($token), $user->id, $expires);

        return URL::temporarySignedRoute('magic-link.show', $expires, ['token' => $token]);
    }

    public static function isValid(string $token): bool
    {
        return Cache::has(self::cacheKey($token));
    }

    /** Consomme le jeton et renvoie l'utilisateur, ou null si invalide. */
    public static function consume(string $token): ?User
    {
        $id = Cache::pull(self::cacheKey($token));

        return $id ? User::where('is_active', true)->find($id) : null;
    }

    private static function cacheKey(string $token): string
    {
        return 'magic-link:'.hash('sha256', $token);
    }
}
