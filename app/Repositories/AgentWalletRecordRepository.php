<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\AgentWalletRecordRepositoryInterface;
use App\Models\AgentWalletRecord;

class AgentWalletRecordRepository implements AgentWalletRecordRepositoryInterface
{
    public function create(array $data): AgentWalletRecord
    {
        return AgentWalletRecord::create($data);
    }
}
