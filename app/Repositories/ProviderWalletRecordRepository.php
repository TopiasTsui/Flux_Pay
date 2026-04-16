<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ProviderWalletRecordRepositoryInterface;
use App\Models\ProviderWalletRecord;

class ProviderWalletRecordRepository implements ProviderWalletRecordRepositoryInterface
{
    public function create(array $data): ProviderWalletRecord
    {
        return ProviderWalletRecord::create($data);
    }
}
