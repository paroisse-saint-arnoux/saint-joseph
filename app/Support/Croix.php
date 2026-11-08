<?php

namespace App\Support;

use App\Models\CroixSignup;
use App\Models\CroixVisitor;
use App\Models\Setting;
use App\Services\Brevo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Concours S.O.S Calvaires « Que va devenir la Croix du Pape ? » (page /croix).
 *
 * Trois états, selon les dates du réglage « croix » :
 * - avant l'ouverture du vote : formulaire seul (« Recevez le lien de vote ») ;
 * - vote ouvert : bouton « Je vote maintenant », le formulaire renvoie vers le site de vote, bandeau sur l'accueil ;
 * - vote clos : formulaire pour recevoir le résultat.
 *
 * Suivi sans données personnelles : un cookie aléatoire par navigateur (13 mois, mesure d'audience exemptée),
 * qui relie la visite, la saisie des coordonnées et le clic vers le vote. Source = utm_source du premier lien utilisé.
 */
class Croix
{
    public const COOKIE = 'croix';

    /** Robots et aperçus de liens (WhatsApp, Facebook…) : pas comptés comme visiteurs. */
    private const BOTS = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|discord|skype|curl|wget|python|headless|lighthouse|monitor/i';

    public static function settings(): array
    {
        return Setting::get('croix', []) + [
            'vote_url' => 'https://vote.soscalvaires.org/',
            'opens_at' => '2026-11-09 00:00',
            'closes_at' => '2026-11-30 23:59',
            'home_banner' => true,
        ];
    }

    public static function opensAt(): Carbon
    {
        return Carbon::parse(self::settings()['opens_at']);
    }

    public static function closesAt(): Carbon
    {
        return Carbon::parse(self::settings()['closes_at'])->endOfMinute();
    }

    /** @return 'before'|'open'|'closed' */
    public static function state(): string
    {
        return match (true) {
            now()->lt(self::opensAt()) => 'before',
            now()->gt(self::closesAt()) => 'closed',
            default => 'open',
        };
    }

    public static function voteUrl(): string
    {
        return self::settings()['vote_url'];
    }

    /** Bandeau « La Croix du Pape au Rouret ? Votez » sur l'accueil : pendant le vote seulement. */
    public static function showHomeBanner(): bool
    {
        return self::state() === 'open' && (bool) self::settings()['home_banner'];
    }

    /** Source de la visite : balise UTM utm_source du lien (ex. ?utm_source=whatsapp&utm_medium=…&utm_campaign=…). */
    public static function source(Request $request): ?string
    {
        $source = Str::lower(preg_replace('/[^a-z0-9_-]/i', '', (string) ($request->query('utm_source') ?? $request->input('source'))));

        return $source !== '' ? Str::limit($source, 32, '') : null;
    }

    public static function isBot(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        return $agent === '' || preg_match(self::BOTS, $agent) === 1;
    }

    /** Visiteur du cookie, créé au besoin (sauf pour les robots). */
    public static function visitor(Request $request, bool $create = true): ?CroixVisitor
    {
        $token = $request->cookie(self::COOKIE);
        $visitor = is_string($token) && $token !== '' ? CroixVisitor::firstWhere('token', $token) : null;

        if (! $visitor && $create && ! self::isBot($request)) {
            $visitor = CroixVisitor::create(['token' => Str::random(48), 'source' => self::source($request)]);
        }

        if ($visitor) {
            Cookie::queue(self::COOKIE, $visitor->token, 60 * 24 * 395);
        }

        return $visitor;
    }

    public static function recordVisit(Request $request): ?CroixVisitor
    {
        $visitor = self::visitor($request);
        if ($visitor) {
            $visitor->forceFill([
                'visits' => $visitor->visits + 1,
                'visited_at' => $visitor->visited_at ?? now(),
                'source' => $visitor->source ?? self::source($request),
            ])->save();
        }

        return $visitor;
    }

    public static function recordVoteClick(Request $request): void
    {
        $visitor = self::visitor($request);
        if ($visitor && ! $visitor->vote_clicked_at) {
            $visitor->forceFill(['vote_clicked_at' => now(), 'source' => $visitor->source ?? self::source($request)])->save();
        }
    }

    /**
     * Enregistre les coordonnées (une ligne par email, mise à jour si la personne revient)
     * et les envoie à Brevo après l'envoi de la réponse.
     */
    public static function signup(Request $request, array $data): CroixSignup
    {
        $visitor = self::visitor($request);
        $email = mb_strtolower(trim($data['email']));
        $signup = CroixSignup::firstOrNew(['email' => $email]);

        $signup->fill(array_filter([
            'first_name' => self::clean($data['first_name'] ?? null),
            'last_name' => self::clean($data['last_name'] ?? null),
            'phone' => self::normalizePhone($data['phone'] ?? null),
        ]) + [
            'visitor_id' => $signup->visitor_id ?? $visitor?->id,
            'consent_sms' => $signup->consent_sms || ! empty($data['consent_sms']),
            'source' => $signup->source ?? $visitor?->source ?? self::source($request),
        ]);
        $dirty = $signup->isDirty(['first_name', 'last_name', 'phone']);
        $signup->save();

        if ($visitor && ! $visitor->signed_up_at) {
            $visitor->forceFill(['signed_up_at' => now()])->save();
        }

        if ($dirty || ! $signup->brevo_synced_at) {
            dispatch(fn () => self::syncToBrevo($signup))->afterResponse();
        }

        return $signup;
    }

    /** Ajoute le contact à la liste Brevo du concours. Si Brevo refuse le numéro (invalide, déjà pris), on réessaie sans. */
    public static function syncToBrevo(CroixSignup $signup): bool
    {
        $brevo = app(Brevo::class);
        $list = (int) config('services.brevo.croix_list_id');
        $attributes = array_filter(['PRENOM' => $signup->first_name, 'NOM' => $signup->last_name]);

        $ok = $signup->phone && $signup->consent_sms
            ? $brevo->addContact($signup->email, $list, $attributes + ['SMS' => $signup->phone])
                || $brevo->addContact($signup->email, $list, $attributes)
            : $brevo->addContact($signup->email, $list, $attributes);

        if ($ok) {
            $signup->forceFill(['brevo_synced_at' => now()])->save();
        }

        return $ok;
    }

    /** Numéro au format international attendu par Brevo : 06 12 34 56 78 → +33612345678. */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/[^\d+]/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }

        return match (true) {
            str_starts_with($digits, '+') => '+'.ltrim($digits, '+'),
            str_starts_with($digits, '00') => '+'.substr($digits, 2),
            str_starts_with($digits, '0') && strlen($digits) === 10 => '+33'.substr($digits, 1),
            default => $digits,
        };
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** Entonnoir : visiteurs de la page → coordonnées → clic vers le vote, au total et par source. */
    public static function funnel(): array
    {
        $sources = CroixVisitor::query()
            ->selectRaw('source, SUM(visited_at IS NOT NULL) as visitors, SUM(signed_up_at IS NOT NULL) as signups,
                SUM(vote_clicked_at IS NOT NULL) as votes, SUM(vote_clicked_at IS NOT NULL AND visited_at IS NULL) as direct_votes,
                SUM(visits) as pageviews')
            ->groupBy('source')
            ->get()
            ->map(fn ($row) => ['source' => $row->source] + array_map('intval', $row->only(['visitors', 'signups', 'votes', 'direct_votes', 'pageviews'])))
            ->sortByDesc('visitors')
            ->values();

        $total = collect(['visitors', 'signups', 'votes', 'direct_votes', 'pageviews'])->mapWithKeys(fn ($k) => [$k => $sources->sum($k)])->all();

        return [
            'total' => $total + ['contacts' => CroixSignup::count(), 'unsynced' => CroixSignup::whereNull('brevo_synced_at')->count()],
            'sources' => $sources->all(),
        ];
    }

    /** Jour par jour depuis le premier visiteur (30 jours au plus). */
    public static function daily(int $days = 30): array
    {
        $since = now()->subDays($days - 1)->startOfDay();
        $count = fn (string $column) => CroixVisitor::query()
            ->where($column, '>=', $since)
            ->selectRaw('DATE('.$column.') as day, COUNT(*) as n')
            ->groupBy(DB::raw('DATE('.$column.')'))
            ->pluck('n', 'day');

        $visitors = $count('visited_at');
        $signups = $count('signed_up_at');
        $votes = $count('vote_clicked_at');

        $first = CroixVisitor::query()->min('created_at');
        $start = $first ? Carbon::parse($first)->startOfDay()->max($since) : now()->startOfDay();

        $result = [];
        for ($day = $start->copy(); $day->lte(now()); $day->addDay()) {
            $key = $day->toDateString();
            $result[$key] = ['visitors' => (int) ($visitors[$key] ?? 0), 'signups' => (int) ($signups[$key] ?? 0), 'votes' => (int) ($votes[$key] ?? 0)];
        }

        return $result;
    }
}
