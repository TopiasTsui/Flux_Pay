<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Helpers\Totp;
use App\Models\User;
use App\Services\Security\TwoFactorService;
use Database\Seeders\PaymentTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TwoFactorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(PaymentTypeSeeder::class);
        $this->seed(TestDataSeeder::class);
        $this->admin = User::where('email', 'admin@fluxpay.com')->firstOrFail();
        $this->service = app(TwoFactorService::class);
    }

    private function currentCode(string $secret): string
    {
        $reflection = new \ReflectionMethod(Totp::class, 'code');
        $reflection->setAccessible(true);

        return $reflection->invoke(null, $secret, (int) floor(time() / 30));
    }

    #[Test]
    public function user_without_2fa_reaches_dashboard_directly(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/main');
        $response->assertStatus(200);
    }

    #[Test]
    public function user_with_2fa_is_redirected_to_challenge(): void
    {
        $this->service->enroll($this->admin);

        $response = $this->actingAs($this->admin->fresh())->get('/admin/main');

        $response->assertRedirect(route('platform.2fa.challenge'));
    }

    #[Test]
    public function challenge_page_renders(): void
    {
        $this->service->enroll($this->admin);

        $response = $this->actingAs($this->admin->fresh())->get('/admin/2fa/challenge');

        $response->assertStatus(200);
        $response->assertSee('Two-Factor Verification');
    }

    #[Test]
    public function valid_code_passes_challenge_and_unlocks_dashboard(): void
    {
        $secret = $this->service->enroll($this->admin);
        $code = $this->currentCode($secret);

        $verify = $this->actingAs($this->admin->fresh())
            ->from('/admin/2fa/challenge')
            ->post('/admin/2fa/verify', ['code' => $code]);

        $verify->assertRedirect();
        $this->assertTrue(session('two_factor_passed'));

        // Now the dashboard should be reachable in the same session.
        $main = $this->get('/admin/main');
        $main->assertStatus(200);
    }

    #[Test]
    public function invalid_code_bounces_back_to_challenge_with_error(): void
    {
        $this->service->enroll($this->admin);

        $verify = $this->actingAs($this->admin->fresh())
            ->from('/admin/2fa/challenge')
            ->post('/admin/2fa/verify', ['code' => '000000']);

        $verify->assertRedirect('/admin/2fa/challenge');
        $this->assertFalse(session('two_factor_passed', false));
    }

    #[Test]
    public function challenge_page_is_exempt_from_redirect_loop(): void
    {
        $this->service->enroll($this->admin);

        // Hitting the challenge route while not yet passed must NOT loop back to itself.
        $response = $this->actingAs($this->admin->fresh())->get('/admin/2fa/challenge');
        $response->assertStatus(200);
    }
}
