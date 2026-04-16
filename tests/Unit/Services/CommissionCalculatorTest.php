<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\EntityStatus;
use App\Enums\FeeType;
use App\Enums\PaymentDirection;
use App\Models\MerchantPaymentType;
use App\Services\Agent\CommissionCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CommissionCalculatorTest extends TestCase
{
    private CommissionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CommissionCalculator();
    }

    private function makeMerchantPaymentType(array $attributes = []): MerchantPaymentType
    {
        $mpt = new MerchantPaymentType();
        $mpt->forceFill(array_merge([
            'merchant_id' => 1,
            'payment_type_id' => 1,
            'status' => EntityStatus::ACTIVE->value,
            'deposit_fee_type' => FeeType::PERCENTAGE->value,
            'deposit_fee' => '2.000000',
            'deposit_agents_fee' => [],
            'withdraw_fee_type' => FeeType::PERCENTAGE->value,
            'withdraw_fee' => '2.000000',
            'withdraw_agents_fee' => [],
        ], $attributes));

        return $mpt;
    }

    #[Test]
    public function calculate_with_percentage_fees_for_deposit(): void
    {
        $mpt = $this->makeMerchantPaymentType([
            'deposit_fee_type' => FeeType::PERCENTAGE->value,
            'deposit_agents_fee' => [
                1 => '0.5',
                2 => '0.3',
            ],
        ]);

        $result = $this->calculator->calculate($mpt, PaymentDirection::DEPOSIT, '10000');

        // Agent 1: 10000 * 0.5 / 100 = 50
        // Agent 2: 10000 * 0.3 / 100 = 30
        $this->assertSame('80.000000', $result->total);
        $this->assertArrayHasKey(1, $result->agentFeeMap);
        $this->assertArrayHasKey(2, $result->agentFeeMap);
        $this->assertSame('50.000000', $result->agentFeeMap[1]);
        $this->assertSame('30.000000', $result->agentFeeMap[2]);
    }

    #[Test]
    public function calculate_with_fixed_fees(): void
    {
        $mpt = $this->makeMerchantPaymentType([
            'deposit_fee_type' => FeeType::FIXED->value,
            'deposit_agents_fee' => [
                1 => '100',
                2 => '50',
            ],
        ]);

        $result = $this->calculator->calculate($mpt, PaymentDirection::DEPOSIT, '10000');

        // Fixed fee: agent gets the rate value directly
        $this->assertSame('150.000000', $result->total);
        $this->assertSame('100', $result->agentFeeMap[1]);
        $this->assertSame('50', $result->agentFeeMap[2]);
    }

    #[Test]
    public function calculate_with_empty_agents_fee(): void
    {
        $mpt = $this->makeMerchantPaymentType([
            'deposit_agents_fee' => [],
        ]);

        $result = $this->calculator->calculate($mpt, PaymentDirection::DEPOSIT, '10000');

        $this->assertSame('0', $result->total);
        $this->assertEmpty($result->agentFeeMap);
    }

    #[Test]
    public function calculate_with_null_agents_fee(): void
    {
        $mpt = $this->makeMerchantPaymentType([
            'deposit_agents_fee' => null,
        ]);

        $result = $this->calculator->calculate($mpt, PaymentDirection::DEPOSIT, '10000');

        $this->assertSame('0', $result->total);
        $this->assertEmpty($result->agentFeeMap);
    }

    #[Test]
    public function calculate_uses_withdraw_fees_for_withdraw_direction(): void
    {
        $mpt = $this->makeMerchantPaymentType([
            'deposit_fee_type' => FeeType::PERCENTAGE->value,
            'deposit_agents_fee' => [1 => '0.5'],
            'withdraw_fee_type' => FeeType::PERCENTAGE->value,
            'withdraw_agents_fee' => [1 => '1.0'],
        ]);

        $result = $this->calculator->calculate($mpt, PaymentDirection::WITHDRAW, '10000');

        // Should use withdraw_agents_fee: 10000 * 1.0 / 100 = 100
        $this->assertSame('100.000000', $result->total);
        $this->assertSame('100.000000', $result->agentFeeMap[1]);
    }

    #[Test]
    public function calculate_with_multiple_agents(): void
    {
        $mpt = $this->makeMerchantPaymentType([
            'deposit_fee_type' => FeeType::PERCENTAGE->value,
            'deposit_agents_fee' => [
                10 => '0.5',
                20 => '0.3',
                30 => '0.2',
            ],
        ]);

        $result = $this->calculator->calculate($mpt, PaymentDirection::DEPOSIT, '10000');

        // 10000 * 0.5/100 = 50, 10000 * 0.3/100 = 30, 10000 * 0.2/100 = 20
        $this->assertSame('100.000000', $result->total);
        $this->assertCount(3, $result->agentFeeMap);
        $this->assertSame('50.000000', $result->agentFeeMap[10]);
        $this->assertSame('30.000000', $result->agentFeeMap[20]);
        $this->assertSame('20.000000', $result->agentFeeMap[30]);
    }

    #[Test]
    public function calculate_with_zero_rate_agent_excluded(): void
    {
        $mpt = $this->makeMerchantPaymentType([
            'deposit_fee_type' => FeeType::PERCENTAGE->value,
            'deposit_agents_fee' => [
                1 => '0.5',
                2 => '0',
            ],
        ]);

        $result = $this->calculator->calculate($mpt, PaymentDirection::DEPOSIT, '10000');

        // Agent 2 has 0 rate, so commission is 0, not positive, should be excluded
        $this->assertSame('50.000000', $result->total);
        $this->assertArrayHasKey(1, $result->agentFeeMap);
        $this->assertArrayNotHasKey(2, $result->agentFeeMap);
    }
}
