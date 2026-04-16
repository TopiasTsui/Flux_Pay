<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\MerchantWalletRecord;

interface MerchantWalletRecordRepositoryInterface
{
    public function create(array $data): MerchantWalletRecord;
}
