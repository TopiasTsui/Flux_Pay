<?php

use App\Http\Controllers\Api\MerchantApiController;
use App\Http\Controllers\Api\ProviderCallbackController;
use Illuminate\Support\Facades\Route;

// Merchant payment API
Route::middleware(['request.logging', 'merchant.auth'])->group(function () {
    Route::post('deposit/apply', [MerchantApiController::class, 'depositApply']);
    Route::post('deposit/query', [MerchantApiController::class, 'depositQuery']);
    Route::post('withdraw/apply', [MerchantApiController::class, 'withdrawApply']);
    Route::post('withdraw/query', [MerchantApiController::class, 'withdrawQuery']);
    Route::post('balance/query', [MerchantApiController::class, 'balanceQuery']);
});

// Provider callbacks
Route::middleware(['request.logging', 'provider.callback'])->group(function () {
    Route::post('deposit/{vendor}/callback', [ProviderCallbackController::class, 'depositCallback'])
        ->name('callback.deposit');
    Route::post('withdraw/{vendor}/callback', [ProviderCallbackController::class, 'withdrawCallback'])
        ->name('callback.withdraw');
});
