<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Helpers\Totp;
use App\Models\AdminUserIpWhitelist;
use App\Models\User;
use App\Services\Security\TwoFactorService;
use Database\Seeders\PaymentTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserSecurityScreenTest extends TestCase
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

    private function screenPost(string $method, array $body = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)
            ->from('/admin/profile/security')
            ->post('/admin/profile/security/'.$method, $body);
    }

    #[Test]
    public function security_screen_renders_in_disabled_state(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/profile/security');
        $response->assertStatus(200);
        $response->assertSee('Two-Factor Authentication');
        $response->assertSee('IP Whitelist');
    }

    #[Test]
    public function security_screen_renders_in_enabled_state(): void
    {
        $this->service->enroll($this->admin);

        // Mark session as 2FA-passed so the middleware doesn't redirect to challenge.
        $this->withSession(['two_factor_passed' => true]);

        $response = $this->actingAs($this->admin->fresh())->get('/admin/profile/security');
        $response->assertStatus(200);
        $response->assertSee('Disable 2FA');
    }

    #[Test]
    public function begin_then_confirm_flow_enables_2fa_and_sets_session_marker(): void
    {
        $this->screenPost('beginTwoFactor')->assertRedirect();

        $this->assertTrue($this->service->isPending($this->admin->fresh()));

        $secret = $this->admin->fresh()->two_factor_secret;
        $code = $this->currentCode($secret);

        $this->screenPost('confirmTwoFactor', ['code' => $code])->assertRedirect();

        $this->assertTrue($this->service->isEnabled($this->admin->fresh()));
        $this->assertTrue(session('two_factor_passed'));
    }

    #[Test]
    public function confirm_with_invalid_code_keeps_pending_state(): void
    {
        $this->service->beginEnrollment($this->admin);

        $this->screenPost('confirmTwoFactor', ['code' => '000000'])->assertRedirect();

        $this->assertTrue($this->service->isPending($this->admin->fresh()));
        $this->assertFalse($this->service->isEnabled($this->admin->fresh()));
    }

    #[Test]
    public function cancel_clears_pending_secret(): void
    {
        $this->service->beginEnrollment($this->admin);

        $this->screenPost('cancelTwoFactor')->assertRedirect();

        $this->assertFalse($this->service->isPending($this->admin->fresh()));
        $this->assertNull($this->admin->fresh()->two_factor_secret);
    }

    #[Test]
    public function disable_clears_enabled_2fa(): void
    {
        $this->service->enroll($this->admin);
        $this->withSession(['two_factor_passed' => true]);

        $this->screenPost('disableTwoFactor')->assertRedirect();

        $this->assertFalse($this->service->isEnabled($this->admin->fresh()));
    }

    #[Test]
    public function add_ip_appends_a_row_to_user_whitelist(): void
    {
        $this->screenPost('addIp', [
            'new_ip' => '203.0.113.7',
            'new_remark' => 'Office',
        ])->assertRedirect();

        $this->assertDatabaseHas('admin_user_ip_whitelists', [
            'admin_user_id' => $this->admin->id,
            'ip_address' => '203.0.113.7',
            'remark' => 'Office',
            'status' => 1,
        ]);
    }

    #[Test]
    public function add_ip_rejects_invalid_address(): void
    {
        $response = $this->screenPost('addIp', ['new_ip' => 'not-an-ip']);

        $response->assertSessionHasErrors('new_ip');
        $this->assertDatabaseMissing('admin_user_ip_whitelists', [
            'admin_user_id' => $this->admin->id,
        ]);
    }

    #[Test]
    public function remove_ip_only_deletes_own_rows(): void
    {
        // Whitelist matches the test client's address so the middleware lets the request through.
        $own = AdminUserIpWhitelist::create([
            'admin_user_id' => $this->admin->id,
            'ip_address' => '127.0.0.1',
            'status' => 1,
        ]);

        $other = User::create([
            'name' => 'Other',
            'username' => 'other',
            'email' => 'other@example.com',
            'password' => bcrypt('secret'),
        ]);
        $otherRow = AdminUserIpWhitelist::create([
            'admin_user_id' => $other->id,
            'ip_address' => '10.0.0.2',
            'status' => 1,
        ]);

        $this->screenPost('removeIp', ['id' => $own->id])->assertRedirect();
        $this->assertDatabaseMissing('admin_user_ip_whitelists', ['id' => $own->id]);

        // Re-add own row (it was just deleted) so we're still allowed in for the second call.
        AdminUserIpWhitelist::create([
            'admin_user_id' => $this->admin->id,
            'ip_address' => '127.0.0.1',
            'status' => 1,
        ]);

        $this->screenPost('removeIp', ['id' => $otherRow->id])->assertRedirect();
        $this->assertDatabaseHas('admin_user_ip_whitelists', ['id' => $otherRow->id]);
    }
}
