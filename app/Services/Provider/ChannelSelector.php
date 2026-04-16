<?php

declare(strict_types=1);

namespace App\Services\Provider;

use App\Enums\EntityStatus;
use App\Enums\PaymentDirection;
use App\Exceptions\ChannelUnavailableException;
use App\Helpers\MoneyHelper;
use App\Models\MerchantProviderPaymentType;
use App\Models\ProviderPaymentType;

class ChannelSelector
{
    public function select(
        int $merchantId,
        PaymentDirection $direction,
        string $amount,
        ?string $paymentTypeCode = null,
    ): ProviderPaymentType {
        $query = MerchantProviderPaymentType::query()
            ->where('merchant_id', $merchantId)
            ->where('status', EntityStatus::ACTIVE->value)
            ->whereHas('providerPaymentType', function ($q) use ($direction, $amount, $paymentTypeCode) {
                $q->where('status', EntityStatus::ACTIVE->value)
                    ->where('type', $direction->value)
                    ->where(function ($q) use ($amount) {
                        $q->whereNull('single_min_amount')
                            ->orWhereRaw('single_min_amount <= ?', [$amount]);
                    })
                    ->where(function ($q) use ($amount) {
                        $q->whereNull('single_max_amount')
                            ->orWhereRaw('single_max_amount >= ?', [$amount]);
                    });

                if ($paymentTypeCode) {
                    $q->whereHas('paymentType', fn ($pt) => $pt->where('payment_type_code', $paymentTypeCode));
                }
            });

        $mppts = $query->with('providerPaymentType.provider')->get();

        // Filter out channels that exceeded daily limits
        $candidates = $mppts
            ->map(fn ($mppt) => $mppt->providerPaymentType)
            ->filter(function (ProviderPaymentType $ppt) use ($amount) {
                // Check provider is active
                if ($ppt->provider && $ppt->provider->status !== EntityStatus::ACTIVE->value) {
                    return false;
                }

                // Check daily amount limit (0 means no limit)
                if (MoneyHelper::isPositive((string) $ppt->daily_amount_limit)) {
                    $newDaily = MoneyHelper::add((string) ($ppt->current_daily_amount ?? '0'), $amount);
                    if (MoneyHelper::gt($newDaily, (string) $ppt->daily_amount_limit)) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        if ($candidates->isEmpty()) {
            throw new ChannelUnavailableException(
                "No available channel for merchant #{$merchantId}, direction={$direction->value}, amount={$amount}"
            );
        }

        return $this->weightedRandom($candidates);
    }

    private function weightedRandom(\Illuminate\Support\Collection $candidates): ProviderPaymentType
    {
        $totalWeight = $candidates->sum(fn (ProviderPaymentType $ppt) => max($ppt->weight, 1));
        $rand = mt_rand(1, (int) $totalWeight);

        $cumulative = 0;
        foreach ($candidates as $ppt) {
            $cumulative += max($ppt->weight, 1);
            if ($rand <= $cumulative) {
                return $ppt;
            }
        }

        return $candidates->last();
    }
}
