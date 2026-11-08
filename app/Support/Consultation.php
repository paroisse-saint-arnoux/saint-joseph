<?php

namespace App\Support;

use App\Models\ConsultationParticipant;
use App\Models\Setting;
use App\Services\Brevo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Consultation des paroissiens sur les projets d'architecture.
 * La personne est identifiée par son email (sans vérification, pour rester simple), gardé en session
 * et dans un cookie de 400 jours : elle retrouve ses réponses en rouvrant la page sur son téléphone.
 */
class Consultation
{
    public const COOKIE = 'consultation';

    /** Avis général, puis les trois critères affichés une fois l'avis donné. Toutes les échelles vont de -2 à +2. */
    public const SCALES = [
        'overall' => ['label' => 'Ce projet vous plaît-il ?', 'short' => 'Avis général',
            'steps' => [-2 => 'Je n\'aime pas du tout', -1 => 'Plutôt pas', 0 => 'Indifférent, pourquoi pas', 1 => 'Plutôt oui', 2 => 'J\'aime beaucoup']],
        'aesthetic' => ['label' => 'Esthétique', 'short' => 'Esthétique',
            'steps' => [-2 => 'C\'est laid', -1 => 'Plutôt laid', 0 => 'Ni beau ni laid', 1 => 'Plutôt beau', 2 => 'Très beau']],
        'spiritual' => ['label' => 'Spirituel', 'short' => 'Spirituel',
            'steps' => [-2 => 'Pas de présence', -1 => 'Peu de présence', 0 => 'Neutre', 1 => 'Une présence', 2 => 'Ça vibre']],
        'convivial' => ['label' => 'Convivial', 'short' => 'Convivial',
            'steps' => [-2 => 'C\'est froid', -1 => 'Plutôt froid', 0 => 'Neutre', 1 => 'Plutôt accueillant', 2 => 'On s\'y sent bien']],
    ];

    /** Libellés affichés sous l'échelle (les autres crans servent aux lecteurs d'écran). */
    public const VISIBLE_STEPS = [
        'overall' => [-2, 0, 2],
        'aesthetic' => [-2, 2],
        'spiritual' => [-2, 2],
        'convivial' => [-2, 2],
    ];

    public static function isOpen(): bool
    {
        return (bool) Setting::get('consultation.open', true);
    }

    public static function participant(Request $request): ?ConsultationParticipant
    {
        if ($id = $request->session()->get(self::COOKIE)) {
            if ($participant = ConsultationParticipant::find($id)) {
                return $participant;
            }
        }

        $token = $request->cookie(self::COOKIE);
        $participant = is_string($token) && $token !== '' ? ConsultationParticipant::firstWhere('token', $token) : null;
        if ($participant) {
            $request->session()->put(self::COOKIE, $participant->id);
        }

        return $participant;
    }

    /** Identifie la personne par son email (création au premier passage) et l'inscrit dans la liste Brevo de la consultation. */
    public static function identify(Request $request, string $email): ConsultationParticipant
    {
        $participant = ConsultationParticipant::firstOrCreate(
            ['email' => mb_strtolower(trim($email))],
            ['token' => Str::random(48)],
        );

        $request->session()->regenerate();
        $request->session()->put(self::COOKIE, $participant->id);
        Cookie::queue(self::COOKIE, $participant->token, 60 * 24 * 400);

        if (! $participant->brevo_synced_at) {
            // Après l'envoi de la réponse : l'appel à Brevo ne ralentit pas l'arrivée sur la page de vote.
            dispatch(function () use ($participant) {
                if (app(Brevo::class)->addContact($participant->email, config('services.brevo.consultation_list_id'))) {
                    $participant->forceFill(['brevo_synced_at' => now()])->save();
                }
            })->afterResponse();
        }

        return $participant;
    }

    public static function forget(Request $request): void
    {
        $request->session()->forget(self::COOKIE);
        Cookie::queue(Cookie::forget(self::COOKIE));
    }
}
