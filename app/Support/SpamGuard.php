<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Protection anti-spam des formulaires publics :
 * - champ « website » invisible (honeypot) qui doit rester vide ;
 * - champ caché « _t » (horodatage chiffré) : un humain met plus de 3 s à remplir le formulaire ;
 * - Cloudflare Turnstile si les clés sont configurées.
 */
class SpamGuard
{
    public static function timestamp(): string
    {
        return encrypt(now()->timestamp);
    }

    public static function passes(Request $request, bool $withTurnstile = true): bool
    {
        if (filled($request->input('website'))) {
            return false;
        }

        try {
            $startedAt = (int) decrypt((string) $request->input('_t'));
        } catch (\Throwable) {
            return false;
        }

        if (now()->timestamp - $startedAt < 3) {
            return false;
        }

        return ! $withTurnstile || self::turnstilePasses($request);
    }

    public static function turnstileEnabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    private static function turnstilePasses(Request $request): bool
    {
        if (! self::turnstileEnabled()) {
            return true;
        }

        $response = Http::asForm()->timeout(10)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('services.turnstile.secret_key'),
            'response' => $request->input('cf-turnstile-response'),
            'remoteip' => $request->ip(),
        ]);

        return (bool) $response->json('success');
    }
}
