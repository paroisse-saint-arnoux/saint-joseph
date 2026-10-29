<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryArchiveController;
use App\Http\Controllers\LibrarySearchController;
use App\Http\Controllers\MagicLinkController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/le-projet', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/le-projet/{category}', [ArticleController::class, 'index'])->name('articles.category');
Route::get('/le-projet/{category}/{slug}', [ArticleController::class, 'show'])->name('articles.show');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->middleware('throttle:5,10')->name('contact.send');
Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:10,10')->name('newsletter');

Route::get('/mentions-legales', [PageController::class, 'legal'])->name('legal');
Route::get('/confidentialite', [PageController::class, 'privacy'])->name('privacy');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Admin : bibliothèque d'images (hors /admin, où Filament capte /admin/bibliotheque/{id}).
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/bibliotheque/archive', LibraryArchiveController::class)->name('library.archive');
    Route::get('/bibliotheque/recherche', LibrarySearchController::class)->name('library.search');
});

Route::middleware('signed')->group(function () {
    Route::get('/connexion/{token}', [MagicLinkController::class, 'show'])->name('magic-link.show');
    Route::post('/connexion/{token}', [MagicLinkController::class, 'login'])->middleware('throttle:10,1')->name('magic-link.login');
});
