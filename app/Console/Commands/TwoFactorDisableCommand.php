<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Security\TwoFactorService;
use Illuminate\Console\Command;

class TwoFactorDisableCommand extends Command
{
    protected $signature = 'flux:2fa:disable {user : 用户邮箱或 username}';

    protected $description = '禁用某用户的 2FA（运维急救通道，例如用户丢失了 authenticator app）';

    public function handle(TwoFactorService $service): int
    {
        $identifier = (string) $this->argument('user');
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (! $user) {
            $this->error("User not found: {$identifier}");

            return self::FAILURE;
        }

        if (! $service->isEnabled($user)) {
            $this->warn("User {$user->email} does not have 2FA enabled.");

            return self::SUCCESS;
        }

        if (! $this->confirm("Disable 2FA for {$user->email}?", false)) {
            return self::SUCCESS;
        }

        $service->disable($user);
        $this->info("2FA disabled for {$user->email}");

        return self::SUCCESS;
    }
}
