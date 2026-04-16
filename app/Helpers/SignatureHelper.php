<?php

namespace App\Helpers;

class SignatureHelper
{
    /**
     * Generate MD5 signature for merchant API.
     * Sort params by key A-Z, join as key=value&, append md5key, MD5 hash.
     */
    public static function generate(array $params, string $md5key): string
    {
        $filtered = collect($params)
            ->except(['signature', 'sign', 'callbackUrl', 'extend'])
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->sortKeys();

        $str = $filtered->map(fn ($v, $k) => "{$k}={$v}")->implode('&');
        $str .= "&{$md5key}";

        return md5($str);
    }

    /**
     * Verify signature against expected.
     */
    public static function verify(array $params, string $md5key, string $signature): bool
    {
        return hash_equals(self::generate($params, $md5key), $signature);
    }
}
