<?php

namespace App\Support;

use App\Enums\Category;
use App\Models\Article;
use App\Models\ShortLink;
use Illuminate\Support\Str;

/**
 * Page /qrcodes : lien vers une page publique du site avec balises UTM, raccourci en
 * sj.saint-arnoux.fr/{code} (config app.short_url), et son QR code. Balises : utm_source/utm_medium (liste SOURCES),
 * utm_campaign (proposé selon la page, modifiable), utm_content (facultatif).
 * La redirection du lien court n'enregistre rien : seule la page d'arrivée est comptée (Matomo, suivi de /croix).
 */
class ShortLinks
{
    /** Où le lien est diffusé : utm_source => [libellé, utm_medium]. */
    public const SOURCES = [
        'affiche' => ['Affiche (QR code)', 'print'],
        'flyer' => ['Flyer, tract (QR code)', 'print'],
        'etiquette' => ['Étiquette, autocollant (QR code)', 'print'],
        'feuille-paroissiale' => ['Feuille paroissiale', 'print'],
        'brevo' => ['Newsletter / campagne Brevo', 'email'],
        'email' => ['E-mail personnel', 'email'],
        'whatsapp' => ['WhatsApp (message ou groupe)', 'message'],
        'sms' => ['SMS', 'message'],
        'facebook' => ['Facebook', 'social'],
        'instagram' => ['Instagram', 'social'],
        'linkedin' => ['LinkedIn', 'social'],
        'saint-arnoux' => ['Site de la paroisse Saint-Arnoux', 'site'],
    ];

    /** Caractères des codes, sans ceux qui se confondent (0/o, 1/l/i). */
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    public const CODE_PATTERN = '[a-z2-9]{5}';

    /**
     * Pages publiques que l'on peut viser, par groupe : [groupe => [chemin => libellé]].
     *
     * @return array<string, array<string, string>>
     */
    public static function targets(): array
    {
        $articles = Article::published()->ordered()->get(['id', 'title', 'slug', 'category']);

        return [
            'Pages du site' => [
                '/' => 'Accueil',
                '/#collecte' => 'Accueil — la collecte (clocher)',
                route('don', absolute: false) => 'Faire un don',
                route('articles.index', absolute: false) => 'Le projet (tous les articles)',
                route('contact', absolute: false) => 'Contact',
                route('legal', absolute: false) => 'Mentions légales',
                route('privacy', absolute: false) => 'Confidentialité',
            ],
            'Opérations' => [
                route('croix', absolute: false) => 'Croix du Pape',
                route('croix.vote', absolute: false) => 'Croix du Pape — directement au site de vote',
                route('huile', absolute: false) => 'Huile d\'olive',
                route('consultation.start', absolute: false) => 'Consultation sur les projets d\'architecture',
            ],
            'Catégories d\'articles' => collect(Category::cases())
                ->filter(fn (Category $c) => $articles->contains('category', $c))
                ->mapWithKeys(fn (Category $c) => [route('articles.category', $c, absolute: false) => $c->getLabel()])
                ->all(),
            'Articles' => $articles
                ->mapWithKeys(fn (Article $a) => [parse_url($a->url(), PHP_URL_PATH) => $a->title])
                ->all(),
        ];
    }

    public static function isTarget(string $path): bool
    {
        return collect(self::targets())->contains(fn (array $group) => array_key_exists($path, $group));
    }

    /** utm_campaign proposé pour la page visée (modifiable) : les opérations ont leur campagne, le reste du site « saint-joseph ». */
    public static function campaignFor(string $path): string
    {
        $path = explode('#', $path)[0];
        foreach (['croix', 'huile', 'consultation', 'don'] as $campaign) {
            if ($path === "/$campaign" || str_starts_with($path, "/$campaign/")) {
                return $campaign;
            }
        }

        return 'saint-joseph';
    }

    /**
     * Campagnes à suggérer : celles par défaut, puis celles déjà utilisées.
     *
     * @return list<string>
     */
    public static function campaigns(): array
    {
        return collect(['saint-joseph', 'don', 'croix', 'huile', 'consultation'])
            ->merge(ShortLink::distinct()->orderBy('utm_campaign')->pluck('utm_campaign'))
            ->unique()->values()->all();
    }

    /** Lien complet : la page, puis les balises UTM (avant l'ancre éventuelle). */
    public static function longUrl(string $path, string $source, string $campaign, ?string $content): string
    {
        [$path, $fragment] = array_pad(explode('#', $path, 2), 2, null);

        $query = http_build_query(array_filter([
            'utm_source' => $source,
            'utm_medium' => self::SOURCES[$source][1],
            'utm_campaign' => $campaign,
            'utm_content' => $content,
        ]));

        return url($path).'?'.$query.($fragment ? '#'.$fragment : '');
    }

    /** Lien court du lien complet (le même s'il a déjà été créé). */
    public static function create(string $path, string $source, ?string $campaign, ?string $content): ShortLink
    {
        $campaign = Str::slug((string) $campaign) ?: self::campaignFor($path);
        $content = Str::slug((string) $content) ?: null;
        $url = self::longUrl($path, $source, $campaign, $content);
        $hash = hash('sha256', $url);

        return ShortLink::firstWhere('url_hash', $hash) ?? ShortLink::create([
            'code' => self::newCode(),
            'url' => $url,
            'url_hash' => $hash,
            'target' => $path,
            'utm_source' => $source,
            'utm_medium' => self::SOURCES[$source][1],
            'utm_campaign' => $campaign,
            'utm_content' => $content,
        ]);
    }

    /** 5 caractères dont au moins un chiffre : un code ne peut pas prendre la place d'une page du site (/croix, /huile…). */
    private static function newCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 5; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (! preg_match('/\d/', $code) || ShortLink::where('code', $code)->exists());

        return $code;
    }
}
