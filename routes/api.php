<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\DeployController;
use Illuminate\Support\Facades\Route;

// Appelé par la GitHub Action après le déploiement FTP (jeton DEPLOY_TOKEN).
Route::post('/deploy', DeployController::class)->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'throttle:120,1'])->name('api.')->group(function () {
    Route::apiResource('articles', ArticleController::class)->scoped(['article' => 'slug']);
    Route::post('media', [MediaController::class, 'store'])->name('media.store');
});
