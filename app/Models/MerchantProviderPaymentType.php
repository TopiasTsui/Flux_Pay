<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantProviderPaymentType extends BaseModel
{
    
    protected $fillable = ['merchant_id', 'provider_payment_type_id', 'status', 'remark'];

    protected $casts = [
        'status' => 'integer',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function providerPaymentType(): BelongsTo
    {
        return $this->belongsTo(ProviderPaymentType::class);
    }
}
