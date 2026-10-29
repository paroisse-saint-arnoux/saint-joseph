<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Appelé par la GitHub Action juste après l'envoi FTP (pas d'accès SSH sur l'hébergement) :
 * applique les migrations et reconstruit les caches.
 */
class DeployController extends Controller
{
    public function __invoke(Request $request)
    {
        $token = (string) config('services.deploy.token');
        abort_unless($token !== '' && hash_equals($token, (string) $request->bearerToken()), 403);

        @set_time_limit(300);
        $output = [];

        foreach ([
            ['migrate', ['--force' => true]],
            ['storage:link', []],
            ['optimize:clear', []],
            ['optimize', []],
            ['filament:optimize', []],
        ] as [$command, $args]) {
            try {
                Artisan::call($command, $args);
                $output[] = "$ $command\n".trim(Artisan::output());
            } catch (\Throwable $e) {
                // storage:link peut échouer si le lien existe déjà : on continue.
                $output[] = "$ $command\nERREUR : ".$e->getMessage();
                if ($command === 'migrate') {
                    return response(implode("\n\n", $output), 500)->header('Content-Type', 'text/plain');
                }
            }
        }

        return response(implode("\n\n", $output))->header('Content-Type', 'text/plain');
    }
}
