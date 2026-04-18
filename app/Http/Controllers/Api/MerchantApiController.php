<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BalanceQueryRequest;
use App\Http\Requests\DepositApplyRequest;
use App\Http\Requests\DepositQueryRequest;
use App\Http\Requests\WithdrawApplyRequest;
use App\Http\Requests\WithdrawQueryRequest;
use App\Http\Resources\DepositOrderResource;
use App\Http\Resources\WithdrawOrderResource;
use App\Models\DepositOrder;
use App\Models\WithdrawOrder;
use App\Services\Order\DepositService;
use App\Services\Order\WithdrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class MerchantApiController extends Controller
{
    public function __construct(
        private readonly DepositService $depositService,
        private readonly WithdrawService $withdrawService,
    ) {}

    #[OA\Post(
        path: '/api/deposit/apply',
        summary: '创建代收订单',
        tags: ['Deposit'],
        security: [['merchantSignature' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['merchantNo', 'orderNo', 'amount', 'signature'],
                properties: [
                    new OA\Property(property: 'merchantNo', type: 'string', description: '商户号', example: 'M0001'),
                    new OA\Property(property: 'orderNo', type: 'string', maxLength: 64, description: '商户订单号', example: 'M-ABC-0001'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', minimum: 0.01, example: 100),
                    new OA\Property(property: 'currency', type: 'string', maxLength: 10, nullable: true, example: 'CNY'),
                    new OA\Property(property: 'paymentTypeCode', type: 'string', nullable: true, example: 'BANK_QR'),
                    new OA\Property(property: 'callbackUrl', type: 'string', format: 'uri', nullable: true, description: '商户异步通知 URL'),
                    new OA\Property(property: 'bankCode', type: 'string', nullable: true, example: 'ICBC'),
                    new OA\Property(property: 'payerName', type: 'string', nullable: true),
                    new OA\Property(property: 'extend', type: 'string', nullable: true, description: '扩展字段，原样回传'),
                    new OA\Property(property: 'signature', type: 'string', description: 'HMAC-SHA256 签名'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '统一响应包络，data 为 DepositOrder',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/ApiResponse'),
                        new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/DepositOrder')]),
                    ],
                ),
            ),
        ],
    )]
    public function depositApply(DepositApplyRequest $request): JsonResponse
    {
        try {
            $merchant = $request->input('_merchant');

            $order = $this->depositService->apply($merchant, [
                'merchant_order_no' => $request->input('orderNo'),
                'amount' => $request->input('amount'),
                'payment_type_code' => $request->input('paymentTypeCode'),
                'notify_url' => $request->input('callbackUrl'),
                'bank_code' => $request->input('bankCode'),
                'payer_name' => $request->input('payerName'),
                'extend' => $request->input('extend'),
            ]);

            return $this->apiResponse(0, 'Success', new DepositOrderResource($order));
        } catch (\Throwable $e) {
            Log::error('Deposit apply failed', [
                'error' => $e->getMessage(),
                'merchant' => $request->input('merchantNo'),
                'orderNo' => $request->input('orderNo'),
            ]);

            return $this->apiResponse(5000, 'System error: ' . $e->getMessage());
        }
    }

    #[OA\Post(
        path: '/api/deposit/query',
        summary: '查询代收订单',
        tags: ['Deposit'],
        security: [['merchantSignature' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['merchantNo', 'orderNo', 'signature'],
                properties: [
                    new OA\Property(property: 'merchantNo', type: 'string', example: 'M0001'),
                    new OA\Property(property: 'orderNo', type: 'string', maxLength: 64, example: 'M-ABC-0001'),
                    new OA\Property(property: 'signature', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '统一响应包络，data 为 DepositOrder（订单不存在时 code=3001、data=null）',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/ApiResponse'),
                        new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/DepositOrder', nullable: true)]),
                    ],
                ),
            ),
        ],
    )]
    public function depositQuery(DepositQueryRequest $request): JsonResponse
    {
        try {
            $merchant = $request->input('_merchant');

            $order = DepositOrder::where('merchant_id', $merchant->id)
                ->where('merchant_order_no', $request->input('orderNo'))
                ->latest()
                ->first();

            if (! $order) {
                return $this->apiResponse(3001, 'Order not found');
            }

            return $this->apiResponse(0, 'Success', new DepositOrderResource($order));
        } catch (\Throwable $e) {
            Log::error('Deposit query failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->apiResponse(5000, 'System error: ' . $e->getMessage());
        }
    }

    #[OA\Post(
        path: '/api/withdraw/apply',
        summary: '创建代付订单',
        tags: ['Withdraw'],
        security: [['merchantSignature' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['merchantNo', 'orderNo', 'amount', 'bankCode', 'bankAccountName', 'bankAccountNo', 'signature'],
                properties: [
                    new OA\Property(property: 'merchantNo', type: 'string', example: 'M0001'),
                    new OA\Property(property: 'orderNo', type: 'string', maxLength: 64, example: 'M-WD-0001'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', minimum: 0.01, example: 500),
                    new OA\Property(property: 'currency', type: 'string', maxLength: 10, nullable: true, example: 'CNY'),
                    new OA\Property(property: 'bankCode', type: 'string', example: 'ICBC'),
                    new OA\Property(property: 'bankAccountName', type: 'string', example: '张三'),
                    new OA\Property(property: 'bankAccountNo', type: 'string', example: '6222021234567890123'),
                    new OA\Property(property: 'bankBranch', type: 'string', nullable: true, example: '北京分行'),
                    new OA\Property(property: 'callbackUrl', type: 'string', format: 'uri', nullable: true),
                    new OA\Property(property: 'extend', type: 'string', nullable: true),
                    new OA\Property(property: 'signature', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '统一响应包络，data 为 WithdrawOrder',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/ApiResponse'),
                        new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/WithdrawOrder')]),
                    ],
                ),
            ),
        ],
    )]
    public function withdrawApply(WithdrawApplyRequest $request): JsonResponse
    {
        try {
            $merchant = $request->input('_merchant');

            $order = $this->withdrawService->apply($merchant, [
                'merchant_order_no' => $request->input('orderNo'),
                'amount' => $request->input('amount'),
                'payment_type_code' => $request->input('paymentTypeCode'),
                'notify_url' => $request->input('callbackUrl'),
                'bank_code' => $request->input('bankCode'),
                'bank_account_name' => $request->input('bankAccountName'),
                'bank_account_no' => $request->input('bankAccountNo'),
                'bank_branch' => $request->input('bankBranch'),
                'extend' => $request->input('extend'),
            ]);

            return $this->apiResponse(0, 'Success', new WithdrawOrderResource($order));
        } catch (\Throwable $e) {
            Log::error('Withdraw apply failed', [
                'error' => $e->getMessage(),
                'merchant' => $request->input('merchantNo'),
                'orderNo' => $request->input('orderNo'),
            ]);

            return $this->apiResponse(5000, 'System error: ' . $e->getMessage());
        }
    }

    #[OA\Post(
        path: '/api/withdraw/query',
        summary: '查询代付订单',
        tags: ['Withdraw'],
        security: [['merchantSignature' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['merchantNo', 'orderNo', 'signature'],
                properties: [
                    new OA\Property(property: 'merchantNo', type: 'string', example: 'M0001'),
                    new OA\Property(property: 'orderNo', type: 'string', maxLength: 64, example: 'M-WD-0001'),
                    new OA\Property(property: 'signature', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '统一响应包络，data 为 WithdrawOrder（订单不存在时 code=3001、data=null）',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/ApiResponse'),
                        new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/WithdrawOrder', nullable: true)]),
                    ],
                ),
            ),
        ],
    )]
    public function withdrawQuery(WithdrawQueryRequest $request): JsonResponse
    {
        try {
            $merchant = $request->input('_merchant');

            $order = WithdrawOrder::where('merchant_id', $merchant->id)
                ->where('merchant_order_no', $request->input('orderNo'))
                ->latest()
                ->first();

            if (! $order) {
                return $this->apiResponse(3001, 'Order not found');
            }

            return $this->apiResponse(0, 'Success', new WithdrawOrderResource($order));
        } catch (\Throwable $e) {
            Log::error('Withdraw query failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->apiResponse(5000, 'System error: ' . $e->getMessage());
        }
    }

    #[OA\Post(
        path: '/api/balance/query',
        summary: '查询商户余额',
        tags: ['Balance'],
        security: [['merchantSignature' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['merchantNo', 'signature'],
                properties: [
                    new OA\Property(property: 'merchantNo', type: 'string', example: 'M0001'),
                    new OA\Property(property: 'signature', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '统一响应包络，data 为 Balance',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/ApiResponse'),
                        new OA\Schema(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Balance')]),
                    ],
                ),
            ),
        ],
    )]
    public function balanceQuery(BalanceQueryRequest $request): JsonResponse
    {
        try {
            $merchant = $request->input('_merchant');

            return $this->apiResponse(0, 'Success', [
                'merchantNo' => $merchant->code,
                'currency' => $merchant->currency_code,
                'totalBalance' => (string) $merchant->total_balance,
                'availableBalance' => (string) $merchant->available_balance,
                'holdBalance' => (string) $merchant->hold_balance,
            ]);
        } catch (\Throwable $e) {
            Log::error('Balance query failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->apiResponse(5000, 'System error: ' . $e->getMessage());
        }
    }

    private function apiResponse(int $code, string $message, mixed $data = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time(),
        ]);
    }
}
