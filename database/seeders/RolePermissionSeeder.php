<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Orchid\Platform\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = [
            'platform.index' => true,
            'platform.systems.roles' => true,
            'platform.systems.users' => true,
            'platform.systems.attachment' => true,
            'platform.orders.deposits' => true,
            'platform.orders.withdrawals' => true,
            'platform.wallets.merchant' => true,
            'platform.wallets.agent' => true,
            'platform.wallets.provider' => true,
            'platform.merchants' => true,
            'platform.agents' => true,
            'platform.providers' => true,
            'platform.payment-types' => true,
            'platform.banks' => true,
            'platform.settings' => true,
        ];

        Role::updateOrCreate(
            ['slug' => 'administrator'],
            [
                'name' => 'Administrator',
                'permissions' => $allPermissions,
            ],
        );

        $managerPermissions = $allPermissions;
        unset($managerPermissions['platform.systems.roles']);

        Role::updateOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Manager',
                'permissions' => $managerPermissions,
            ],
        );

        Role::updateOrCreate(
            ['slug' => 'merchant'],
            [
                'name' => 'Merchant',
                'permissions' => [
                    'platform.index' => true,
                    'platform.orders.deposits' => true,
                    'platform.orders.withdrawals' => true,
                    'platform.wallets.merchant' => true,
                ],
            ],
        );

        Role::updateOrCreate(
            ['slug' => 'agent'],
            [
                'name' => 'Agent',
                'permissions' => [
                    'platform.index' => true,
                    'platform.orders.deposits' => true,
                    'platform.orders.withdrawals' => true,
                    'platform.wallets.agent' => true,
                    'platform.merchants' => true,
                ],
            ],
        );
    }
}
