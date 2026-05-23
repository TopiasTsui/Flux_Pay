<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\AdminUserIpWhitelist;
use App\Models\User;
use Database\Seeders\PaymentTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminIpWhitelistTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(PaymentTypeSeeder::class);
        $this->seed(TestDataSeeder::class);
        $this->admin = User::where('email', 'admin@fluxpay.com')->firstOrFail();
    }

    #[Test]
    public function user_without_whitelist_rows_passes_through(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/main');
        $response->assertStatus(200);
    }

    #[Test]
    public function user_with_whitelist_and_matching_ip_passes_through(): void
    {
        AdminUserIpWhitelist::create([
            'admin_user_id' => $this->admin->id,
            'ip_address' => '127.0.0.1',
            'status' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/admin/main');

        $response->assertStatus(200);
    }

    #[Test]
    public function user_with_whitelist_and_mismatching_ip_is_blocked(): void
    {
        AdminUserIpWhitelist::create([
            'admin_user_id' => $this->admin->id,
            'ip_address' => '10.0.0.1',
            'status' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/admin/main');

        $response->assertStatus(403);
    }

    #[Test]
    public function inactive_whitelist_rows_are_ignored(): void
    {
        AdminUserIpWhitelist::create([
            'admin_user_id' => $this->admin->id,
            'ip_address' => '10.0.0.1',
            'status' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/admin/main');

        $response->assertStatus(200);
    }
}
