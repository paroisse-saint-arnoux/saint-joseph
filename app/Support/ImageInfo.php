<?php

namespace App\Support;

/**
 * Informations lisibles sur un fichier image : format et compression.
 * Pour un JPEG, la qualité est estimée à partir de la table de quantification de luminance
 * (même méthode qu'ImageMagick : comparaison avec la table standard mise à l'échelle).
 */
class ImageInfo
{
    private const FORMATS = [
        'image/jpeg' => 'JPEG',
        'image/png' => 'PNG',
        'image/webp' => 'WebP',
        'image/gif' => 'GIF',
        'image/bmp' => 'BMP',
        'image/x-ms-bmp' => 'BMP',
    ];

    // Table de quantification de luminance standard (JPEG, annexe K), dans l'ordre zigzag du fichier.
    private const LUMINANCE = [
        16, 11, 12, 14, 12, 10, 16, 14, 13, 14, 18, 17, 16, 19, 24, 40, 26, 24, 22, 22, 24, 49, 35, 37,
        29, 40, 58, 51, 61, 60, 57, 51, 56, 55, 64, 72, 92, 78, 64, 68, 87, 69, 55, 56, 80, 109, 81, 87,
        95, 98, 103, 104, 103, 62, 77, 113, 121, 112, 100, 120, 92, 101, 103, 99,
    ];

    public static function format(string $mime): string
    {
        return self::FORMATS[$mime] ?? $mime;
    }

    public static function compression(string $bytes, string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => ($quality = self::jpegQuality($bytes)) ? "avec perte, qualité ≈ {$quality} %" : 'avec perte',
            'image/png' => 'sans perte',
            'image/gif' => 'sans perte, 256 couleurs maximum',
            'image/webp' => self::webpIsLossless($bytes) ? 'sans perte' : 'avec perte',
            'image/bmp', 'image/x-ms-bmp' => 'non compressée',
            default => 'inconnue',
        };
    }

    public static function jpegQuality(string $bytes): ?int
    {
        $table = self::luminanceTable($bytes);
        if (! $table) {
            return null;
        }

        $best = null;
        $bestError = PHP_INT_MAX;
        for ($quality = 1; $quality <= 100; $quality++) {
            $scale = $quality < 50 ? 5000 / $quality : 200 - 2 * $quality;
            $error = 0;
            foreach (self::LUMINANCE as $i => $value) {
                $expected = max(1, min(255, (int) floor(($value * $scale + 50) / 100)));
                $error += abs($expected - $table[$i]);
            }
            if ($error < $bestError) {
                [$best, $bestError] = [$quality, $error];
            }
        }

        return $best;
    }

    /** @return array<int, int>|null 64 valeurs de la table de luminance (id 0) */
    private static function luminanceTable(string $bytes): ?array
    {
        $length = strlen($bytes);
        $offset = 2; // après SOI (FFD8)
        while ($offset + 4 <= $length) {
            if (ord($bytes[$offset]) !== 0xFF) {
                return null;
            }
            $marker = ord($bytes[$offset + 1]);
            $size = unpack('n', substr($bytes, $offset + 2, 2))[1];

            if ($marker === 0xDB) { // DQT : une ou plusieurs tables
                $position = $offset + 4;
                $end = $offset + 2 + $size;
                while ($position < $end) {
                    $info = ord($bytes[$position]);
                    $precision = $info >> 4; // 0 = 8 bits, 1 = 16 bits
                    $values = $precision
                        ? array_values(unpack('n64', substr($bytes, $position + 1, 128)))
                        : array_values(unpack('C64', substr($bytes, $position + 1, 64)));
                    if (($info & 0x0F) === 0) {
                        return $values;
                    }
                    $position += 1 + ($precision ? 128 : 64);
                }
            }
            if ($marker === 0xDA) { // début des données : plus d'en-têtes
                return null;
            }
            $offset += 2 + $size;
        }

        return null;
    }

    private static function webpIsLossless(string $bytes): bool
    {
        return substr($bytes, 12, 4) === 'VP8L' || (substr($bytes, 12, 4) === 'VP8X' && str_contains(substr($bytes, 0, 4096), 'VP8L'));
    }
}
