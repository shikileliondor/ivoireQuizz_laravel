<?php

use App\Http\Controllers\Site\AccountDeletionController;
use Illuminate\Support\Facades\Route;

// Site public : vitrine et pages exigées par Google Play.
Route::name('site.')->group(function (): void {
    Route::view('/', 'site.home')->name('home');
    Route::view('/confidentialite', 'site.privacy')->name('privacy');
    Route::view('/cookies', 'site.cookies')->name('cookies');
    Route::view('/conditions-utilisation', 'site.terms')->name('terms');
    Route::view('/mentions-legales', 'site.legal')->name('legal');

    Route::get('/suppression-compte', [AccountDeletionController::class, 'create'])->name('account-deletion');
    Route::post('/suppression-compte', [AccountDeletionController::class, 'store'])->name('account-deletion.store');
});
