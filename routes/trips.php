<?php

use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\TripEditController;
use Illuminate\Support\Facades\Route;

Route::get('/trajets/{trip}/modifier', [TripEditController::class, 'edit'])->name('trips.edit');
Route::put('/trajets/{trip}', [TripEditController::class, 'update'])->name('trips.update');
Route::get('/devenir-livreur', [MarketplaceController::class, 'apply'])->name('drivers.apply');
Route::post('/devenir-livreur', [MarketplaceController::class, 'storeApplication'])->middleware('throttle:5,1')->name('drivers.store');
Route::get('/trajets/nouveau', [MarketplaceController::class, 'newTrip'])->name('trips.create');
Route::post('/trajets', [MarketplaceController::class, 'storeTrip'])->name('trips.store');
Route::post('/trajets/{trip}/fermer', [MarketplaceController::class, 'closeTrip'])->name('trips.close');
Route::post('/trajets/{trip}/reserver', [MarketplaceController::class, 'book'])->middleware('throttle:10,1')->name('bookings.store');
