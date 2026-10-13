<?php

use App\Http\Controllers\AccountAreaController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PrivacyController;
use Illuminate\Support\Facades\Route;

Route::post('/administration/documents/{document}', [AccountAreaController::class, 'review'])->name('account.review');
Route::get('/administration/dossiers', [AccountAreaController::class, 'adminDossiers'])->name('account.admin');
Route::get('/administration/dossiers/{user}', [AccountAreaController::class, 'adminDossier'])->name('account.admin.show');
Route::post('/administration/droits/{privacyRequest}', [PrivacyController::class, 'review'])->name('admin.privacy');
Route::get('/administration', [AdminController::class, 'index'])->name('admin');
Route::post('/administration/comptes/{user}', [AdminController::class, 'user'])->name('admin.user');
Route::post('/administration/candidatures/{application}', [AdminController::class, 'application'])->name('admin.application');
Route::get('/administration/candidatures/{application}/{kind}', [AdminController::class, 'document'])->name('admin.document');
