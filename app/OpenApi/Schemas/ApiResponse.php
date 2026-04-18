<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApiResponse',
    description: '统一响应包络',
    required: ['code', 'message', 'timestamp'],
    properties: [
        new OA\Property(property: 'code', type: 'integer', description: '业务状态码：0 成功；2001 参数校验失败；3001 订单不存在；5000 系统错误', example: 0),
        new OA\Property(property: 'message', type: 'string', example: 'Success'),
        new OA\Property(property: 'data', description: '业务数据，根据接口不同而不同', nullable: true),
        new OA\Property(property: 'timestamp', type: 'integer', description: '服务端 Unix 秒级时间戳', example: 1713350400),
    ],
)]
class ApiResponse
{
}
