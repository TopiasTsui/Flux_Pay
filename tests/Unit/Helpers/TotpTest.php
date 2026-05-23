<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\Totp;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TotpTest extends TestCase
{
    #[Test]
    public function generated_secret_is_base32_and_long_enough(): void
    {
        $secret = Totp::generateSecret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
        $this->assertGreaterThanOrEqual(32, strlen($secret));
    }

    #[Test]
    public function freshly_generated_code_verifies(): void
    {
        $secret = Totp::generateSecret();
        $counter = (int) floor(time() / 30);
        $reflection = new \ReflectionMethod(Totp::class, 'code');
        $reflection->setAccessible(true);
        $code = $reflection->invoke(null, $secret, $counter);

        $this->assertTrue(Totp::verify($secret, $code));
    }

    #[Test]
    public function obviously_wrong_codes_are_rejected(): void
    {
        $secret = Totp::generateSecret();

        $this->assertFalse(Totp::verify($secret, '000000'));
        $this->assertFalse(Totp::verify($secret, 'abcdef'));
        $this->assertFalse(Totp::verify($secret, '12345'));        // wrong length
        $this->assertFalse(Totp::verify($secret, '1234567'));      // wrong length
        $this->assertFalse(Totp::verify($secret, ''));
    }

    #[Test]
    public function otpauth_url_contains_required_params(): void
    {
        $url = Totp::otpauthUrl('JBSWY3DPEHPK3PXP', 'admin@example.com', 'FluxPay');

        $this->assertStringStartsWith('otpauth://totp/', $url);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $url);
        $this->assertStringContainsString('issuer=FluxPay', $url);
        $this->assertStringContainsString('algorithm=SHA1', $url);
        $this->assertStringContainsString('digits=6', $url);
        $this->assertStringContainsString('period=30', $url);
    }
}
