<?php

declare(strict_types=1);

namespace App\Services\Security;

use Symfony\Component\HttpFoundation\IpUtils;

class IpWhitelistService
{
    public function isAllowed(string $ip, array|string $whitelist): bool
    {
        if (is_string($whitelist)) {
            $whitelist = array_filter(array_map('trim', explode(',', $whitelist)));
        }

        if (empty($whitelist)) {
            return true;
        }

        if (class_exists(IpUtils::class)) {
            return IpUtils::checkIp($ip, $whitelist);
        }

        foreach ($whitelist as $entry) {
            $entry = trim($entry);

            if (str_contains($entry, '/')) {
                if ($this->matchesCidr($ip, $entry)) {
                    return true;
                }
            } elseif ($ip === $entry) {
                return true;
            }
        }

        return false;
    }

    private function matchesCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
