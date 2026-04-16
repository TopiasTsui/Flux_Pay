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

class ProviderCallbackController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayFactory $gatewayFactory,
    ) {}

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
