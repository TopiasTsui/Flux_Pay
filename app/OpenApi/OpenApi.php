<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'FluxPay API',
    description: '第四方代收代付聚合系统 — 商户接入与三方供应商回调接口文档',
    contact: new OA\Contact(name: 'FluxPay'),
)]
#[OA\Server(url: '/', description: '当前环境')]
#[OA\Tag(name: 'Deposit', description: '代收（充值）')]
#[OA\Tag(name: 'Withdraw', description: '代付（提现）')]
#[OA\Tag(name: 'Balance', description: '余额查询')]
#[OA\Tag(name: 'Callback', description: '三方供应商异步回调')]
#[OA\SecurityScheme(
    securityScheme: 'merchantSignature',
    type: 'apiKey',
    description: '商户签名鉴权：请求体须包含 merchantNo 与 signature。signature = HMAC-SHA256(按字段名升序拼接的 querystring, 商户密钥)',
    name: 'signature',
    in: 'query',
)]
class OpenApi
{
}
