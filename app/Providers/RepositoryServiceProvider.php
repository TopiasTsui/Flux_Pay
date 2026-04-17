<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        \App\Contracts\Repositories\MerchantRepositoryInterface::class => \App\Repositories\MerchantRepository::class,
        \App\Contracts\Repositories\AgentRepositoryInterface::class => \App\Repositories\AgentRepository::class,
        \App\Contracts\Repositories\ProviderRepositoryInterface::class => \App\Repositories\ProviderRepository::class,
        \App\Contracts\Repositories\DepositOrderRepositoryInterface::class => \App\Repositories\DepositOrderRepository::class,
        \App\Contracts\Repositories\WithdrawOrderRepositoryInterface::class => \App\Repositories\WithdrawOrderRepository::class,
        \App\Contracts\Repositories\MerchantWalletRecordRepositoryInterface::class => \App\Repositories\MerchantWalletRecordRepository::class,
        \App\Contracts\Repositories\AgentWalletRecordRepositoryInterface::class => \App\Repositories\AgentWalletRecordRepository::class,
        \App\Contracts\Repositories\ProviderWalletRecordRepositoryInterface::class => \App\Repositories\ProviderWalletRecordRepository::class,
        \App\Contracts\Repositories\TranslationRepositoryInterface::class => \App\Repositories\TranslationRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
