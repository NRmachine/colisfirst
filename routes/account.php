<?php

use App\Http\Controllers\AccountAreaController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PrivacyController;
use Illuminate\Support\Facades\Route;

Route::get('/notifications', [AlertController::class, 'index'])->name('alerts.index');
Route::post('/notifications/{alert}/lue', [AlertController::class, 'read'])->name('alerts.read');
Route::get('/mon-dossier', [AccountAreaController::class, 'dossier'])->name('account.dossier');
Route::post('/mon-dossier', [AccountAreaController::class, 'save'])->name('account.save');
Route::post('/mon-dossier/documents', [AccountAreaController::class, 'upload'])->middleware('throttle:10,1')->name('account.upload');
Route::get('/documents-prives/{document}', [AccountAreaController::class, 'download'])->name('account.download');
Route::get('/support', [AccountAreaController::class, 'support'])->name('support.index');
Route::post('/support', [AccountAreaController::class, 'createTicket'])->middleware('throttle:5,60')->name('support.store');
Route::get('/support/{ticket}', [AccountAreaController::class, 'ticket'])->name('support.show');
Route::post('/support/{ticket}', [AccountAreaController::class, 'reply'])->middleware('throttle:20,1')->name('support.reply');
Route::get('/livreur/paiements', [PaymentController::class, 'onboardingPage'])->name('payments.onboarding.page');
Route::post('/livreur/paiements', [PaymentController::class, 'onboarding'])->middleware('throttle:5,1')->name('payments.onboarding');
Route::get('/mes-donnees', [PrivacyController::class, 'index'])->name('privacy.requests');
Route::get('/mes-donnees/export', [PrivacyController::class, 'export'])->middleware('throttle:5,1')->name('privacy.export');
Route::post('/mes-donnees', [PrivacyController::class, 'store'])->middleware('throttle:3,60')->name('privacy.store');
Route::get('/dashboard', [MarketplaceController::class, 'dashboard'])->name('dashboard');
Route::get('/profil', [MarketplaceController::class, 'profile'])->name('profile');
Route::post('/profil', [MarketplaceController::class, 'updateProfile'])->name('profile.save');
