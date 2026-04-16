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

class MerchantApiController extends Controller
{
    public function __construct(
        private readonly DepositService $depositService,
        private readonly WithdrawService $withdrawService,
    ) {}

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
