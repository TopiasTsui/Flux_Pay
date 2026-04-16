<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\ProviderWalletRecord;

interface ProviderWalletRecordRepositoryInterface
{
    public function create(array $data): ProviderWalletRecord;
}
