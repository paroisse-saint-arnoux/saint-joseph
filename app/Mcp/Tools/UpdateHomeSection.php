<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\JsonResponse;
use App\Models\Setting;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
#[Description(<<<'TXT'
Modifie une section de la page d'accueil. Lire d'abord la page avec get-page (page = "/").
« content » ne contient que les champs à changer, avec les mêmes noms et la même forme que dans get-page ;
une liste (facts, stats, buildings, images, steps) fournie remplace toute la liste.
Dans les titres et textes courts, *texte* = mise en valeur (italique doré) et **texte** = gras.
Les montants de la collecte (raised, goal) sont des nombres en euros.
TXT)]
class UpdateHomeSection extends Tool
{
    use JsonResponse;

    private const SECTIONS = ['hero', 'decouvrir', 'projet', 'avancement', 'collecte'];

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'section' => ['required', 'in:'.implode(',', self::SECTIONS)],
            'content' => ['required', 'array'],
            'content.raised' => ['sometimes', 'numeric', 'min:0'],
            'content.goal' => ['sometimes', 'numeric', 'min:1'],
            'content.steps.*.status' => ['sometimes', 'in:done,now,todo'],
        ]);

        $home = Setting::get('home', []);
        $current = $home[$data['section']] ?? [];
        $unknown = array_diff(array_keys($data['content']), array_keys($current));
        if ($unknown !== []) {
            return Response::error('Champs inconnus pour la section '.$data['section'].' : '.implode(', ', $unknown)
                .'. Champs possibles : '.implode(', ', array_keys($current)).'.');
        }

        $home[$data['section']] = array_replace($current, $data['content']);
        Setting::put('home', $home);

        return $this->json(
            "Section « {$data['section']} » de la page d'accueil modifiée (en ligne immédiatement). Voir : ".url('/').' — admin : '.url('/admin/page-accueil'),
            $home[$data['section']],
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'section' => $schema->string()->enum(self::SECTIONS)->description('Section à modifier.')->required(),
            'content' => $schema->object()->description('Champs à modifier dans la section (voir get-page).')->required(),
        ];
    }
}
