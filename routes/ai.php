<?php

use App\Mcp\Servers\SiteServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

// Connecteur Claude : découverte OAuth (.well-known) + inscription automatique du client (limitée).
Route::middleware('throttle:10,1')->group(function () {
    Mcp::oauthRoutes();
});

// Serveur MCP : https://saint-joseph.saint-arnoux.fr/mcp
Mcp::web('/mcp', SiteServer::class)
    ->middleware(['auth:api', 'active', 'throttle:120,1']);
