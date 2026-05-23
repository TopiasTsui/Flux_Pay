<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Security\TwoFactorService;
use Illuminate\Console\Command;

class TwoFactorEnrollCommand extends Command
{
    protected $signature = 'flux:2fa:enroll {user : 用户邮箱或 username}';

    protected $description = '为后台用户启用 2FA，输出 base32 密钥和 otpauth URL（用扫码工具自行生成 QR 码）';

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

        if ($service->isEnabled($user)) {
            if (! $this->confirm("User {$user->email} already has 2FA enabled. Rotate secret?", false)) {
                return self::SUCCESS;
            }
        }

        $secret = $service->enroll($user);

        $this->info("2FA enabled for {$user->email}");
        $this->newLine();
        $this->line('Secret (base32):  '.$secret);
        $this->line('otpauth URL:      '.$service->otpauthUrl($user, $secret));
        $this->newLine();
        $this->comment('Paste the otpauth URL into a QR generator (e.g. qrencode -t ANSI "<url>") or type the base32 secret into Google Authenticator / Authy / 1Password.');

        return self::SUCCESS;
    }
}
