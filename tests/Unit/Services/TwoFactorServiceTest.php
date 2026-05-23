<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Helpers\Totp;
use App\Models\User;
use App\Services\Security\TwoFactorService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private TwoFactorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(TestDataSeeder::class);
        $this->user = User::where('email', 'admin@fluxpay.com')->firstOrFail();
        $this->service = new TwoFactorService;
    }

    #[Test]
    public function is_enabled_returns_false_when_no_secret(): void
    {
        $this->assertFalse($this->service->isEnabled($this->user));
    }

    #[Test]
    public function enroll_sets_secret_and_marks_confirmed(): void
    {
        $secret = $this->service->enroll($this->user);

        $this->assertNotEmpty($secret);
        $this->assertTrue($this->service->isEnabled($this->user->fresh()));
        $this->assertNotNull($this->user->fresh()->two_factor_confirmed_at);
    }

    #[Test]
    public function disable_clears_secret_and_confirmation(): void
    {
        $this->service->enroll($this->user);
        $this->service->disable($this->user);

        $this->assertFalse($this->service->isEnabled($this->user->fresh()));
        $this->assertNull($this->user->fresh()->two_factor_secret);
        $this->assertNull($this->user->fresh()->two_factor_confirmed_at);
    }

    #[Test]
    public function verify_accepts_current_code(): void
    {
        $secret = $this->service->enroll($this->user);

        $reflection = new \ReflectionMethod(Totp::class, 'code');
        $reflection->setAccessible(true);
        $code = $reflection->invoke(null, $secret, (int) floor(time() / 30));

        $this->assertTrue($this->service->verify($this->user->fresh(), $code));
    }

    #[Test]
    public function verify_rejects_wrong_code(): void
    {
        $this->service->enroll($this->user);

        $this->assertFalse($this->service->verify($this->user->fresh(), '000000'));
    }

    #[Test]
    public function verify_rejects_when_not_enrolled(): void
    {
        $this->assertFalse($this->service->verify($this->user, '123456'));
    }

    #[Test]
    public function secret_is_encrypted_at_rest(): void
    {
        $secret = $this->service->enroll($this->user);

        $raw = \DB::table('users')->where('id', $this->user->id)->value('two_factor_secret');
        $this->assertNotSame($secret, $raw);
        $this->assertSame($secret, $this->user->fresh()->two_factor_secret);
    }

    #[Test]
    public function begin_enrollment_creates_pending_state_not_enabled(): void
    {
        $secret = $this->service->beginEnrollment($this->user);

        $this->assertNotEmpty($secret);
        $this->assertTrue($this->service->isPending($this->user->fresh()));
        $this->assertFalse($this->service->isEnabled($this->user->fresh()));
        $this->assertNull($this->user->fresh()->two_factor_confirmed_at);
    }

    #[Test]
    public function confirm_enrollment_with_valid_code_marks_enabled(): void
    {
        $secret = $this->service->beginEnrollment($this->user);

        $reflection = new \ReflectionMethod(Totp::class, 'code');
        $reflection->setAccessible(true);
        $code = $reflection->invoke(null, $secret, (int) floor(time() / 30));

        $this->assertTrue($this->service->confirmEnrollment($this->user->fresh(), $code));
        $this->assertTrue($this->service->isEnabled($this->user->fresh()));
        $this->assertFalse($this->service->isPending($this->user->fresh()));
    }

    #[Test]
    public function confirm_enrollment_with_bad_code_leaves_pending(): void
    {
        $this->service->beginEnrollment($this->user);

        $this->assertFalse($this->service->confirmEnrollment($this->user->fresh(), '000000'));
        $this->assertTrue($this->service->isPending($this->user->fresh()));
        $this->assertFalse($this->service->isEnabled($this->user->fresh()));
    }

    #[Test]
    public function confirm_enrollment_refuses_when_not_pending(): void
    {
        // No pending secret at all.
        $this->assertFalse($this->service->confirmEnrollment($this->user, '123456'));

        // Already confirmed.
        $secret = $this->service->enroll($this->user);
        $this->assertFalse($this->service->confirmEnrollment($this->user->fresh(), '000000'));
    }
}
