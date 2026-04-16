<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderPaymentType extends BaseModel
{
    
    protected $fillable = [
        'provider_id', 'payment_type_id', 'type', 'alias', 'status', 'weight',
        'single_min_amount', 'single_max_amount', 'daily_amount_limit', 'daily_count_limit',
        'current_daily_amount', 'reset_time',
        'deposit_fee_type', 'deposit_fee', 'withdraw_fee_type', 'withdraw_fee', 'agent_fee',
    ];

    protected $casts = [
        'type' => 'string',
        'status' => 'integer',
        'deposit_fee_type' => 'integer',
        'withdraw_fee_type' => 'integer',
        'weight' => 'integer',
        'daily_count_limit' => 'integer',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function merchantProviderPaymentTypes(): HasMany
    {
        return $this->hasMany(MerchantProviderPaymentType::class);
    }
}
