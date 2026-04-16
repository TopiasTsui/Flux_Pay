<?php

use App\Http\Controllers\Api\FrontendPayController;
use App\Http\Controllers\Auth\OrchidLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Override Orchid login POST to use username instead of email
Route::post('admin/login', [OrchidLoginController::class, 'login'])
    ->name('platform.login.auth')
    ->middleware('web');

Route::get('/pay/{token}', [FrontendPayController::class, 'cashier'])->name('pay.cashier');
