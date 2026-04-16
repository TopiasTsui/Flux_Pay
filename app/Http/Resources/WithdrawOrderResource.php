<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'systemOrderNo' => $this->system_order_no,
            'merchantOrderNo' => $this->merchant_order_no,
            'amount' => (string) $this->order_amount,
            'actualAmount' => (string) $this->actual_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'merchantFee' => (string) $this->merchant_fee,
            'bankCode' => $this->bank_code,
            'bankAccountName' => $this->bank_account_name,
            'bankAccountNo' => $this->bank_account_no,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
