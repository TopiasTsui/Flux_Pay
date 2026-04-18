<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WithdrawOrder',
    description: '代付订单',
    properties: [
        new OA\Property(property: 'systemOrderNo', type: 'string', example: 'W20260418000001'),
        new OA\Property(property: 'merchantOrderNo', type: 'string', example: 'M-WD-0001'),
        new OA\Property(property: 'amount', type: 'string', example: '500.000000'),
        new OA\Property(property: 'actualAmount', type: 'string', example: '500.000000'),
        new OA\Property(property: 'currency', type: 'string', example: 'CNY'),
        new OA\Property(property: 'status', type: 'string', example: 'pending'),
        new OA\Property(property: 'merchantFee', type: 'string', example: '5.000000'),
        new OA\Property(property: 'bankCode', type: 'string', example: 'ICBC'),
        new OA\Property(property: 'bankAccountName', type: 'string', example: '张三'),
        new OA\Property(property: 'bankAccountNo', type: 'string', example: '6222021234567890123'),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2026-04-18 12:00:00'),
    ],
)]
class WithdrawOrder
{
}
