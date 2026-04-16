<?php

namespace App\Providers;

use App\Events\Order\DepositCallbackReceived;
use App\Events\Order\DepositFundSettled;
use App\Events\Order\WithdrawCallbackReceived;
use App\Events\Order\WithdrawFundReversed;
use App\Events\Order\WithdrawFundSettled;
use App\Listeners\Order\LogOrderStatusChange;
use App\Listeners\Order\NotifyMerchantOnDepositSuccess;
use App\Listeners\Order\NotifyMerchantOnWithdrawResult;
use App\Listeners\Order\ReverseWithdrawFunds;
use App\Listeners\Order\SettleDepositFunds;
use App\Listeners\Order\SettleWithdrawFunds;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Deposit callback listeners
        Event::listen(DepositCallbackReceived::class, SettleDepositFunds::class);
        Event::listen(DepositCallbackReceived::class, LogOrderStatusChange::class);

        // Withdraw callback listeners
        Event::listen(WithdrawCallbackReceived::class, SettleWithdrawFunds::class);
        Event::listen(WithdrawCallbackReceived::class, ReverseWithdrawFunds::class);
        Event::listen(WithdrawCallbackReceived::class, LogOrderStatusChange::class);

        // Fund settlement notifications
        Event::listen(DepositFundSettled::class, NotifyMerchantOnDepositSuccess::class);
        Event::listen(WithdrawFundSettled::class, NotifyMerchantOnWithdrawResult::class);
        Event::listen(WithdrawFundReversed::class, NotifyMerchantOnWithdrawResult::class);
    }
}
