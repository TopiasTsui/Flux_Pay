<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Balance',
    description: '商户余额',
    properties: [
        new OA\Property(property: 'merchantNo', type: 'string', example: 'M0001'),
        new OA\Property(property: 'currency', type: 'string', example: 'CNY'),
        new OA\Property(property: 'totalBalance', type: 'string', description: '总余额', example: '10000.000000'),
        new OA\Property(property: 'availableBalance', type: 'string', description: '可用余额', example: '9500.000000'),
        new OA\Property(property: 'holdBalance', type: 'string', description: '冻结余额', example: '500.000000'),
    ],
)]
class Balance
{
}
