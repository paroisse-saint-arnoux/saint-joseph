<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CroixController;
use App\Http\Controllers\DonController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HuileController;
use App\Http\Controllers\LibraryArchiveController;
use App\Http\Controllers\LibrarySearchController;
use App\Http\Controllers\MagicLinkController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OilOrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\ShortLinkController;
use App\Http\Controllers\SitemapController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Domaine des liens courts (sj.saint-arnoux.fr, config app.short_url) : redirection sans session ni cookie,
// rien n'est compté. En premier : ces routes ne répondent que sur ce domaine.
Route::domain(parse_url(config('app.short_url'), PHP_URL_HOST))
    ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class, EncryptCookies::class, AddQueuedCookiesToResponse::class])
    ->group(function () {
        Route::get('/{path?}', [ShortLinkController::class, 'short'])->where('path', '.*')->name('short-link');
    });

Route::get('/', HomeController::class)->name('home');

Route::get('/le-projet', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/le-projet/{category}', [ArticleController::class, 'index'])->name('articles.category');
Route::get('/le-projet/{category}/{slug}', [ArticleController::class, 'show'])->name('articles.show');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->middleware('throttle:5,10,contact')->name('contact.send');
Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:10,10,newsletter')->name('newsletter');

// Formulaire de don RGIVE intégré dans le site.
Route::get('/don', DonController::class)->name('don');

Route::get('/mentions-legales', [PageController::class, 'legal'])->name('legal');
Route::get('/confidentialite', [PageController::class, 'privacy'])->name('privacy');

// Consultation sur les projets d'architecture (QR code dans l'exposition des maquettes).
Route::prefix('consultation')->name('consultation.')->controller(ConsultationController::class)->group(function () {
    Route::get('/', 'start')->name('start');
    Route::post('/', 'identify')->middleware('throttle:consultation-identify')->name('identify');
    Route::get('/merci', 'thanks')->name('thanks');
    Route::post('/changer', 'forget')->name('forget');
    Route::get('/projet/{project}', 'show')->name('project');
    Route::post('/projet/{project}', 'save')->middleware('throttle:consultation-save')->name('save');
});

// Concours S.O.S Calvaires « Que va devenir la Croix du Pape ? ».
Route::get('/croix', [CroixController::class, 'show'])->name('croix');
Route::post('/croix', [CroixController::class, 'store'])->middleware('throttle:croix-signup')->name('croix.store');
Route::get('/croix/vote', [CroixController::class, 'vote'])->name('croix.vote');
Route::redirect('/croix/liens', '/qrcodes', 301); // ancienne adresse du générateur de liens

// Générateur de liens UTM, liens courts et QR codes (public, non référencé).
Route::get('/qrcodes', [QrCodeController::class, 'show'])->name('qrcodes');
Route::post('/qrcodes', [QrCodeController::class, 'store'])->middleware('throttle:30,10,qrcodes')->name('qrcodes.store');

// Opération « huile d'olive » : page ouverte par le QR code des étiquettes (non référencée).
Route::get('/huile', [HuileController::class, 'show'])->name('huile');
Route::post('/huile', [HuileController::class, 'store'])->middleware('throttle:huile-signup')->name('huile.store');
// Réservation de bouteilles d'huile, à retirer à la kermesse (non référencée).
Route::get('/commande-huile', [OilOrderController::class, 'show'])->name('huile.order');
Route::post('/commande-huile', [OilOrderController::class, 'store'])->middleware('throttle:huile-order')->name('huile.order.store');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Admin : bibliothèque d'images (hors /admin, où Filament capte /admin/bibliotheque/{id}).
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/bibliotheque/archive', LibraryArchiveController::class)->name('library.archive');
    Route::get('/bibliotheque/recherche', LibrarySearchController::class)->name('library.search');
});

Route::middleware('signed')->group(function () {
    Route::get('/connexion/{token}', [MagicLinkController::class, 'show'])->name('magic-link.show');
    Route::post('/connexion/{token}', [MagicLinkController::class, 'login'])->middleware('throttle:10,1,magic-link')->name('magic-link.login');
});

// Lien court arrivé sur le domaine principal (sj.saint-arnoux.fr redirigé en gardant le chemin).
Route::fallback([ShortLinkController::class, 'fallback']);
