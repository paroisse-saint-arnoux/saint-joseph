<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Téléchargement d'une image depuis une adresse web (outil du connecteur Claude), pour la bibliothèque.
 * Protégé contre les adresses internes (SSRF) : seuls http(s) vers des IP publiques, 15 Mo max.
 */
class RemoteImage
{
    public const MAX_BYTES = 15 * 1024 * 1024;

    /** Télécharge l'image dans un fichier temporaire (à supprimer par l'appelant) ; renvoie [chemin, nom d'origine]. */
    public static function download(string $url): array
    {
        self::assertPublicUrl($url);

        $response = Http::timeout(20)
            ->withHeaders(['User-Agent' => 'SaintJosephSite/1.0'])
            ->withOptions([
                'allow_redirects' => [
                    'max' => 3,
                    'protocols' => ['http', 'https'],
                    'on_redirect' => fn ($request, $response, $uri) => self::assertPublicUrl((string) $uri),
                ],
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new InvalidArgumentException("Téléchargement impossible ({$response->status()}).");
        }
        if (strlen($response->body()) > self::MAX_BYTES) {
            throw new InvalidArgumentException('Image trop lourde (15 Mo maximum).');
        }
        if (! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
            throw new InvalidArgumentException("L'adresse ne renvoie pas une image.");
        }

        $tmp = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($tmp, $response->body());
        $name = Str::limit(basename((string) parse_url($url, PHP_URL_PATH)) ?: 'image', 80, '');

        return [$tmp, $name];
    }

    public static function assertPublicUrl(string $url): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (! in_array($scheme, ['http', 'https'], true) || ! $host) {
            throw new InvalidArgumentException('Adresse invalide : seules les adresses http(s) sont acceptées.');
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        foreach (array_merge($ips, array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6')) as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new InvalidArgumentException('Adresse non autorisée.');
            }
        }
        if ($ips === []) {
            throw new InvalidArgumentException('Nom de domaine introuvable.');
        }
    }
}
