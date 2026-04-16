<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\DTOs\AgentCommissionResult;
use App\Enums\PaymentDirection;
use App\Helpers\MoneyHelper;
use App\Models\MerchantPaymentType;

class CommissionCalculator
{
    public function calculate(
        MerchantPaymentType $mpt,
        PaymentDirection $direction,
        string $amount,
    ): AgentCommissionResult {
        $agentsFee = $direction === PaymentDirection::DEPOSIT
            ? ($mpt->deposit_agents_fee ?? [])
            : ($mpt->withdraw_agents_fee ?? []);

        $feeType = $direction === PaymentDirection::DEPOSIT
            ? $mpt->deposit_fee_type
            : $mpt->withdraw_fee_type;

        $agentFeeMap = [];
        $total = '0';

        foreach ($agentsFee as $agentId => $rate) {
            $commission = $feeType->calculate($amount, (string) $rate);

            if (MoneyHelper::isPositive($commission)) {
                $agentFeeMap[(int) $agentId] = $commission;
                $total = MoneyHelper::add($total, $commission);
            }
        }

        return new AgentCommissionResult(
            total: $total,
            agentFeeMap: $agentFeeMap,
        );
    }
}
