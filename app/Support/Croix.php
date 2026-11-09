<?php

namespace App\Support;

use App\Models\CroixVisitor;
use App\Models\Setting;
use App\Services\Brevo;
use Illuminate\Http\Request;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Concours S.O.S Calvaires « Que va devenir la Croix du Pape ? » (page /croix).
 *
 * Trois états, selon les dates du réglage « croix » :
 * - avant l'ouverture du vote : formulaire seul (« Recevez le lien de vote ») ;
 * - vote ouvert : le formulaire renvoie vers le site de vote, lien « Aller sur la page de vote sans m'inscrire », bandeau sur l'accueil ;
 * - vote clos : formulaire pour recevoir le résultat.
 *
 * Suivi sans données personnelles : un cookie aléatoire par navigateur (13 mois, mesure d'audience exemptée),
 * qui relie la visite, la saisie des coordonnées et le clic vers le vote. Source = utm_source du premier lien utilisé.
 */
class Croix
{
    public const COOKIE = 'croix';

    /** Pays essayés, dans l'ordre, pour un numéro écrit sans indicatif. */
    private const PHONE_REGIONS = ['FR', 'GP', 'MQ', 'GF', 'RE', 'YT', 'PM', 'BL', 'MF'];

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

    /** QR code (SVG) du lien de vote suivi, affiché sur ordinateur pour voter depuis son téléphone (en 4G si le Wi-Fi a déjà voté). */
    public static function voteQrCode(): string
    {
        return ConsultationStats::qrCode(route('croix.vote', ['utm_source' => 'qrcode', 'utm_medium' => 'page', 'utm_campaign' => 'croix']));
    }

    public static function recordVoteClick(Request $request): void
    {
        $visitor = self::visitor($request);
        if ($visitor && ! $visitor->vote_clicked_at) {
            $visitor->forceFill(['vote_clicked_at' => now(), 'source' => $visitor->source ?? self::source($request)])->save();
        }
    }

    /**
     * Envoie les coordonnées à Brevo (liste du concours), sans rien en garder sur le site : seul le visiteur
     * anonyme est marqué « a laissé ses coordonnées » pour les statistiques. Appel immédiat, pour pouvoir
     * prévenir la personne si Brevo ne répond pas.
     */
    public static function signup(Request $request, array $data): bool
    {
        $brevo = app(Brevo::class);
        $email = mb_strtolower(trim($data['email']));
        $list = (int) config('services.brevo.croix_list_id');
        $attributes = array_filter(['PRENOM' => self::clean($data['first_name'] ?? null), 'NOM' => self::clean($data['last_name'] ?? null)]);
        $phone = ! empty($data['consent_sms']) ? self::normalizePhone($data['phone'] ?? null) : null;

        // Si Brevo refuse le numéro (déjà associé à un autre contact…), on inscrit quand même l'adresse, sans le numéro.
        $ok = $phone
            ? $brevo->addContact($email, $list, $attributes + ['SMS' => $phone]) || $brevo->addContact($email, $list, $attributes)
            : $brevo->addContact($email, $list, $attributes);

        $visitor = self::visitor($request);
        if ($ok && $visitor && ! $visitor->signed_up_at) {
            $visitor->forceFill(['signed_up_at' => now()])->save();
        }

        return $ok;
    }

    /**
     * Numéro au format international E.164 attendu par Brevo, la France par défaut :
     * 06 12 34 56 78, 6.12.34.56.78, +33 (0)6 12 34 56 78, 0033 6… → +33612345678 ; +32 470 12 34 56 → +32470123456.
     * Null si le champ est vide ou si le numéro n'existe pas (libphonenumber, métadonnées de Google).
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }

        $util = PhoneNumberUtil::getInstance();
        // France d'abord, puis l'outre-mer (0690… Guadeloupe, 0696… Martinique, 0694… Guyane, 0692… Réunion, 0639… Mayotte),
        // dont les numéros s'écrivent aussi avec un 0 mais ont leur propre indicatif. Un numéro écrit avec + ou 00 garde le sien.
        foreach (self::PHONE_REGIONS as $region) {
            try {
                // « (0) » après l'indicatif, souvent recopié : +33 (0)6…
                $number = $util->parse(str_replace('(0)', '', $phone), $region);
            } catch (NumberParseException) {
                return null;
            }
            if ($util->isValidNumber($number)) {
                return $util->format($number, PhoneNumberFormat::E164);
            }
        }

        return null;
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
            'total' => $total,
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
