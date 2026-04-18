<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DepositOrder',
    description: '代收订单',
    properties: [
        new OA\Property(property: 'systemOrderNo', type: 'string', description: '系统订单号', example: 'D20260418000001'),
        new OA\Property(property: 'merchantOrderNo', type: 'string', description: '商户订单号', example: 'M-ABC-0001'),
        new OA\Property(property: 'amount', type: 'string', description: '订单金额（字符串避免精度问题）', example: '100.000000'),
        new OA\Property(property: 'actualAmount', type: 'string', description: '实付金额', example: '100.000000'),
        new OA\Property(property: 'currency', type: 'string', example: 'CNY'),
        new OA\Property(property: 'status', type: 'string', description: '订单状态枚举', example: 'pending'),
        new OA\Property(property: 'payUrl', type: 'string', nullable: true, description: '收银台 / 支付链接', example: 'https://pay.example.com/cashier/xxx'),
        new OA\Property(property: 'merchantFee', type: 'string', description: '商户手续费', example: '1.000000'),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2026-04-18 12:00:00'),
    ],
)]
class DepositOrder
{
}
