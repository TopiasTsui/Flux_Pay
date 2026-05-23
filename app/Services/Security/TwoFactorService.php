<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Helpers\Totp;
use App\Models\User;

class TwoFactorService
{
    public function isEnabled(User $user): bool
    {
        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at !== null;
    }

    public function enroll(User $user, ?string $secret = null): string
    {
        $secret ??= Totp::generateSecret();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = now();
        $user->save();

        return $secret;
    }

    public function disable(User $user): void
    {
        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->save();
    }

    public function verify(User $user, string $code): bool
    {
        if (! $this->isEnabled($user)) {
            return false;
        }

        return Totp::verify($user->two_factor_secret, $code);
    }

    public function otpauthUrl(User $user, string $secret): string
    {
        $issuer = config('app.name', 'FluxPay');
        $account = $user->email ?: ($user->username ?: 'user');

        return Totp::otpauthUrl($secret, $account, $issuer);
    }
}
