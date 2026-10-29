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
#[Description('Remplace le texte (Markdown) des mentions légales (page = mentions) ou de la politique de confidentialité (page = privacy). Lire d\'abord le texte actuel avec get-page.')]
class UpdateLegalPage extends Tool
{
    use JsonResponse;

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'page' => ['required', 'in:mentions,privacy'],
            'markdown' => ['required', 'string', 'max:50000'],
        ]);

        $legal = Setting::get('legal', []);
        $legal[$data['page']] = $data['markdown'];
        Setting::put('legal', $legal);

        $url = route($data['page'] === 'mentions' ? 'legal' : 'privacy');

        return $this->json("Page modifiée (en ligne immédiatement) : {$url}", ['page' => $data['page'], 'url' => $url]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'page' => $schema->string()->enum(['mentions', 'privacy'])->description('mentions (mentions légales) ou privacy (confidentialité).')->required(),
            'markdown' => $schema->string()->description('Texte complet de la page, en Markdown.')->required(),
        ];
    }
}
