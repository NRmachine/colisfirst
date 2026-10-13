<?php

use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/reservations/{booking}/payer', [PaymentController::class, 'checkout'])->middleware('throttle:10,1')->name('payments.checkout');
Route::get('/reservations/{booking}', [MarketplaceController::class, 'booking'])->name('bookings.show');
Route::post('/reservations/{booking}/statut', [MarketplaceController::class, 'transition'])->name('bookings.transition');
Route::post('/reservations/{booking}/paiement-demo', [MarketplaceController::class, 'pay'])->name('bookings.pay');
Route::post('/reservations/{booking}/messages', [MarketplaceController::class, 'message'])->middleware('throttle:20,1')->name('bookings.message');
