<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\MediaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:120,1'])->name('api.')->group(function () {
    Route::apiResource('articles', ArticleController::class)->scoped(['article' => 'slug']);
    Route::post('media', [MediaController::class, 'store'])->name('media.store');
});
