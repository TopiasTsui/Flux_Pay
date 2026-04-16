<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DepositOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FrontendPayController extends Controller
{
    public function cashier(string $token): View
    {
        try {
            $data = json_decode(Crypt::decryptString($token), true);

            if (! $data || ! isset($data['order_id'])) {
                return view('payment.error', ['message' => 'Invalid payment token']);
            }

            $order = DepositOrder::find($data['order_id']);

            if (! $order) {
                return view('payment.error', ['message' => 'Order not found']);
            }

            if ($order->status->isFinal()) {
                return view('payment.error', ['message' => 'Order is no longer available for payment']);
            }

            return view('payment.cashier', [
                'order' => $order,
                'merchant' => $order->merchant,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cashier page error', [
                'error' => $e->getMessage(),
            ]);

            return view('payment.error', ['message' => 'Invalid or expired payment link']);
        }
    }

    public function error(): View
    {
        return view('payment.error', ['message' => 'An error occurred']);
    }
}
