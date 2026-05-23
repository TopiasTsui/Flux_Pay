<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProviderPaymentType;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ResetProviderDailyLimitsCommand extends Command
{
    protected $signature = 'flux:reset-provider-daily-limits {--force : 忽略 reset_time 立即重置所有通道}';

    protected $description = '按 reset_time 重置 provider_payment_types 的 current_daily_amount';

    public function handle(): int
    {
        $query = ProviderPaymentType::query()->where('current_daily_amount', '>', 0);

        if (! $this->option('force')) {
            $now = Carbon::now()->format('H:i');
            $query->where('reset_time', $now);
        }

        $affected = $query->update(['current_daily_amount' => 0]);

        $this->info("Reset current_daily_amount on {$affected} provider_payment_types.");

        return self::SUCCESS;
    }
}
