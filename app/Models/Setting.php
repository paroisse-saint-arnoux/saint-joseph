<?php

namespace App\Models;

use App\Support\LibraryUsages;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Contenus éditables regroupés par clé : 'home' (page d'accueil), 'site'
 * (pied de page, réseaux, liens), 'legal' (mentions légales, confidentialité).
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    protected static function booted(): void
    {
        // Index des images de la bibliothèque utilisées par la page d'accueil.
        static::saved(function (Setting $setting) {
            if ($setting->key === 'home') {
                LibraryUsages::syncHome($setting->value ?? []);
            }
        });
    }

    /** Lecture en notation pointée : Setting::get('home.collecte.raised'). */
    public static function get(string $path, mixed $default = null): mixed
    {
        [$key, $rest] = array_pad(explode('.', $path, 2), 2, null);

        self::$cache[$key] ??= static::find($key)?->value ?? [];

        return $rest === null ? (self::$cache[$key] ?: $default) : Arr::get(self::$cache[$key], $rest, $default);
    }

    public static function put(string $key, array $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache[$key] = $value;
    }
}
