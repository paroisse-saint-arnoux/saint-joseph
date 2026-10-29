<?php

namespace App\Mcp\Tools\Concerns;

use Laravel\Mcp\Response;

trait JsonResponse
{
    /** Réponse lisible par Claude : une phrase d'explication puis les données en JSON. */
    protected function json(string $message, array $data): Response
    {
        return Response::text($message."\n\n".json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
