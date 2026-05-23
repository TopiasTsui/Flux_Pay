<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Jobs\OrderQueryPollingJob;
use App\Models\DepositOrder;
use App\Models\WithdrawOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class StalledOrderCheckCommand extends Command
{
    protected $signature = 'flux:stalled-order-check';

    protected $description = '扫描卡在 SENT_TO_PROVIDER 的订单并触发轮询查询';

    public function handle(): int
    {
        $minAge = (int) config('fluxpay.stalled_min_age_seconds', 120);
        $maxAge = (int) config('fluxpay.stalled_max_age_seconds', 86400);

        $upperBound = Carbon::now()->subSeconds($minAge);
        $lowerBound = Carbon::now()->subSeconds($maxAge);

        $depositCount = $this->dispatchFor(DepositOrder::class, 'deposit', $lowerBound, $upperBound);
        $withdrawCount = $this->dispatchFor(WithdrawOrder::class, 'withdraw', $lowerBound, $upperBound);

        $this->info("Dispatched polling for {$depositCount} deposit + {$withdrawCount} withdraw stalled orders.");

        return self::SUCCESS;
    }

    private function dispatchFor(string $modelClass, string $type, Carbon $lower, Carbon $upper): int
    {
        $count = 0;

        $modelClass::query()
            ->select(['id'])
            ->where('status', OrderStatus::SENT_TO_PROVIDER->value)
            ->whereBetween('provider_apply_time', [$lower, $upper])
            ->orderBy('id')
            ->chunkById(200, function ($orders) use ($type, &$count) {
                foreach ($orders as $order) {
                    OrderQueryPollingJob::dispatch($type, $order->id);
                    $count++;
                }
            });

        return $count;
    }
}
