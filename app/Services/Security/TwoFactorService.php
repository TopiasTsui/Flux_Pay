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

    public function isPending(User $user): bool
    {
        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;
    }

    /**
     * CLI/admin path: enroll a user and mark confirmed in one shot.
     * For self-service web enrollment, use beginEnrollment + confirmEnrollment.
     */
    public function enroll(User $user, ?string $secret = null): string
    {
        $secret ??= Totp::generateSecret();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = now();
        $user->save();

        return $secret;
    }

    /**
     * Self-service step 1: generate and store an unconfirmed secret.
     * The user is not yet "enabled" — they still need to confirm with a code.
     */
    public function beginEnrollment(User $user): string
    {
        $secret = Totp::generateSecret();
        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return $secret;
    }

    /**
     * Self-service step 2: verify the user-provided code against the pending
     * secret. On success, mark confirmed (= enabled). On failure, leave the
     * pending secret untouched so the user can retry.
     */
    public function confirmEnrollment(User $user, string $code): bool
    {
        if (! $this->isPending($user)) {
            return false;
        }

        if (! Totp::verify($user->two_factor_secret, $code)) {
            return false;
        }

        $user->two_factor_confirmed_at = now();
        $user->save();

        return true;
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
