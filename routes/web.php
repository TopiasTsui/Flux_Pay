<?php

use App\Http\Controllers\Api\FrontendPayController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pay/{token}', [FrontendPayController::class, 'cashier'])->name('pay.cashier');
