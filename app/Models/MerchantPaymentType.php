<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantPaymentType extends BaseModel
{
    
    protected $fillable = [
        'merchant_id', 'payment_type_id', 'status',
        'single_min_amount', 'single_max_amount',
        'deposit_fee_type', 'deposit_fee', 'deposit_agents_fee',
        'withdraw_fee_type', 'withdraw_fee', 'withdraw_agents_fee',
    ];

    protected $casts = [
        'status' => 'integer',
        'deposit_fee_type' => 'integer',
        'withdraw_fee_type' => 'integer',
        'deposit_agents_fee' => 'array',
        'withdraw_agents_fee' => 'array',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }
}
