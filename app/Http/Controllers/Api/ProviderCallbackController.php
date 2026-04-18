<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\Order\DepositCallbackReceived;
use App\Events\Order\WithdrawCallbackReceived;
use App\Http\Controllers\Controller;
use App\Models\DepositOrder;
use App\Models\WithdrawOrder;
use App\Services\Gateway\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class ProviderCallbackController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayFactory $gatewayFactory,
    ) {}

    #[OA\Post(
        path: '/api/deposit/{vendor}/callback',
        summary: '三方供应商代收异步回调',
        description: '请求体格式由各供应商自行定义。无论成功失败，本接口始终返回 HTTP 200，body 为字符串 `success` 或 `fail`。',
        tags: ['Callback'],
        parameters: [
            new OA\Parameter(name: 'vendor', in: 'path', required: true, description: '供应商代码', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: '由具体供应商定义的回调字段（form-data 或 JSON）',
            content: [
                new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(type: 'object', additionalProperties: true)),
                new OA\MediaType(mediaType: 'application/x-www-form-urlencoded', schema: new OA\Schema(type: 'object', additionalProperties: true)),
            ],
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '`success` 表示已受理，`fail` 表示处理失败（供应商通常据此决定是否重投）',
                content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(type: 'string', example: 'success')),
            ),
        ],
    )]
    public function depositCallback(Request $request, string $vendor): Response
    {
        try {
            $provider = $request->input('_provider');

            $gateway = $this->gatewayFactory->createFromProvider($provider);
            $result = $gateway->depositCallback($request->all());

            if (! $result->success || ! $result->systemOrderNo) {
                Log::warning('Deposit callback: failed to parse', [
                    'vendor' => $vendor,
                    'data' => $request->all(),
                ]);
                return response('fail', 200);
            }

            $order = DepositOrder::where('system_order_no', $result->systemOrderNo)->first();

            if (! $order) {
                Log::warning('Deposit callback: order not found', [
                    'system_order_no' => $result->systemOrderNo,
                    'vendor' => $vendor,
                ]);
                return response('fail', 200);
            }

            if ($order->status->isFinal()) {
                Log::info('Deposit callback: order already in final state', [
                    'system_order_no' => $result->systemOrderNo,
                    'status' => $order->status->name,
                ]);
                return response('success', 200);
            }

            DepositCallbackReceived::dispatch($order, $result);

            return response('success', 200);
        } catch (\Throwable $e) {
            Log::error('Deposit callback exception', [
                'vendor' => $vendor,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('fail', 200);
        }
    }

    #[OA\Post(
        path: '/api/withdraw/{vendor}/callback',
        summary: '三方供应商代付异步回调',
        description: '请求体格式由各供应商自行定义。无论成功失败，本接口始终返回 HTTP 200，body 为字符串 `success` 或 `fail`。',
        tags: ['Callback'],
        parameters: [
            new OA\Parameter(name: 'vendor', in: 'path', required: true, description: '供应商代码', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: '由具体供应商定义的回调字段（form-data 或 JSON）',
            content: [
                new OA\MediaType(mediaType: 'application/json', schema: new OA\Schema(type: 'object', additionalProperties: true)),
                new OA\MediaType(mediaType: 'application/x-www-form-urlencoded', schema: new OA\Schema(type: 'object', additionalProperties: true)),
            ],
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '`success` 表示已受理，`fail` 表示处理失败',
                content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(type: 'string', example: 'success')),
            ),
        ],
    )]
    public function withdrawCallback(Request $request, string $vendor): Response
    {
        try {
            $provider = $request->input('_provider');

            $gateway = $this->gatewayFactory->createFromProvider($provider);
            $result = $gateway->withdrawCallback($request->all());

            if (! $result->success || ! $result->systemOrderNo) {
                Log::warning('Withdraw callback: failed to parse', [
                    'vendor' => $vendor,
                    'data' => $request->all(),
                ]);
                return response('fail', 200);
            }

            $order = WithdrawOrder::where('system_order_no', $result->systemOrderNo)->first();

            if (! $order) {
                Log::warning('Withdraw callback: order not found', [
                    'system_order_no' => $result->systemOrderNo,
                    'vendor' => $vendor,
                ]);
                return response('fail', 200);
            }

            if ($order->status->isFinal()) {
                Log::info('Withdraw callback: order already in final state', [
                    'system_order_no' => $result->systemOrderNo,
                    'status' => $order->status->name,
                ]);
                return response('success', 200);
            }

            WithdrawCallbackReceived::dispatch($order, $result);

            return response('success', 200);
        } catch (\Throwable $e) {
            Log::error('Withdraw callback exception', [
                'vendor' => $vendor,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('fail', 200);
        }
    }
}
