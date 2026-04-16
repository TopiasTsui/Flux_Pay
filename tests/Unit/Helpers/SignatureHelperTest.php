<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\SignatureHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SignatureHelperTest extends TestCase
{
    private string $md5key = 'test_secret_key_123';

    #[Test]
    public function generate_produces_consistent_md5_signature(): void
    {
        $params = [
            'merchantNo' => 'TEST001',
            'orderNo' => 'ORD123456',
            'amount' => '1000.00',
        ];

        $sig1 = SignatureHelper::generate($params, $this->md5key);
        $sig2 = SignatureHelper::generate($params, $this->md5key);

        $this->assertSame($sig1, $sig2);
        $this->assertSame(32, strlen($sig1));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $sig1);
    }

    #[Test]
    public function generate_sorts_params_alphabetically(): void
    {
        $params1 = ['z_param' => 'last', 'a_param' => 'first', 'merchantNo' => 'TEST001'];
        $params2 = ['a_param' => 'first', 'merchantNo' => 'TEST001', 'z_param' => 'last'];

        $this->assertSame(
            SignatureHelper::generate($params1, $this->md5key),
            SignatureHelper::generate($params2, $this->md5key),
        );
    }

    #[Test]
    public function generate_excludes_signature_sign_callbackUrl_and_extend(): void
    {
        $base = ['merchantNo' => 'TEST001', 'amount' => '100'];
        $withExcluded = array_merge($base, [
            'signature' => 'should_be_ignored',
            'sign' => 'also_ignored',
            'callbackUrl' => 'https://example.com/callback',
            'extend' => 'extra_data',
        ]);

        $this->assertSame(
            SignatureHelper::generate($base, $this->md5key),
            SignatureHelper::generate($withExcluded, $this->md5key),
        );
    }

    #[Test]
    public function generate_filters_out_empty_and_null_values(): void
    {
        $base = ['merchantNo' => 'TEST001', 'amount' => '100'];
        $withEmpty = array_merge($base, ['emptyField' => '', 'nullField' => null]);

        $this->assertSame(
            SignatureHelper::generate($base, $this->md5key),
            SignatureHelper::generate($withEmpty, $this->md5key),
        );
    }

    #[Test]
    public function verify_returns_true_for_valid_signature(): void
    {
        $params = [
            'merchantNo' => 'TEST001',
            'orderNo' => 'ORD123456',
            'amount' => '1000.00',
        ];

        $signature = SignatureHelper::generate($params, $this->md5key);

        $this->assertTrue(SignatureHelper::verify($params, $this->md5key, $signature));
    }

    #[Test]
    public function verify_returns_false_for_tampered_params(): void
    {
        $params = [
            'merchantNo' => 'TEST001',
            'orderNo' => 'ORD123456',
            'amount' => '1000.00',
        ];

        $signature = SignatureHelper::generate($params, $this->md5key);

        // Tamper with amount
        $params['amount'] = '2000.00';

        $this->assertFalse(SignatureHelper::verify($params, $this->md5key, $signature));
    }

    #[Test]
    public function verify_returns_false_for_wrong_key(): void
    {
        $params = ['merchantNo' => 'TEST001', 'amount' => '100'];

        $signature = SignatureHelper::generate($params, $this->md5key);

        $this->assertFalse(SignatureHelper::verify($params, 'wrong_key', $signature));
    }

    #[Test]
    public function verify_returns_false_for_garbage_signature(): void
    {
        $params = ['merchantNo' => 'TEST001', 'amount' => '100'];

        $this->assertFalse(SignatureHelper::verify($params, $this->md5key, 'not_a_valid_signature'));
    }
}
