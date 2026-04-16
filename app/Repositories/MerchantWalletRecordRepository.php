<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\MerchantWalletRecordRepositoryInterface;
use App\Models\MerchantWalletRecord;

class MerchantWalletRecordRepository implements MerchantWalletRecordRepositoryInterface
{
    public function create(array $data): MerchantWalletRecord
    {
        return MerchantWalletRecord::create($data);
    }
}
