<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\JsonResponse;
use App\Models\Article;
use App\Models\Setting;
use App\Support\ArticleData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description(<<<'TXT'
Lit le contenu modifiable d'une page du site à partir de son adresse (ou du slug d'un article) :
- un article (/le-projet/{categorie}/{slug}, lien d'aperçu ou lien d'édition de l'admin) → tous ses champs, corps compris ;
- la page d'accueil (/) → ses sections (hero, decouvrir, projet, avancement, collecte) ;
- /mentions-legales ou /confidentialite → le texte Markdown.
À appeler avant toute modification, pour partir du contenu actuel.
TXT)]
class GetPage extends Tool
{
    use JsonResponse;

    public function handle(Request $request): Response
    {
        $reference = trim((string) $request->validate(['page' => ['required', 'string', 'max:500']])['page']);
        $path = trim((string) parse_url($reference, PHP_URL_PATH), '/');

        if (($path === '' && str_contains($reference, '/')) || in_array(strtolower($reference), ['accueil', 'home'], true)) {
            return $this->json(
                "Page d'accueil. Modifier une section avec update-home-section. Admin : ".url('/admin/page-accueil'),
                Setting::get('home', []),
            );
        }

        if (in_array($path, ['mentions-legales', 'confidentialite'], true)) {
            $key = $path === 'mentions-legales' ? 'mentions' : 'privacy';

            return $this->json(
                "Page {$path} (Markdown). Modifier avec update-legal-page (page = {$key}).",
                ['page' => $key, 'markdown' => Setting::get('legal.'.$key, '')],
            );
        }

        $article = Article::findByReference($reference);
        if (! $article) {
            return Response::error("Aucune page trouvée pour « {$reference} ». Utiliser list-articles pour retrouver un article.");
        }

        return $this->json('Article. Modifier avec update-article (article = '.$article->slug.').', ArticleData::present($article, withBody: true));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'page' => $schema->string()->description('Adresse de la page (ex. https://saint-joseph.saint-arnoux.fr/le-projet/budget/le-budget-du-projet, ou / pour l\'accueil) ou slug d\'un article.')->required(),
        ];
    }
}
