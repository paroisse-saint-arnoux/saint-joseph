<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Les liens « Faire un don » mènent désormais à la page /don du site (formulaire RGIVE intégré)
// au lieu de la page de campagne du diocèse : réglages (lien de don, liens utiles, textes) et articles.
return new class extends Migration
{
    private const PATTERN = '~https?://don-nice\.catholique\.fr/centre-paroissial-st-arnoux-rouret/?~';

    public function up(): void
    {
        foreach (DB::table('settings')->get() as $row) {
            $value = json_decode($row->value, true);
            if (! is_array($value)) {
                continue;
            }

            if ($row->key === 'site' && isset($value['useful_links'])) {
                // Doublon du lien « Faire un don » du pied de page.
                $value['useful_links'] = array_values(array_filter(
                    $value['useful_links'],
                    fn ($link) => ! preg_match(self::PATTERN, $link['url'] ?? ''),
                ));
            }

            $new = $this->replace($value);
            if ($new !== json_decode($row->value, true)) {
                DB::table('settings')->where('key', $row->key)->update(['value' => json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }
        }

        foreach (DB::table('articles')->get(['id', 'body', 'excerpt']) as $article) {
            $changes = [];
            foreach (['body', 'excerpt'] as $column) {
                if (is_string($article->$column) && preg_match(self::PATTERN, $article->$column)) {
                    $changes[$column] = preg_replace(self::PATTERN, '/don', $article->$column);
                }
            }
            if ($changes) {
                DB::table('articles')->where('id', $article->id)->update($changes);
            }
        }
    }

    private function replace(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->replace($v), $value);
        }

        return is_string($value) ? preg_replace(self::PATTERN, '/don', $value) : $value;
    }

    public function down(): void
    {
        // Pas de retour en arrière automatique : le lien se règle dans Admin > Autres textes du site.
    }
};
