<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\AgentWalletRecord;

interface AgentWalletRecordRepositoryInterface
{
    public function create(array $data): AgentWalletRecord;
}
