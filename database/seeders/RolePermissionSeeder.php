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
            // Access Controls
            'platform.systems.roles' => true,
            'platform.systems.users' => true,
            'platform.systems.attachment' => true,
            // Payment
            'platform.orders' => true,
            'platform.orders.actions' => true,
            // Entities
            'platform.merchants' => true,
            'platform.agents' => true,
            'platform.providers' => true,
            // Payment Config
            'platform.payment-config' => true,
            // Finance
            'platform.wallets' => true,
            // Reports
            'platform.reports' => true,
            // Banks
            'platform.banks' => true,
            // System
            'platform.system' => true,
            'platform.system.i18n' => true,
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
                    'platform.orders' => true,
                    'platform.wallets' => true,
                ],
            ],
        );

        Role::updateOrCreate(
            ['slug' => 'agent'],
            [
                'name' => 'Agent',
                'permissions' => [
                    'platform.index' => true,
                    'platform.orders' => true,
                    'platform.wallets' => true,
                    'platform.merchants' => true,
                ],
            ],
        );
    }
}
