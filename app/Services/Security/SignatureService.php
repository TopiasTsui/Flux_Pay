<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Helpers\SignatureHelper;

class SignatureService
{
    public function generate(array $params, string $md5key): string
    {
        return SignatureHelper::generate($params, $md5key);
    }

    public function verify(array $params, string $md5key, string $signature): bool
    {
        return SignatureHelper::verify($params, $md5key, $signature);
    }
}
