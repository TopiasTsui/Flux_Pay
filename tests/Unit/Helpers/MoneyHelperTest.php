<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\MoneyHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MoneyHelperTest extends TestCase
{
    #[Test]
    public function add_returns_correct_sum(): void
    {
        $this->assertSame('300.000000', MoneyHelper::add('100.000000', '200.000000'));
    }

    #[Test]
    public function add_with_zero(): void
    {
        $this->assertSame('100.000000', MoneyHelper::add('100.000000', '0'));
        $this->assertSame('100.000000', MoneyHelper::add('0', '100.000000'));
    }

    #[Test]
    public function add_with_negative_values(): void
    {
        $this->assertSame('50.000000', MoneyHelper::add('100.000000', '-50.000000'));
    }

    #[Test]
    public function add_with_high_precision(): void
    {
        $this->assertSame('0.300000', MoneyHelper::add('0.100000', '0.200000'));
    }

    #[Test]
    public function sub_returns_correct_difference(): void
    {
        $this->assertSame('100.000000', MoneyHelper::sub('300.000000', '200.000000'));
    }

    #[Test]
    public function sub_with_zero(): void
    {
        $this->assertSame('100.000000', MoneyHelper::sub('100.000000', '0'));
    }

    #[Test]
    public function sub_resulting_in_negative(): void
    {
        $this->assertSame('-100.000000', MoneyHelper::sub('100.000000', '200.000000'));
    }

    #[Test]
    public function mul_returns_correct_product(): void
    {
        $this->assertSame('200.000000', MoneyHelper::mul('100.000000', '2'));
    }

    #[Test]
    public function mul_with_zero(): void
    {
        $this->assertSame('0.000000', MoneyHelper::mul('100.000000', '0'));
    }

    #[Test]
    public function mul_with_percentage(): void
    {
        // 1000 * 0.025 = 25
        $this->assertSame('25.000000', MoneyHelper::mul('1000', '0.025'));
    }

    #[Test]
    public function div_returns_correct_quotient(): void
    {
        $this->assertSame('50.000000', MoneyHelper::div('100.000000', '2'));
    }

    #[Test]
    public function div_with_remainder(): void
    {
        $this->assertSame('33.333333', MoneyHelper::div('100', '3'));
    }

    #[Test]
    public function gte_returns_true_when_greater(): void
    {
        $this->assertTrue(MoneyHelper::gte('200', '100'));
    }

    #[Test]
    public function gte_returns_true_when_equal(): void
    {
        $this->assertTrue(MoneyHelper::gte('100.000000', '100'));
    }

    #[Test]
    public function gte_returns_false_when_less(): void
    {
        $this->assertFalse(MoneyHelper::gte('50', '100'));
    }

    #[Test]
    public function gt_returns_true_when_greater(): void
    {
        $this->assertTrue(MoneyHelper::gt('200', '100'));
    }

    #[Test]
    public function gt_returns_false_when_equal(): void
    {
        $this->assertFalse(MoneyHelper::gt('100', '100'));
    }

    #[Test]
    public function gt_returns_false_when_less(): void
    {
        $this->assertFalse(MoneyHelper::gt('50', '100'));
    }

    #[Test]
    public function isPositive_returns_true_for_positive_amount(): void
    {
        $this->assertTrue(MoneyHelper::isPositive('100.000000'));
        $this->assertTrue(MoneyHelper::isPositive('0.000001'));
    }

    #[Test]
    public function isPositive_returns_false_for_zero(): void
    {
        $this->assertFalse(MoneyHelper::isPositive('0'));
        $this->assertFalse(MoneyHelper::isPositive('0.000000'));
    }

    #[Test]
    public function isPositive_returns_false_for_negative(): void
    {
        $this->assertFalse(MoneyHelper::isPositive('-1'));
        $this->assertFalse(MoneyHelper::isPositive('-0.000001'));
    }

    #[Test]
    public function isZero_returns_true_for_zero(): void
    {
        $this->assertTrue(MoneyHelper::isZero('0'));
        $this->assertTrue(MoneyHelper::isZero('0.000000'));
        $this->assertTrue(MoneyHelper::isZero('0.0'));
    }

    #[Test]
    public function isZero_returns_false_for_non_zero(): void
    {
        $this->assertFalse(MoneyHelper::isZero('1'));
        $this->assertFalse(MoneyHelper::isZero('-1'));
        $this->assertFalse(MoneyHelper::isZero('0.000001'));
    }
}
