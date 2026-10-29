<?php

namespace App\Support;

use App\Mail\MagicLinkMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Lien de connexion à usage unique, valable MINUTES minutes.
 */
class MagicLink
{
    public const MINUTES = 30;

    public static function send(User $user): void
    {
        $token = Str::random(48);
        Cache::put(self::cacheKey($token), $user->id, now()->addMinutes(self::MINUTES));

        $url = URL::temporarySignedRoute('magic-link.show', now()->addMinutes(self::MINUTES), ['token' => $token]);

        Mail::to($user->email)->send(new MagicLinkMail($user, $url));
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
