<?php

use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\SearchHistoryController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'home'])->name('home');
Route::get('/trajets', [MarketplaceController::class, 'trips'])->name('trips.index');
Route::delete('/historique-recherches', [SearchHistoryController::class, 'destroy'])->middleware('throttle:20,1')->name('search-history.destroy');
Route::view('/comment-ca-marche', 'market.how')->name('how');
Route::view('/confiance', 'market.trust')->name('trust');
Route::view('/conditions', 'market.terms')->name('terms');
Route::view('/confidentialite', 'market.privacy')->name('privacy');
Route::view('/mentions-legales', 'market.legal')->name('legal');
Route::view('/cookies', 'market.cookies')->name('cookies');
Route::view('/accessibilite', 'market.accessibility')->name('accessibility');
Route::view('/cadre-reglementaire', 'market.regulations')->name('regulations');
Route::post('/cookies/preferences', [PrivacyController::class, 'cookies'])->name('cookies.save');
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
Route::middleware('auth')->group(function () {
    require __DIR__.'/account.php';
    require __DIR__.'/trips.php';
    require __DIR__.'/bookings.php';
    require __DIR__.'/admin.php';
});
Route::get('/trajets/{trip}', [MarketplaceController::class, 'trip'])->name('trips.show');

require __DIR__.'/settings.php';
